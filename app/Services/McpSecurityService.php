<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class McpSecurityService
{
    /**
     * Analyze login behavior using MCP AI (MiniMax)
     *
     * @param array $data Login data including IP, email, and attempt counts
     * @return array MCP structured response with decision, attack_type, risk_score, confidence, recommended_action
     */
    public static function analyzeLogin(array $data): array
    {
        $apiKey = config('services.minimax.api_key');
        $host   = config('services.minimax.host', 'https://api.minimax.io');
        $model  = config('services.minimax.model', 'MiniMax-M2.1');

        $prompt = <<<PROMPT
You are a cybersecurity AI system.

Respond ONLY in JSON format:
{
  "decision": "SAFE or MALICIOUS",
  "attack_type": "brute_force | credential_stuffing | bot_attack | dos | normal_user",
  "risk_score": 0-100,
  "confidence": 0-1,
  "recommended_action": "block_ip | captcha | otp | monitor"
}

Context:
IP: {$data['ip']}
Email: {$data['email']}
IP Attempts: {$data['ip_attempts']}
Email Attempts: {$data['email_attempts']}
Time Window: 15 minutes
PROMPT;

        Log::info('[MCP Security] Analyzing login', [
            'ip' => $data['ip'],
            'email' => $data['email'],
            'ip_attempts' => $data['ip_attempts'],
            'email_attempts' => $data['email_attempts']
        ]);

        // Default response
        $defaultResponse = [
            'decision' => 'SAFE',
            'attack_type' => 'normal_user',
            'risk_score' => self::getRiskScore($data),
            'confidence' => 0.5,
            'recommended_action' => 'monitor'
        ];

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$apiKey,
                'Content-Type'  => 'application/json',
            ])->post($host.'/v1/chat/completions', [
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => 'You are a cybersecurity AI. Respond ONLY in valid JSON format.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0,
                'max_tokens' => 200
            ]);

            $responseArray = $response->json();
            $content = $responseArray['choices'][0]['message']['content'] ?? '{}';

            // Parse JSON response
            $mcp = json_decode($content, true);

            // Validate and merge with defaults
            if (!is_array($mcp) || !isset($mcp['decision'])) {
                Log::warning('[MCP Security] Invalid JSON response, using defaults', [
                    'raw_response' => $content
                ]);
                // Still log the default response
                Log::channel('mcp_security')->info('MCP_DECISION', [
                    'ip' => $data['ip'],
                    'email' => $data['email'],
                    'decision' => $defaultResponse['decision'],
                    'attack_type' => $defaultResponse['attack_type'],
                    'risk_score' => $defaultResponse['risk_score'],
                    'confidence' => $defaultResponse['confidence'],
                    'recommended_action' => $defaultResponse['recommended_action'],
                ]);
                return $defaultResponse;
            }

            // Ensure all required fields exist
            $mcp = array_merge($defaultResponse, $mcp);

            Log::info('[MCP Security] Analysis result', [
                'mcp' => $mcp
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
            ]);

            return $mcp;

        } catch (\Exception $e) {
            Log::error('[MCP Security] Error analyzing login', [
                'error' => $e->getMessage(),
                'ip' => $data['ip'],
                'email' => $data['email']
            ]);
            // Still log on error
            Log::channel('mcp_security')->info('MCP_DECISION', [
                'ip' => $data['ip'],
                'email' => $data['email'],
                'decision' => $defaultResponse['decision'],
                'attack_type' => $defaultResponse['attack_type'],
                'risk_score' => $defaultResponse['risk_score'],
                'confidence' => $defaultResponse['confidence'],
                'recommended_action' => $defaultResponse['recommended_action'],
            ]);
            // fail-open (don't block legit users if AI fails)
            return $defaultResponse;
        }
    }

    /**
     * Get AI risk score for login attempt (0-100) - rule-based fallback
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

        if ($ipAttempts >= 10) {
            $baseScore += 50;
        } elseif ($ipAttempts >= 5) {
            $baseScore += 30;
        } elseif ($ipAttempts >= 3) {
            $baseScore += 15;
        }

        if ($emailAttempts >= 5) {
            $baseScore += 40;
        } elseif ($emailAttempts >= 3) {
            $baseScore += 25;
        }

        return min(100, $baseScore);
    }
}
