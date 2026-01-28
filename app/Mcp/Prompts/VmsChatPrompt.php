<?php

namespace App\Mcp\Prompts;

use Laravel\Mcp\Prompt;
use Laravel\Mcp\Server\Concerns\HandlesPrompts;

class VmsChatPrompt extends Prompt
{
    use HandlesPrompts;

    public function handle(array $params): array
    {
        $question = $params['query'] ?? $params['message'] ?? '';
        $language = $params['language'] ?? 'auto';

        return $this->text($this->buildSystemPrompt($question, $language));
    }

    private function buildSystemPrompt(string $question, string $language): string
    {
        $langInstruction = $this->getLanguageInstruction($language);

        return <<<PROMPT
You are a helpful Visitor Management System (VMS) Assistant for UCBL University.

{$langInstruction}

Your capabilities:
1. Answer questions about visitors, visits, appointments, and schedules
2. Help with visitor registration and check-in/checkout processes
3. Provide statistics and reports about visits
4. Assist with staff/host information queries
5. Help with role and permission questions

Current user question: {$question}

When you need to get data from the database, use the appropriate tools:
- For visitor information: use get_visitor, search_visitors
- For visit information: use get_visit, search_visits
- For statistics: use get_dashboard_stats
- For database queries: use query_database for any custom questions
- For general AI chat: use ask_ai

Be polite, helpful, and provide accurate information based on the data available.

If you need to ask clarifying questions, do so politely.
PROMPT;
    }

    private function getLanguageInstruction(string $language): string
    {
        return match ($language) {
            'bn', 'bengali' => 'Respond primarily in Bengali (Bangla) with English translations when helpful.',
            'en', 'english' => 'Respond in English.',
            'both' => 'Respond in both Bengali and English when helpful.',
            default => 'Respond in the language the user uses. If the user writes in Bengali, respond in Bengali. If in English, respond in English.',
        };
    }
}
