<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Mcp\Tools\AskAiTool;

class McpServeCommand extends Command
{
    protected $signature = 'mcp:serve';
    protected $description = 'Start the MCP server for AI assistant integration';

    private array $tools = [];

    public function handle(): int
    {
        $this->registerTools();
        $this->runServer();
        return 0;
    }

    private function registerTools(): void
    {
        $this->tools = [
            'ask_ai' => [
                'description' => 'Ask any question about the database in natural language. The AI will interpret your question and query the database.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'question' => ['type' => 'string', 'description' => 'Your natural language question about visitors, visits, or statistics'],
                    ],
                    'required' => ['question'],
                ],
            ],
            'chat_ai' => [
                'description' => 'Chat directly with the AI model. Send any text prompt and get a response without database interaction.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'prompt' => ['type' => 'string', 'description' => 'Your text prompt or question for the AI'],
                    ],
                    'required' => ['prompt'],
                ],
            ],
        ];
    }

    private function runServer(): void
    {
        $stdin = fopen('php://stdin', 'r');
        stream_set_blocking($stdin, false);

        while (true) {
            $line = fgets($stdin);
            if ($line === false) {
                usleep(10000);
                continue;
            }

            $line = trim($line);
            if (empty($line)) continue;

            $request = json_decode($line, true);
            if (!$request) continue;

            $response = $this->handleRequest($request);
            $this->sendResponse($response);
        }
    }

    private function handleRequest(array $request): array
    {
        $method = $request['method'] ?? '';
        $id = $request['id'] ?? null;

        return match ($method) {
            'initialize' => $this->handleInitialize($id),
            'initialized' => ['jsonrpc' => '2.0', 'id' => $id, 'result' => null],
            'tools/list' => $this->handleToolsList($id),
            'tools/call' => $this->handleToolCall($id, $request['params'] ?? []),
            'ping' => ['jsonrpc' => '2.0', 'id' => $id, 'result' => []],
            default => [
                'jsonrpc' => '2.0',
                'id' => $id,
                'error' => ['code' => -32601, 'message' => 'Method not found: ' . $method],
            ],
        };
    }

    private function handleInitialize(mixed $id): array
    {
        return [
            'jsonrpc' => '2.0',
            'id' => $id,
            'result' => [
                'protocolVersion' => '2024-11-05',
                'capabilities' => [
                    'tools' => ['listChanged' => true],
                ],
                'serverInfo' => [
                    'name' => config('mcp.name', 'Pragati AI VMS'),
                    'version' => config('mcp.version', '1.0.0'),
                ],
            ],
        ];
    }

    private function handleToolsList(mixed $id): array
    {
        $tools = [];
        foreach ($this->tools as $name => $config) {
            $tools[] = [
                'name' => $name,
                'description' => $config['description'],
                'inputSchema' => $config['inputSchema'],
            ];
        }

        return [
            'jsonrpc' => '2.0',
            'id' => $id,
            'result' => ['tools' => $tools],
        ];
    }

    private function handleToolCall(mixed $id, array $params): array
    {
        $toolName = $params['name'] ?? '';
        $arguments = $params['arguments'] ?? [];

        $askAiTool = app(AskAiTool::class);

        try {
            $result = match ($toolName) {
                'ask_ai' => $askAiTool->askAi($arguments['question']),
                'chat_ai' => $askAiTool->chatAi($arguments['prompt']),
                default => throw new \Exception("Unknown tool: {$toolName}"),
            };

            return [
                'jsonrpc' => '2.0',
                'id' => $id,
                'result' => [
                    'content' => [
                        ['type' => 'text', 'text' => json_encode($result, JSON_PRETTY_PRINT)],
                    ],
                ],
            ];
        } catch (\Exception $e) {
            return [
                'jsonrpc' => '2.0',
                'id' => $id,
                'result' => [
                    'content' => [
                        ['type' => 'text', 'text' => 'Error: ' . $e->getMessage()],
                    ],
                    'isError' => true,
                ],
            ];
        }
    }

    private function sendResponse(array $response): void
    {
        echo json_encode($response) . "\n";
        flush();
    }
}
