<?php

namespace App\Mcp\Tools;

use App\Mcp\MiniMaxClient;
use App\Mcp\SecurityPrompt;

/**
 * MCP tool: analyze_login_risk
 *
 * Exposed over the MCP server endpoint (POST /api/mcp, JSON-RPC 2.0).
 * The tool receives the (already pseudonymised - see Problem 6) login
 * context, builds the prompt and calls the LLM. This is what makes the
 * "MCP" claim real: the security layer talks to a protocol-conformant
 * tool server instead of hard-wiring an LLM API call.
 */
class AnalyzeLoginRiskTool
{
    public const NAME = 'analyze_login_risk';

    public static function name(): string
    {
        return self::NAME;
    }

    public static function description(): string
    {
        return 'Analyzes login behaviour context (IP, email identifier, attempt counts) '
            .'and returns a JSON verdict: decision, attack_type, risk_score, confidence, recommended_action.';
    }

    /**
     * MCP input schema (JSON Schema draft) advertised via tools/list.
     */
    public static function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'ip' => ['type' => 'string', 'description' => 'Client IP address (or pseudonymised prefix)'],
                'email' => ['type' => 'string', 'description' => 'Account identifier (or pseudonymised hash)'],
                'ip_attempts' => ['type' => 'integer', 'minimum' => 0],
                'email_attempts' => ['type' => 'integer', 'minimum' => 0],
            ],
            'required' => ['ip', 'email', 'ip_attempts', 'email_attempts'],
        ];
    }

    /**
     * Execute the tool. Returns the parsed verdict array from the LLM,
     * or ['error' => ...] when the model output could not be parsed.
     */
    public static function handle(array $arguments): array
    {
        $context = [
            'ip'            => (string) ($arguments['ip'] ?? ''),
            'email'         => (string) ($arguments['email'] ?? ''),
            'ip_attempts'   => (int) ($arguments['ip_attempts'] ?? 0),
            'email_attempts' => (int) ($arguments['email_attempts'] ?? 0),
        ];

        $content = MiniMaxClient::chat(
            SecurityPrompt::system(),
            SecurityPrompt::user($context)
        );

        $verdict = json_decode(trim($content), true);

        if (!is_array($verdict)) {
            return ['error' => 'unparseable_model_output', 'raw' => mb_substr($content, 0, 500)];
        }

        return $verdict;
    }
}
