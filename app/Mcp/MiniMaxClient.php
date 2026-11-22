<?php

namespace App\Mcp;

use Illuminate\Support\Facades\Http;

/**
 * Thin client for the MiniMax OpenAI-style chat-completions API.
 * This is the LOW-LEVEL LLM transport - it knows nothing about security
 * decisions. It is used directly by the "direct" driver and by the MCP
 * server tool (analyze_login_risk) for the "mcp" driver.
 */
class MiniMaxClient
{
    /**
     * Send a chat completion and return the raw message content.
     * Throws on transport failure (the caller decides the fallback).
     */
    public static function chat(string $system, string $user, int $maxTokens = 200): string
    {
        $apiKey     = config('services.minimax.api_key');
        $host       = config('services.minimax.host', 'https://api.minimax.io');
        $model      = config('services.minimax.model', 'MiniMax-M2.1');
        $retries    = (int) config('services.minimax.retries', 1);
        $totalTries = max(1, 1 + $retries);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$apiKey,
            'Content-Type'  => 'application/json',
        ])
        ->timeout((int) config('services.minimax.timeout', 10))
        ->retry($totalTries, 200, throw: false)
        ->post(self::endpoint($host), [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $user],
            ],
            'temperature' => 0,
            'max_tokens' => $maxTokens,
        ]);

        $body = $response->json();

        return $body['choices'][0]['message']['content'] ?? '';
    }

    /**
     * Chat-completions endpoint for the configured host (Comment 1 fix).
     *
     * MiniMax-style hosts expect "/v1/chat/completions", but OpenAI-compatible
     * hosts that already carry a version prefix — e.g. Gemini's
     * "https://generativelanguage.googleapis.com/v1beta/openai" — resolve
     * "/chat/completions" directly; appending another "/v1" 404s every call
     * and silently disables the live AI layer. MCP_CHAT_PATH overrides the
     * detection outright when set.
     */
    private static function endpoint(string $host): string
    {
        $override = trim((string) config('services.minimax.chat_path', ''));
        if ($override !== '') {
            return rtrim($host, '/').'/'.ltrim($override, '/');
        }

        $host = rtrim($host, '/');

        $versioned = preg_match('#/(v1|v1beta/openai|v1beta)(/|$)#', $host) === 1;

        return $host.($versioned ? '/chat/completions' : '/v1/chat/completions');
    }
}
