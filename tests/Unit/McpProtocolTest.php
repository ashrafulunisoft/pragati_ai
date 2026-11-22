<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Support\Facades\Http;
use App\Mcp\McpClient;
use App\Services\McpSecurityService;

/**
 * Tests for the real MCP layer (Problem 3 fix):
 *  - /api/mcp speaks JSON-RPC 2.0 (initialize, tools/list, tools/call, errors, 401)
 *  - McpClient performs the handshake and returns structured content
 *  - McpSecurityService can run through the MCP driver end-to-end (faked transport)
 */
class McpProtocolTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['services.minimax.mcp_server_token' => 'test-mcp-token']);
    }

    private function rpc(array $payload, ?string $token = 'test-mcp-token'): \Symfony\Component\HttpFoundation\Response
    {
        $server = $this->app->make(\Illuminate\Contracts\Http\Kernel::class);

        $request = \Illuminate\Http\Request::create(
            '/api/mcp',
            'POST',
            [],
            [],
            [],
            [
                'CONTENT_TYPE'          => 'application/json',
                'HTTP_ACCEPT'           => 'application/json, text/event-stream',
                'HTTP_AUTHORIZATION'    => $token ? "Bearer {$token}" : '',
            ],
            json_encode($payload)
        );

        return $this->app->handle($request);
    }

    /*
     * Server-side protocol tests
     */

    public function test_server_rejects_missing_auth(): void
    {
        $res = $this->rpc(['jsonrpc' => '2.0', 'id' => 1, 'method' => 'initialize'], token: null);
        $this->assertSame(401, $res->getStatusCode());
    }

    public function test_server_rejects_wrong_token(): void
    {
        $res = $this->rpc(['jsonrpc' => '2.0', 'id' => 1, 'method' => 'initialize'], token: 'wrong');
        $this->assertSame(401, $res->getStatusCode());
    }

    public function test_initialize_handshake(): void
    {
        $res = $this->rpc([
            'jsonrpc' => '2.0',
            'id'      => 1,
            'method'  => 'initialize',
            'params'  => ['protocolVersion' => '2025-06-18', 'capabilities' => new \stdClass(), 'clientInfo' => ['name' => 't', 'version' => '0']],
        ]);

        $this->assertSame(200, $res->getStatusCode());
        $body = json_decode($res->getContent(), true);

        $this->assertSame('2.0', $body['jsonrpc']);
        $this->assertSame(1, $body['id']);
        $this->assertSame('2025-06-18', $body['result']['protocolVersion']);
        $this->assertArrayHasKey('tools', $body['result']['capabilities']);
        $this->assertSame('pragati-security-mcp-server', $body['result']['serverInfo']['name']);
        // Session header issued per Streamable HTTP transport
        $this->assertNotEmpty($res->headers->get('Mcp-Session-Id'));
    }

    public function test_notification_gets_202_without_body(): void
    {
        $res = $this->rpc(['jsonrpc' => '2.0', 'method' => 'notifications/initialized']);
        $this->assertSame(202, $res->getStatusCode());
    }

    public function test_unknown_method_returns_jsonrpc_error(): void
    {
        $res = $this->rpc(['jsonrpc' => '2.0', 'id' => 7, 'method' => 'resources/list']);
        $body = json_decode($res->getContent(), true);
        $this->assertSame(-32601, $body['error']['code']);
        $this->assertSame(7, $body['id']);
    }

    public function test_invalid_envelope_rejected(): void
    {
        $res = $this->rpc(['method' => 'initialize']);
        $body = json_decode($res->getContent(), true);
        $this->assertSame(-32600, $body['error']['code']);
    }

    public function test_tools_list_advertises_analyze_login_risk(): void
    {
        $res = $this->rpc(['jsonrpc' => '2.0', 'id' => 2, 'method' => 'tools/list']);
        $body = json_decode($res->getContent(), true);

        $tools = $body['result']['tools'];
        $this->assertCount(1, $tools);
        $this->assertSame('analyze_login_risk', $tools[0]['name']);
        $this->assertArrayHasKey('inputSchema', $tools[0]);
        $this->assertContains('ip_attempts', $tools[0]['inputSchema']['required']);
    }

    public function test_tools_call_returns_structured_content(): void
    {
        // Fake the LLM the tool will call
        Http::fake([
            '*/chat/completions' => Http::response([
                'choices' => [['message' => ['content' => json_encode([
                    'decision' => 'MALICIOUS', 'attack_type' => 'brute_force',
                    'risk_score' => 85, 'confidence' => 0.9, 'recommended_action' => 'block_ip',
                ])]]],
            ], 200),
        ]);

        $res = $this->rpc([
            'jsonrpc' => '2.0', 'id' => 3, 'method' => 'tools/call',
            'params'  => ['name' => 'analyze_login_risk', 'arguments' => [
                'ip' => '10.0.0.1', 'email' => 'a@b.com', 'ip_attempts' => 10, 'email_attempts' => 5,
            ]],
        ]);

        $body = json_decode($res->getContent(), true);
        $result = $body['result'];

        $this->assertFalse($result['isError']);
        $this->assertSame('MALICIOUS', $result['structuredContent']['decision']);
        $this->assertSame(85, $result['structuredContent']['risk_score']);
        $this->assertSame('text', $result['content'][0]['type']); // spec content fallback
    }

    public function test_tools_call_unknown_tool_error(): void
    {
        $res = $this->rpc([
            'jsonrpc' => '2.0', 'id' => 4, 'method' => 'tools/call',
            'params'  => ['name' => 'does_not_exist', 'arguments' => []],
        ]);
        $body = json_decode($res->getContent(), true);
        $this->assertSame(-32602, $body['error']['code']);
    }

    /*
     * Client-side tests
     */

    public function test_client_handshake_and_call_tool(): void
    {
        Http::fakeSequence()
            ->push([ // initialize response
                'jsonrpc' => '2.0', 'id' => 1,
                'result'  => ['protocolVersion' => '2025-06-18', 'capabilities' => new \stdClass(), 'serverInfo' => ['name' => 's', 'version' => '1']],
            ], 200, ['Mcp-Session-Id' => 'sess-123'])
            ->push('', 202) // notifications/initialized
            ->push([ // tools/call response
                'jsonrpc' => '2.0', 'id' => 2,
                'result'  => [
                    'structuredContent' => ['decision' => 'SAFE', 'confidence' => 0.9],
                    'content'           => [['type' => 'text', 'text' => '{}']],
                ],
            ], 200);

        config(['services.minimax.mcp_server_url' => 'http://mcp.local/api/mcp']);

        $verdict = McpClient::make()->callTool('analyze_login_risk', [
            'ip' => '1.2.3.4', 'email' => 'x@y.z', 'ip_attempts' => 5, 'email_attempts' => 0,
        ]);

        $this->assertSame('SAFE', $verdict['decision']);

        // First request must be a proper initialize with the protocol version
        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/api/mcp')
                && $request['method'] === 'initialize'
                && $request['params']['protocolVersion'] === '2025-06-18'
                && $request->hasHeader('Authorization', 'Bearer test-mcp-token');
        });
        // tools/call must carry the session id issued at initialize
        Http::assertSent(function ($request) {
            return ($request['method'] ?? '') === 'tools/call'
                && $request->hasHeader('Mcp-Session-Id', 'sess-123');
        });
    }

    /*
     * End-to-end: security service through the MCP driver
     */

    public function test_security_service_uses_mcp_driver(): void
    {
        config([
            'services.minimax.driver' => 'mcp',
            'services.minimax.mcp_server_url' => 'http://mcp.local/api/mcp',
            'services.minimax.mcp_handshake' => false, // skip handshake for a focused test
        ]);

        Http::fake([
            'mcp.local/*' => Http::response([
                'jsonrpc' => '2.0', 'id' => 1,
                'result'  => [
                    'structuredContent' => [
                        'decision' => 'MALICIOUS', 'attack_type' => 'bot_attack',
                        'risk_score' => 60, 'confidence' => 0.8, 'recommended_action' => 'block_ip',
                    ],
                    'content' => [['type' => 'text', 'text' => '{}']],
                ],
            ], 200),
        ]);

        $result = McpSecurityService::analyzeLogin([
            'ip' => '10.55.0.1', 'email' => 'mcp@test.com',
            'ip_attempts' => 5, 'email_attempts' => 3, // rule 55
        ]);

        // MCP driver delivered the verdict; fusion must honour AI escalation (P1)
        $this->assertTrue($result['ai_available']);
        $this->assertSame('MALICIOUS', $result['decision']);
        $this->assertSame('F2_ai_escalation', $result['fusion']);

        Http::assertSent(fn ($request) => str_contains($request->url(), 'mcp.local')
            && ($request['method'] ?? '') === 'tools/call');
    }

    public function test_security_service_mcp_outage_degrades_gracefully(): void
    {
        config([
            'services.minimax.driver' => 'mcp',
            'services.minimax.mcp_server_url' => 'http://mcp.local/api/mcp',
        ]);

        Http::fake(fn () => throw new \Exception('mcp server down'));

        $result = McpSecurityService::analyzeLogin([
            'ip' => '10.55.0.2', 'email' => 'mcp2@test.com',
            'ip_attempts' => 10, 'email_attempts' => 5, // rule 90
        ]);

        // P2 tiered fallback still applies over the MCP driver
        $this->assertFalse($result['ai_available']);
        $this->assertSame('fail_closed', $result['degraded_mode']);
        $this->assertSame('MALICIOUS', $result['decision']);
    }
}
