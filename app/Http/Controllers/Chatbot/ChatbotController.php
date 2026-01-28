<?php

namespace App\Http\Controllers\Chatbot;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        $user = Auth::user();

        // Build user context
        if ($user) {
            $userContext = "
User is LOGGED IN.
User ID: {$user->id}
Name: {$user->name}
Email: {$user->email}

If user asks:
- 'Am I logged in?' → say YES
- 'What is my user id?' → show user id
- 'My policy / order / claim?' → say you can check and ask permission
";
        } else {
            $userContext = "
User is NOT logged in.

If user asks about:
- policy
- order
- claim
- personal data

Tell them politely to login first.
";
        }

        $reply = $this->callMiniMax($message, $userContext);
        return response()->json(['reply' => $reply]);
    }

    private function callMiniMax($message, $userContext)
    {
        $apiKey = config('services.minimax.api_key');
        $host = config('services.minimax.host', 'https://api.minimax.io');
        $model = config('services.minimax.model', 'MiniMax-M2.1');
        
        if (empty($apiKey)) {
            return 'Error: MiniMax API key not configured.';
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(30)->post($host . '/v1/chat/completions', [
                'model' => $model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => "You are Pragati Life Insurance AI Assistant.

Rules:
- Answer in Bangla or English based on user language
- Be polite, professional, human-like
- Do NOT invent user data

USER CONTEXT:
" . $userContext
                    ],
                    [
                        'role' => 'user',
                        'content' => $message
                    ]
                ],
                'temperature' => 0.7,
                'max_tokens' => 2000
            ]);

            $status = $response->status();

            if ($status === 200) {
                $data = $response->json();
                if (isset($data['choices']) && count($data['choices']) > 0) {
                    $content = $data['choices'][0]['message']['content'];
                    return $this->cleanResponse($content);
                }
                return 'Error: Empty response from MiniMax.';
            }

            if ($status === 500 || $status === 520 || $status === 796) {
                return 'MiniMax server is temporarily unavailable. Please try again in a moment.';
            }

            return 'Error: MiniMax returned status ' . $status;
            
        } catch (\Exception $e) {
            return 'Error: ' . $e->getMessage();
        }
    }

    private function cleanResponse($content)
    {
        $content = str_replace('<think>', '', $content);
        $content = str_replace('</think>', '', $content);
        $content = str_replace('[THINKING]', '', $content);
        $content = str_replace('[/THINKING]', '', $content);
        $content = preg_replace('/Thinking:.*?(\n\n|$)/s', '', $content);
        $content = str_replace('<analysis>', '', $content);
        $content = str_replace('</analysis>', '', $content);
        $content = preg_replace('/\n{3,}/s', "\n\n", $content);
        return trim($content);
    }
}
