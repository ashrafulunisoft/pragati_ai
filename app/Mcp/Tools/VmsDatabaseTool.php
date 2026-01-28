<?php

namespace App\Mcp\Tools;

use Laravel\Mcp\Tools\Tool;
use App\Services\AiQueryService;

class VmsDatabaseTool extends Tool
{
    protected string $name = 'query_database';
    protected string $description = 'Query the database with natural language questions. Works for any database-related question.';

    protected array $parameters = [
        'question' => [
            'type' => 'string',
            'description' => 'The question to ask about the database (e.g., "How many visitors today?", "Show me pending visits")',
            'required' => true,
        ],
        'use_external_db' => [
            'type' => 'boolean',
            'description' => 'Whether to use external database (default: false)',
            'required' => false,
            'default' => false,
        ],
    ];

    public function execute(array $parameters): array
    {
        $question = $parameters['question'];
        $useExternalDb = $parameters['use_external_db'] ?? false;

        try {
            AiQueryService::useExternalDatabase($useExternalDb);
            $result = AiQueryService::answerFromDatabase($question);

            return $this->formatResponse($result, $question);
        } catch (\Exception $e) {
            return $this->error("Database query failed: " . $e->getMessage());
        }
    }

    private function formatResponse(array $result, string $question): array
    {
        $answer = $result['answer'] ?? 'No results found';
        $data = $result['data'] ?? [];
        $action = $result['action'] ?? 'query';
        $confidence = $result['confidence'] ?? 1.0;

        return $this->text("📊 **Database Query Result**\n\n**Question:** {$question}\n\n**Answer:** {$answer}\n\n**Confidence:** " . ($confidence * 100) . "%");
    }
}
