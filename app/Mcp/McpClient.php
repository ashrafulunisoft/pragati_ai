<?php

namespace App\Mcp;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * MCP client (Problem 3 fix) - Streamable HTTP transport, JSON-RPC 2.0,
 * protocol version 2025-06-18.
 *
 * Usage:
 *   $verdict = McpClient::make()->callTool('analyze_login_risk', $context);
 *
 * The client performs the initialize handshake (reusing the issued session
 * id), then calls tools/call and returns the tool's structured content.
 * Transport failures throw - callers apply their degraded policy.
 */
class McpClient
{
    private ?string $sessionId = null;

    public static function make(): self
    {
        return new self();
    }

    /**
     * JSON-RPC initialize handshake with the MCP server.
     */
    public function initialize(): array
    {
        $response = $this->post([
            'jsonrpc' => '2.0',
            'id'      => $this->nextId(),
            'method'  => 'initialize',
            'params'  => [
                'protocolVersion' => $this->protocolVersion(),
                'capabilities'    => new \stdClass(),
                'clientInfo'      => ['name' => 'pragati-security-mcp-client', 'version' => '1.0.0'],
            ],
        ]);

        // Session id issued by the server (optional per spec)
        $session = $response->header('Mcp-Session-Id');
        if ($session) {
            $this->sessionId = $session;
        }

        // notifications/initialized - notification, 202 expected, no body
        $this->post([
            'jsonrpc' => '2.0',
            'method'  => 'notifications/initialized',
        ]);

        return $response->json('result', []);
    }

    /**
     * Call a tool and return its structured content (array).
     */
    public function callTool(string $name, array $arguments): array
    {
        if ($this->sessionId === null && config('services.minimax.mcp_handshake', true)) {
            $this->initialize();
        }

        $response = $this->post([
            'jsonrpc' => '2.0',
            'id'      => $this->nextId(),
            'method'  => 'tools/call',
            'params'  => ['name' => $name, 'arguments' => $arguments],
        ]);

        if ($response->status() !== 200) {
            throw new \RuntimeException("MCP server HTTP {$response->status()}");
        }

        $body = $response->json();

        if (isset($body['error'])) {
            throw new \RuntimeException('MCP error: '.($body['error']['message'] ?? 'unknown'));
        }

        $result  = $body['result'] ?? [];
        $isError = (bool) ($result['isError'] ?? false);

        if ($isError) {
            throw new \RuntimeException('MCP tool error: '.json_encode($result['structuredContent'] ?? []));
        }

        // Prefer structuredContent (spec 2025-06-18), fall back to text content
        if (isset($result['structuredContent']) && is_array($result['structuredContent'])) {
            return $result['structuredContent'];
        }

        $text = $result['content'][0]['text'] ?? null;

        if (is_string($text)) {
            $decoded = json_decode($text, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        throw new \RuntimeException('MCP tool returned no usable content');
    }

    private function post(array $payload): \Illuminate\Http\Client\Response
    {
        $headers = [
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json, text/event-stream',
        ];

        $token = (string) config('services.minimax.mcp_server_token', '');
        if ($token !== '') {
            $headers['Authorization'] = 'Bearer '.$token;
        }

        if ($this->sessionId) {
            $headers['Mcp-Session-Id'] = $this->sessionId;
        }

        return Http::withHeaders($headers)
            ->timeout((int) config('services.minimax.timeout', 10))
            ->retry((int) config('services.minimax.retries', 1), 200, throw: false)
            ->post($this->serverUrl(), $payload);
    }

    private function serverUrl(): string
    {
        return rtrim((string) config('services.minimax.mcp_server_url', url('/api/mcp')), '/');
    }

    private function protocolVersion(): string
    {
        return (string) config('services.minimax.mcp_protocol_version', '2025-06-18');
    }

    private function nextId(): int
    {
        static $id = 0;

        return ++$id;
    }
}
