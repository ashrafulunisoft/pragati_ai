<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\McpSecurityService;
use App\Services\SecurityStore;
use App\Services\SecurityInputSanitizer;
use App\Services\ChallengeService;
use App\Events\McpAttackDetected;
use Symfony\Component\HttpFoundation\Response;

class McpMaliciousLoginMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $ip    = $request->ip();
        $email = $request->input('email');

        // Skip if no email provided (not a login attempt)
        if (empty($email)) {
            return $next($request);
        }

        /*
         * Problem 4 fix: untrusted inputs are sanitised before they reach
         * Redis keys or the LLM prompt. An invalid/malformed email is not
         * analysed at all - the controller validation rejects it (422).
         */
        $email = SecurityInputSanitizer::email($email);
        if ($email === null) {
            Log::warning('[MCP Middleware] Malformed email input - skipping AI analysis, deferring to validation', [
                'ip' => $ip,
            ]);

            return $next($request);
        }

        $ip = SecurityInputSanitizer::ip($ip) ?? '0.0.0.0';

        $ipAttempts    = (int) SecurityStore::get("attack:login:ip:$ip") ?? 0;
        $emailAttempts = (int) SecurityStore::get("attack:login:email:$email") ?? 0;

        /*
         * Early block check (M9): already-blocked identifiers are rejected
         * without re-running (billable) AI analysis - this also protects the
         * LLM quota from blocked attackers hammering the login endpoint.
         * Blocked emails are scoped blocks on shared NATs (see blockIp()).
         */
        if (SecurityStore::get("blocked:ip:$ip") || SecurityStore::get("blocked:email:$email")) {
            return response()->json([
                'error' => 'AI Security System: Access blocked due to detected attack activity. Try again later.'
            ], 403)
                ->header('X-Mcp-Decision', 'MALICIOUS')
                ->header('X-Mcp-Enforcement', 'BLOCKED');
        }

        // Analysis trigger thresholds are configurable (M8: LOGIN_ATTEMPT_THRESHOLD_*)
        $thresholdIp    = (int) config('services.minimax.threshold_ip_analyze', 5);
        $thresholdEmail = (int) config('services.minimax.threshold_email_analyze', 3);

        if ($ipAttempts >= $thresholdIp || $emailAttempts >= $thresholdEmail) {
            Log::info('[MCP Middleware] Threshold exceeded, calling AI analysis', [
                'ip' => $ip,
                'email' => $email,
                'ip_attempts' => $ipAttempts,
                'email_attempts' => $emailAttempts
            ]);

            // Get full MCP response with decision, attack_type, risk_score, confidence, recommended_action
            $mcp = McpSecurityService::analyzeLogin([
                'ip' => $ip,
                'email' => $email,
                'ip_attempts' => $ipAttempts,
                'email_attempts' => $emailAttempts,
            ]);

            // Block if:
            // 1. Decision is MALICIOUS (rules guardrail OR AI escalation), OR
            // 2. Risk score is HIGH (>= malicious band)
            $shouldBlock = false;
            $blockReason = '';

            if ($mcp['decision'] === 'MALICIOUS') {
                $shouldBlock = true;
                $blockReason = 'MALICIOUS decision detected';
            } elseif ($mcp['risk_score'] >= (int) config('services.minimax.risk_band_malicious', 80)) {
                $shouldBlock = true;
                $blockReason = 'High risk score (' . $mcp['risk_score'] . ') detected';
            }

            if ($shouldBlock) {
                $this->blockIp($ip, (int) config('services.minimax.block_ttl', 3600), $blockReason, $email, $mcp, $ipAttempts, $emailAttempts);

                return response()->json([
                    'error' => 'AI Security System: ' . $blockReason . '. Access blocked for 1 hour.'
                ], 403)
                    ->header('X-Mcp-Decision', 'MALICIOUS')
                    ->header('X-Mcp-Enforcement', 'BLOCKED');
            }

            /*
             * Problem 2 fix - risk-adaptive behaviour while the AI layer is down:
             *
             *   emergency_throttle (AI unavailable + risk 50-79):
             *     allow the first (degraded_threshold-1) medium-risk analyses per IP,
             *     then block for degraded_block_ttl (degraded-security mode).
             */
            if (($mcp['recommended_action'] ?? null) === 'emergency_throttle') {
                $threshold = (int) config('services.minimax.degraded_threshold', 3);
                $blockTtl  = (int) config('services.minimax.degraded_block_ttl', 900);

                $strikes = SecurityStore::incrWithTtl("mcp:degraded:ip:$ip", 900);

                if ($strikes >= $threshold) {
                    $reason = "AI analysis unavailable; repeated medium-risk activity (strike {$strikes}/{$threshold})";
                    $this->blockIp($ip, $blockTtl, $reason, $email, $mcp, $ipAttempts, $emailAttempts);

                    return response()->json([
                        'error' => 'Security system operating in heightened-security mode (AI analysis unavailable). '
                                 . 'Access temporarily restricted. Try again later.'
                    ], 429)
                        ->header('X-Mcp-Decision', 'SUSPICIOUS')
                        ->header('X-Mcp-Enforcement', 'THROTTLED');
                }

                Log::warning('[MCP Middleware] Degraded-security mode: AI unavailable, medium risk allowed with monitoring', [
                    'ip' => $ip,
                    'email' => $email,
                    'risk_score' => $mcp['risk_score'],
                    'strike' => $strikes,
                    'threshold' => $threshold,
                ]);

                $response = $next($request);
                $response->headers->set('X-Mcp-Decision', 'SUSPICIOUS');
                $response->headers->set('X-Mcp-Enforcement', 'ALLOWED');
                return $response;
            }

            /*
             * Major-fix M10 - CAPTCHA/OTP step-up is now ENFORCED.
             *
             * When the AI layer is available and the fused verdict is not
             * MALICIOUS, a `captcha` (band 30-49) or `otp` (band 50-79)
             * recommended action answers the request with HTTP 428 plus a
             * single-use challenge. A request that already carries a valid
             * challenge response passes through.
             */
            if (config('services.minimax.challenge_enabled', true)
                && $mcp['ai_available']
                && in_array($mcp['recommended_action'] ?? '', ['captcha', 'otp'], true)) {

                $type = $mcp['recommended_action'];

                // Already solved? Verify the presented token and pass through.
                if (ChallengeService::verify(
                    $request->input('security_challenge_token'),
                    (string) $request->input('security_challenge_answer')
                )) {
                    Log::channel('mcp_security')->info('CHALLENGE_PASSED', [
                        'type' => $type, 'ip' => $ip, 'email' => $email,
                    ]);

                    $response = $next($request);
                    $response->headers->set('X-Mcp-Decision', 'SUSPICIOUS');
                    $response->headers->set('X-Mcp-Enforcement', 'CHALLENGE_SOLVED');
                    return $response;
                }

                // Challenge-issue rate limit (prevents challenge-flooding DoS)
                $maxPerIp = (int) config('services.minimax.challenge_max_per_ip', 10);
                $issued   = SecurityStore::incrWithTtl("challenge:issued:ip:$ip", 900);
                if ($issued > $maxPerIp) {
                    return response()->json([
                        'error' => 'Too many verification challenges requested. Try again later.'
                    ], 429)
                        ->header('X-Mcp-Decision', 'MALICIOUS')
                        ->header('X-Mcp-Enforcement', 'THROTTLED');
                }

                $challenge = ChallengeService::issue($type, $ip, $email);

                return response()->json([
                    'error'            => 'Security verification required before continuing.',
                    'required_action'  => $type,
                    'challenge'        => $challenge,
                ] + (
                    // If the client attempted an answer and failed, say so
                    $request->has('security_challenge_answer')
                        ? ['detail' => 'The verification response was invalid or expired.']
                        : []
                ), 428)
                    ->header('X-Mcp-Decision', 'SUSPICIOUS')
                    ->header('X-Mcp-Enforcement', 'CHALLENGED');
            }
        }

        $response = $next($request);
        if (!$response->headers->has('X-Mcp-Decision')) {
            $decision = isset($mcp) ? ($mcp['decision'] ?? 'SAFE') : 'SAFE';
            $response->headers->set('X-Mcp-Decision', $decision);
            $response->headers->set('X-Mcp-Enforcement', 'ALLOWED');
        }

        return $response;
    }

    /**
     * Apply the block, log it and fire the admin alert event.
     *
     * Major-fix M9 - shared-NAT scoping: when several DISTINCT accounts have
     * recently logged in successfully from this IP (an office/campus/mobile
     * NAT), blocking the whole IP would take out every legitimate user
     * behind it. In that case the block is scoped to the attacking ACCOUNT
     * (blocked:email:...) while the rest of the NAT keeps working. IPs with
     * no such evidence are blocked whole, as before.
     */
    private function blockIp(string $ip, int $ttl, string $reason, string $email, array $mcp, int $ipAttempts, int $emailAttempts): void
    {
        $sharedThreshold = (int) config('services.minimax.shared_ip_success_threshold', 3);
        $distinctSuccess = SecurityStore::scard("success:emails:ip:$ip");
        $isSharedIp      = $distinctSuccess >= $sharedThreshold;

        if ($isSharedIp) {
            SecurityStore::setex("blocked:email:$email", $ttl, 1);

            // Problem 8 fix: Shared-IP abuse circuit breaker (adversarial NAT gaming defense)
            SecurityStore::sAddWithTtl("blocked:accounts:ip:$ip", $email, $ttl);
            $blockedAccountsCount = SecurityStore::scard("blocked:accounts:ip:$ip");
            $maxBlockedAccounts   = (int) config('services.minimax.shared_ip_max_blocked_accounts', 5);
            $maxIpAttempts        = (int) config('services.minimax.shared_ip_max_ip_attempts', 25);

            if ($blockedAccountsCount >= $maxBlockedAccounts || $ipAttempts >= $maxIpAttempts) {
                // Trip emergency IP quarantine
                $quarantineTtl = (int) config('services.minimax.degraded_block_ttl', 900);
                SecurityStore::setex("blocked:ip:$ip", $quarantineTtl, 1);

                Log::warning('[MCP Middleware] Shared-IP abuse circuit breaker tripped: IP quarantined due to widespread account attacks', [
                    'ip' => $ip,
                    'blocked_accounts_count' => $blockedAccountsCount,
                    'max_allowed_blocked' => $maxBlockedAccounts,
                    'ip_attempts' => $ipAttempts,
                    'max_allowed_attempts' => $maxIpAttempts,
                    'quarantine_ttl' => $quarantineTtl,
                ]);
            }
        } else {
            SecurityStore::setex("blocked:ip:$ip", $ttl, 1);
        }

        Log::warning('[MCP Middleware] '.($isSharedIp ? 'Account blocked (shared-IP detected)' : 'IP blocked').' - ' . $reason, [
            'ip' => $ip,
            'email' => $email,
            'block_scope' => $isSharedIp ? 'email' : 'ip',
            'distinct_successful_accounts' => $distinctSuccess,
            'attack_type' => $mcp['attack_type'],
            'risk_score' => $mcp['risk_score'],
            'decision' => $mcp['decision'],
            'ip_attempts' => $ipAttempts,
            'email_attempts' => $emailAttempts,
            'ttl' => $ttl,
        ]);

        // Trigger event for email alert (wrapped in try-catch to prevent email errors from blocking response)
        try {
            event(new McpAttackDetected(
                [
                    'ip' => $ip,
                    'email' => $email
                ],
                $mcp
            ));
        } catch (\Exception $e) {
            // Log email error but don't block response
            Log::error('[MCP Middleware] Failed to send security alert email', [
                'error' => $e->getMessage(),
                'ip' => $ip,
                'email' => $email
            ]);
        }
    }
}
