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
     * @return bool True if malicious, false if safe
     */
    public static function analyzeLogin(array $data): bool
    {
        $apiKey = config('services.minimax.api_key');
        $host   = config('services.minimax.host', 'https://api.minimax.io');
        $model  = config('services.minimax.model', 'MiniMax-M2.1');

        $prompt = "You are a cybersecurity AI. Analyze this login behavior and respond ONLY with: MALICIOUS or SAFE. Data: IP: {$data['ip']}, Email: {$data['email']}, IP Attempts: {$data['ip_attempts']}, Email Attempts: {$data['email_attempts']}, Time Window: 15 minutes.";

        Log::info('[MCP Security] Analyzing login', [
            'ip' => $data['ip'],
            'email' => $data['email'],
            'ip_attempts' => $data['ip_attempts'],
            'email_attempts' => $data['email_attempts']
        ]);

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$apiKey,
                'Content-Type'  => 'application/json',
            ])->post($host.'/v1/chat/completions', [
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => 'You are a security analysis engine.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0,
                'max_tokens' => 5
            ]);

            $content = 'SAFE';
            $responseData = json_decode($response->getBody()->getContents(), true);
            if (isset($responseData['choices']) && count($responseData['choices']) > 0) {
                $content = $responseData['choices'][0]['message']['content'] ?? 'SAFE';
            }

            $isMalicious = str_contains(strtoupper($content), 'MALICIOUS');

            Log::info('[MCP Security] Analysis result', [
                'response' => $content,
                'is_malicious' => $isMalicious
            ]);

            return $isMalicious;

        } catch (\Exception $e) {
            Log::error('[MCP Security] Error analyzing login', [
                'error' => $e->getMessage(),
                'ip' => $data['ip'],
                'email' => $data['email']
            ]);
            // fail-open (don't block legit users if AI fails)
            return false;
        }
    }

    /**
     * Get AI risk score for login attempt (0-100)
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
