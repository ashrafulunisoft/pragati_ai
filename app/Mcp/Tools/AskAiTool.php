<?php

namespace App\Mcp\Tools;

use App\Services\AiQueryService;
use Illuminate\Support\Facades\Http;

class AskAiTool
{
    /**
     * Ask any question about the database in natural language
     */
    public function askAi(string $question): array
    {
        return AiQueryService::answerFromDatabase($question);
    }

    /**
     * Chat directly with AI model without database interaction
     */
    public function chatAi(string $prompt): array
    {
        $provider = config('services.ai.provider', 'local');
        
        try {
            $response = match ($provider) {
                'minimax' => $this->callMiniMax($prompt),
                'openai' => $this->callOpenAI($prompt),
                'zhipu' => $this->callZhipu($prompt),
                default => ['response' => 'AI provider not configured. Set AI_PROVIDER in .env'],
            };
            
            return [
                'provider' => $provider,
                'prompt' => $prompt,
                'response' => $response,
            ];
        } catch (\Exception $e) {
            return [
                'provider' => $provider,
                'prompt' => $prompt,
                'error' => $e->getMessage(),
            ];
        }
    }

    private function callMiniMax(string $prompt): string
    {
        $apiKey = config('services.minimax.api_key');
        
        // Try OpenAI-compatible endpoint first
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
        ])->timeout(60)->post('https://api.minimax.io/v1/chat/completions', [
            'model' => config('services.minimax.model', 'MiniMax-Text-01'),
            'messages' => [
                ['role' => 'user', 'content' => $prompt]
            ],
            'temperature' => 0.7,
            'max_tokens' => 1024,
        ]);

        // Log for debugging
        \Log::info('MiniMax Response:', ['status' => $response->status(), 'body' => $response->json()]);

        if ($response->successful()) {
            $content = $response->json('choices.0.message.content');
            if ($content) {
                return $content;
            }
        }
        
        // Return error details for debugging
        return 'MiniMax Error: ' . ($response->json('error.message') ?? $response->body());
    }

    private function callOpenAI(string $prompt): string
    {
        $apiKey = config('services.openai.api_key');
        
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
        ])->timeout(60)->post('https://api.openai.com/v1/chat/completions', [
            'model' => config('services.openai.model', 'gpt-4o-mini'),
            'messages' => [
                ['role' => 'user', 'content' => $prompt]
            ],
            'temperature' => 0.7,
        ]);

        return $response->json('choices.0.message.content') ?? 'No response from OpenAI';
    }

    private function callZhipu(string $prompt): string
    {
        $apiKey = config('services.zhipu.api_key');
        
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
        ])->timeout(60)->post('https://open.bigmodel.cn/api/paas/v4/chat/completions', [
            'model' => config('services.zhipu.model', 'glm-4'),
            'messages' => [
                ['role' => 'user', 'content' => $prompt]
            ],
            'temperature' => 0.7,
        ]);

        return $response->json('choices.0.message.content') ?? 'No response from Zhipu';
    }
}
