<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class McpSecurityService
{
    /**
     * Valid values the AI may return in its JSON verdict.
     * Anything outside these sets is treated as an invalid response.
     */
    public const VALID_DECISIONS    = ['SAFE', 'SUSPICIOUS', 'MALICIOUS'];
    public const VALID_ATTACK_TYPES = ['brute_force', 'credential_stuffing', 'bot_attack', 'dos', 'normal_user'];

    /**
     * Live-run observability counters (journal reviewer Comment 1).
     *
     * Incremented on every analyzeLogin() call so the evaluation harness can
     * report, separately from the detection metrics:
     *   ai_calls   - total LLM invocations attempted
     *   ai_invalid - responses that failed parsing/validation (parseAiResponse
     *                returned null) => treated as AI-unavailable
     *   ai_errors  - transport failures (timeout, connection, HTTP errors)
     * The paper's live-model section reports the unparseable rate and the
     * transport-error rate from these counters (reset per harness run).
     */
    public static array $liveStats = ['ai_calls' => 0, 'ai_invalid' => 0, 'ai_errors' => 0];

    /**
     * Analyze login behavior using MCP AI (MiniMax)
     *
     * @param array $data Login data including IP, email, and attempt counts
     * @return array MCP structured response with decision, attack_type, risk_score,
     *               confidence, recommended_action + audit fields
     *               (ai_available, ai_decision, fusion, source)
     */
    public static function analyzeLogin(array $data): array
    {
        $driver = config('services.minimax.driver', 'direct');

        Log::info('[MCP Security] Analyzing login', [
            'ip' => $data['ip'],
            'email' => $data['email'],
            'ip_attempts' => $data['ip_attempts'],
            'email_attempts' => $data['email_attempts'],
            'driver' => $driver,
        ]);

        // Deterministic rule score (acts as the FLOOR of the fused score)
        $ruleScore = self::getRiskScore($data);

        // Start measuring AI analysis response time
        $startTime = microtime(true);
        $ai = null;
        self::$liveStats['ai_calls']++;

        /*
         * Problem 6 fix: only pseudonymised identifiers cross the outbound
         * boundary (raw values stay in local logs/Redis). Counts unchanged.
         */
        $outbound = \App\Services\PrivacyService::context($data);

        try {
            if ($driver === 'mcp') {
                // Problem 3: protocol-conformant MCP path
                // Laravel -> McpClient (JSON-RPC 2.0) -> /api/mcp -> analyze_login_risk tool -> LLM
                $verdict = \App\Mcp\McpClient::make()->callTool('analyze_login_risk', $outbound);
            } else {
                // Direct LLM API path (baseline for the ablation study)
                $verdict = json_decode(\App\Mcp\MiniMaxClient::chat(
                    \App\Mcp\SecurityPrompt::system(),
                    \App\Mcp\SecurityPrompt::user($outbound)
                ), true);
            }

            // Parse + validate the AI verdict (null => AI unavailable)
            $ai = self::parseAiResponse(is_array($verdict) ? json_encode($verdict) : null);

            if ($ai === null) {
                self::$liveStats['ai_invalid']++;

                Log::warning('[MCP Security] Invalid AI response, falling back to rules', [
                    'raw_response' => is_string($verdict) ? mb_substr($verdict, 0, 300) : json_encode($verdict),
                ]);
            }

        } catch (\Exception $e) {
            self::$liveStats['ai_errors']++;

            Log::error('[MCP Security] Error analyzing login', [
                'error' => $e->getMessage(),
                'ip' => $data['ip'],
                'email' => $data['email'],
                'driver' => $driver,
            ]);
            // AI unavailable -> deterministic rule fallback (tiered handling in middleware)
            $ai = null;
        }

        $responseTimeMs = round((microtime(true) - $startTime) * 1000, 2);

        // Fuse rule score + AI verdict into the final decision
        $mcp = self::fuseDecision($ai, $ruleScore);

        // Risk-adaptive policy when the AI layer was unavailable (Problem 2 fix)
        $mcp = self::applyDegradedPolicy($mcp);

        Log::info('[MCP Security] Analysis result', [
            'mcp' => $mcp,
            'response_time_ms' => $responseTimeMs
        ]);

        // Always log to mcp_security channel for every analysis
        Log::channel('mcp_security')->info('MCP_DECISION', [
            'ip' => $data['ip'],
            'email' => $data['email'],
            'decision' => $mcp['decision'],
            'attack_type' => $mcp['attack_type'],
            'risk_score' => $mcp['risk_score'],
            'confidence' => $mcp['confidence'],
            'recommended_action' => $mcp['recommended_action'],
            'ai_available' => $mcp['ai_available'],
            'ai_decision' => $mcp['ai_decision'],
            'fusion' => $mcp['fusion'],
            'degraded_mode' => $mcp['degraded_mode'],
            'driver' => $driver,
            'response_time_ms' => $responseTimeMs,
        ]);

        return $mcp;
    }

    /**
     * Risk-adaptive fallback policy when the AI layer is unavailable
     * (Problem 2 fix - replaces the blanket fail-open).
     *
     *   risk >= malicious band -> fail_closed        (block_ip - the rules alone block)
     *   risk >= suspicious band -> heightened_throttle (emergency_throttle action; the middleware
     *                                       N-strike blocks repeat medium-risk traffic)
     *   below             -> fail_open_monitored (allow + audit log entry)
     */
    public static function applyDegradedPolicy(array $mcp): array
    {
        $mcp['degraded_mode'] = null;

        if ($mcp['ai_available']) {
            return $mcp;
        }

        $risk = (int) $mcp['risk_score'];

        if ($risk >= self::band('malicious')) {
            $mcp['recommended_action'] = 'block_ip';
            $mcp['degraded_mode'] = 'fail_closed';
        } elseif ($risk >= self::band('suspicious')) {
            $mcp['recommended_action'] = 'emergency_throttle';
            $mcp['degraded_mode'] = 'heightened_throttle';
        } else {
            $mcp['degraded_mode'] = 'fail_open_monitored';
        }

        return $mcp;
    }

    /**
     * Parse and validate the raw model output.
     *
     * Returns a normalised AI verdict array, or null when the output is
     * missing/invalid (bad JSON, missing/unknown decision, out-of-range
     * values). Callers treat null as "AI unavailable".
     */
    public static function parseAiResponse(?string $content): ?array
    {
        if (!is_string($content) || trim($content) === '') {
            return null;
        }

        $trimmed = trim($content);
        $ai = json_decode($trimmed, true);

        // The model may wrap the JSON in prose - extract the outermost object
        if (!is_array($ai) && preg_match('/\{.*\}/s', $trimmed, $m)) {
            $ai = json_decode($m[0], true);
        }

        if (!is_array($ai) || !isset($ai['decision'])) {
            return null;
        }

        $decision = strtoupper(trim((string) $ai['decision']));
        if (!in_array($decision, self::VALID_DECISIONS, true)) {
            return null;
        }
        $ai['decision'] = $decision;

        // Numeric fields must actually be numeric - anything else is treated
        // as absent (never trusted for scoring)
        $ai['risk_score'] = isset($ai['risk_score']) && is_numeric($ai['risk_score'])
            ? max(0, min(100, (int) $ai['risk_score']))
            : null;
        $ai['confidence'] = isset($ai['confidence']) && is_numeric($ai['confidence'])
            ? max(0.0, min(1.0, (float) $ai['confidence']))
            : null;

        return $ai;
    }

    /**
     * Decision fusion (Problem 1 fix).
     *
     * The deterministic rule score is a FLOOR: the AI may raise the effective
     * score but never lower it. The AI verdict has genuine authority in the
     * 50-79 band (it can escalate to MALICIOUS/blocking), and AI leniency is
     * bounded (a confident SAFE can soften MALICIOUS-band evidence only down
     * to SUSPICIOUS/step-up, never to a full allow).
     *
     * Fusion rules, applied in order:
     *   F1  finalScore >= 80                                 -> MALICIOUS  (deterministic guardrail)
     *   F2  AI = MALICIOUS and finalScore >= 50              -> MALICIOUS  (AI escalation now blocks in 50-79)
     *   F3  AI = SAFE, confidence >= 0.8, finalScore 50-79   -> SUSPICIOUS (bounded AI leniency: step-up, not allow)
     *   F4  finalScore >= 50                                 -> SUSPICIOUS (rule-based medium band)
     *   F5  otherwise                                        -> SAFE
     *
     * @param array|null $ai        Validated AI verdict (null = AI unavailable)
     * @param int        $ruleScore Deterministic rule-based score (0-100)
     */
    public static function fuseDecision(?array $ai, int $ruleScore): array
    {
        $aiAvailable = $ai !== null;
        $aiDecision  = $aiAvailable ? $ai['decision'] : null;

        // Rules are the floor: AI can raise the score, never lower it
        $aiScore    = $aiAvailable ? (int) ($ai['risk_score'] ?? $ruleScore) : $ruleScore;
        $finalScore = max($ruleScore, $aiScore);

        if ($finalScore >= self::band('malicious')) {
            $decision = 'MALICIOUS';
            $fusion   = 'F1_rule_guardrail_high_score';
        } elseif ($aiAvailable && $aiDecision === 'MALICIOUS' && $finalScore >= self::band('suspicious')) {
            $decision = 'MALICIOUS';
            $fusion   = 'F2_ai_escalation';
        } elseif ($aiAvailable && $aiDecision === 'SAFE' && ($ai['confidence'] ?? 0) >= 0.8 && $finalScore >= self::band('suspicious')) {
            $decision = 'SUSPICIOUS';
            $fusion   = 'F3_ai_bounded_leniency';
        } elseif ($finalScore >= self::band('suspicious')) {
            $decision = 'SUSPICIOUS';
            $fusion   = 'F4_rule_medium_score';
        } else {
            $decision = 'SAFE';
            $fusion   = 'F5_low_score';
        }

        // attack_type: prefer a valid AI classification, else derive from score band
        $attackType = ($aiAvailable && in_array($ai['attack_type'] ?? '', self::VALID_ATTACK_TYPES, true))
            ? $ai['attack_type']
            : self::bandAttackType($finalScore, $decision);

        // recommended_action follows the FINAL decision (a MALICIOUS decision always maps to block_ip)
        $recommendedAction = $decision === 'MALICIOUS'
            ? 'block_ip'
            : self::getRecommendedAction($finalScore);

        return [
            'decision'           => $decision,
            'attack_type'        => $attackType,
            'risk_score'         => $finalScore,
            'confidence'         => $aiAvailable ? ($ai['confidence'] ?? 0.5) : 0.5,
            'recommended_action' => $recommendedAction,
            'ai_available'       => $aiAvailable,
            'ai_decision'        => $aiDecision,
            'fusion'             => $fusion,
            'source'             => $aiAvailable ? 'ai' : 'rule_fallback',
        ];
    }

    /**
     * Derive an attack-type label from the score band (fallback only).
     */
    private static function bandAttackType(int $score, string $decision): string
    {
        if ($decision === 'SAFE') {
            return 'normal_user';
        }

        return $score >= self::band('malicious') ? 'brute_force' : 'credential_stuffing';
    }

    /**
     * Configurable risk-score band (Major-fix M8): malicious|suspicious|captcha.
     */
    private static function band(string $name): int
    {
        $defaults = ['malicious' => 80, 'suspicious' => 50, 'captcha' => 30];

        return (int) config('services.minimax.risk_band_'.$name, $defaults[$name]);
    }

    /**
     * Get AI risk score for login attempt (0-100) - rule-based fallback
     *
     * Weights are configurable (services.minimax.rule_weights, Major-fix M8);
     * defaults reproduce the published scoring table exactly.
     *
     * @param array $data Login data
     * @return int Risk score
     */
    public static function getRiskScore(array $data): int
    {
        $baseScore = 0;

        // Base score from attempt counts
        $ipAttempts = (int) ($data['ip_attempts'] ?? 0);
        $emailAttempts = (int) ($data['email_attempts'] ?? 0);

        foreach ((array) config('services.minimax.rule_weights.ip', [[10, 50], [5, 30], [3, 15]]) as [$min, $points]) {
            if ($ipAttempts >= $min) {
                $baseScore += $points;
                break;
            }
        }

        foreach ((array) config('services.minimax.rule_weights.email', [[5, 40], [3, 25]]) as [$min, $points]) {
            if ($emailAttempts >= $min) {
                $baseScore += $points;
                break;
            }
        }

        return min(100, $baseScore);
    }

    /**
     * Get recommended action based on risk score
     *
     * @param int $riskScore Risk score (0-100)
     * @return string Recommended action
     */
    public static function getRecommendedAction(int $riskScore): string
    {
        if ($riskScore >= self::band('malicious')) {
            return 'block_ip';
        } elseif ($riskScore >= self::band('suspicious')) {
            return 'otp';
        } elseif ($riskScore >= self::band('captcha')) {
            return 'captcha';
        } else {
            return 'monitor';
        }
    }
}
