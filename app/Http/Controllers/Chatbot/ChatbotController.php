<?php

namespace App\Http\Controllers\Chatbot;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ChatbotController extends Controller
{
    public function index()
    {
        return view('chatbot.ai-chatbot');
    }

    public function chat(Request $request)
    {
        $message = $request->message;
        
        // Call MiniMax API directly
        $reply = $this->callMiniMax($message);
        
        return response()->json(['reply' => $reply]);
    }

    private function callMiniMax($message)
    {
        $apiKey = config('services.minimax.api_key');
        $host = config('services.minimax.host', 'https://api.minimax.io');
        $model = config('services.minimax.model', 'MiniMax-M2.1');
        
        if (empty($apiKey)) {
            return 'MiniMax API key not configured. Please set MINIMAX_API_KEY in .env file.';
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(60)->post($host . '/v1/chat/completions', [
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => 'You are a helpful insurance assistant. Answer in the language the user writes.'],
                    ['role' => 'user', 'content' => $message]
                ],
                'temperature' => 0.7,
                'max_tokens' => 2000,
            ]);

            $statusCode = $response->status();
            
            if ($statusCode === 200) {
                $data = $response->json();
                if (isset($data['choices']) && count($data['choices']) > 0) {
                    $content = $data['choices'][0]['message']['content'];
                    return $this->cleanResponse($content);
                }
                return 'Sorry, I did not understand. Please try again.';
            }
            
            $errorData = $response->json();
            if (isset($errorData['error']['message'])) {
                return 'Error: ' . $errorData['error']['message'];
            }
            return 'Error: Failed to get response from MiniMax. Status: ' . $statusCode;
            
        } catch (\Exception $e) {
            return 'Error: ' . $e->getMessage();
        }
    }

    /**
     * Clean thinking tags from response
     */
    private function cleanResponse($content)
    {
        // Remove <think>...</think> tags
        $content = preg_replace('/<think>.*?<\/think>/s', '', $content);
        
        // Remove [THINKING]...[/THINKING] tags
        $content = preg_replace('/\[THINKING\].*?\[\/THINKING\]/s', '', $content);
        
        // Remove any thinking blocks
        $content = preg_replace('/Thinking:.*?(\n\n|$)/s', '', $content);
        
        // Remove analysis tags
        $content = preg_replace('/<analysis>.*?<\/analysis>/s', '', $content);
        
        // Remove multiple blank lines
        $content = preg_replace('/\n{3,}/s', "\n\n", $content);
        
        return trim($content);
    }
}
