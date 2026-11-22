<?php

namespace App\Http\Controllers\Mcp;

use App\Http\Controllers\Controller;
use App\Mcp\Tools\AnalyzeLoginRiskTool;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * MCP server endpoint (Problem 3 fix).
 *
 * Implements an MCP server over the Streamable HTTP transport
 * (protocol version 2025-06-18): clients POST JSON-RPC 2.0 messages to
 * POST /api/mcp and receive JSON responses.
 *
 * Supported methods:
 *   initialize               -> capabilities + serverInfo handshake
 *   notifications/initialized-> 202 Accepted (notification, no body)
 *   tools/list               -> advertised tools (analyze_login_risk)
 *   tools/call               -> execute a tool
 *
 * Authentication: Bearer token (MCP_SERVER_TOKEN) - the endpoint rejects
 * unauthenticated clients with HTTP 401 before any JSON-RPC processing.
 */
class McpServerController extends Controller
{
    /**
     * Tools served by this MCP server.
     */
    private const TOOLS = [AnalyzeLoginRiskTool::class];

    private const PROTOCOL_VERSION = '2025-06-18';

    public function __invoke(Request $request): JsonResponse
    {
        // --- Transport auth -------------------------------------------------
        $expected = (string) config('services.minimax.mcp_server_token', '');
        if ($expected !== '' && $request->bearerToken() !== $expected) {
            return response()->json(['error' => 'unauthorized'], 401);
        }

        // --- JSON-RPC envelope ----------------------------------------------
        $rpc = $request->json()->all();

        if (!is_array($rpc) || !isset($rpc['jsonrpc']) || !isset($rpc['method'])) {
            return $this->error($rpc['id'] ?? null, -32600, 'Invalid Request');
        }

        $id     = $rpc['id'] ?? null;
        $method = (string) $rpc['method'];
        $params = is_array($rpc['params'] ?? null) ? $rpc['params'] : [];

        // Notifications carry no id and get no response body
        if ($id === null && str_starts_with($method, 'notifications/')) {
            return response()->json('', 202);
        }

        switch ($method) {
            case 'initialize':
                return $this->initialize($request);

            case 'tools/list':
                return $this->toolsList();

            case 'tools/call':
                return $this->toolsCall($params);

            case 'ping':
                return response()->json(['jsonrpc' => '2.0', 'id' => $id, 'result' => new \stdClass()]);

            default:
                return $this->error($id, -32601, "Method not found: {$method}");
        }
    }

    private function initialize(Request $request): JsonResponse
    {
        $requested = (string) ($request->json('params.protocolVersion', self::PROTOCOL_VERSION));

        return response()->json([
            'jsonrpc' => '2.0',
            'id'      => $request->json('id'),
            'result'  => [
                'protocolVersion' => self::PROTOCOL_VERSION,
                'capabilities'    => [
                    'tools' => ['listChanged' => false],
                ],
                'serverInfo' => [
                    'name'    => 'pragati-security-mcp-server',
                    'version' => '1.0.0',
                ],
            ],
        ], 200, ['Mcp-Session-Id' => (string) \Illuminate\Support\Str::uuid()]);
    }

    private function toolsList(): JsonResponse
    {
        $tools = array_map(function (string $tool) {
            return [
                'name'        => $tool::name(),
                'description' => $tool::description(),
                'inputSchema' => $tool::inputSchema(),
            ];
        }, self::TOOLS);

        return response()->json([
            'jsonrpc' => '2.0',
            'id'      => request()->json('id'),
            'result'  => ['tools' => $tools],
        ]);
    }

    private function toolsCall(array $params): JsonResponse
    {
        $name = (string) ($params['name'] ?? '');
        $args = is_array($params['arguments'] ?? null) ? $params['arguments'] : [];

        foreach (self::TOOLS as $tool) {
            if ($tool::name() === $name) {
                $result = $tool::handle($args);

                $isError = isset($result['error']);

                return response()->json([
                    'jsonrpc' => '2.0',
                    'id'      => request()->json('id'),
                    'result'  => [
                        // MCP structured content (spec 2025-06-18) + text fallback
                        'structuredContent' => $result,
                        'content'           => [[
                            'type' => 'text',
                            'text' => json_encode($result),
                        ]],
                        'isError' => $isError,
                    ],
                ]);
            }
        }

        return $this->error(request()->json('id'), -32602, "Unknown tool: {$name}");
    }

    private function error(mixed $id, int $code, string $message): JsonResponse
    {
        return response()->json([
            'jsonrpc' => '2.0',
            'id'      => $id,
            'error'   => ['code' => $code, 'message' => $message],
        ]);
    }
}
