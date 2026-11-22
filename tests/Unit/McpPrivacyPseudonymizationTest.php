<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Support\Facades\Http;
use App\Services\McpSecurityService;
use App\Services\PrivacyService;

/**
 * Tests for the outbound privacy boundary (Problem 6 fix):
 * raw IP/email must never leave the server towards the LLM or the MCP tool.
 */
class McpPrivacyPseudonymizationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.minimax.pseudonymize' => true,
            'services.minimax.privacy_salt' => 'unit-test-salt',
            'services.minimax.driver' => 'direct',
        ]);
    }

    public function test_email_is_hmac_pseudonymised(): void
    {
        $p = PrivacyService::email('victim@corp.com');
        $this->assertStringStartsWith('email_id:', $p);
        $this->assertStringNotContainsString('victim@corp.com', $p);
        $this->assertSame(16, strlen(substr($p, strlen('email_id:'))));
    }

    public function test_ip_is_prefix_plus_hash(): void
    {
        $p = PrivacyService::ip('203.0.113.50');
        $this->assertStringStartsWith('ip_net:203.0.113~', $p);   // /24 locality kept
        $this->assertStringNotContainsString('203.0.113.50', $p); // full address never leaves

        $v6 = PrivacyService::ip('2001:db8:1234:5678::1');
        $this->assertStringStartsWith('ip_net:20010db812345678~', $v6); // /64 prefix
    }

    public function test_pseudonyms_are_stable_for_repeat_identifiers(): void
    {
        // the LLM must still recognise "same identifier as before"
        $this->assertSame(PrivacyService::email('a@corp.com'), PrivacyService::email('a@corp.com'));
        $this->assertSame(PrivacyService::ip('203.0.113.50'), PrivacyService::ip('203.0.113.50'));
        // ...and different identifiers do not collide
        $this->assertNotSame(PrivacyService::email('a@corp.com'), PrivacyService::email('b@corp.com'));
    }

    public function test_pseudonymization_can_be_disabled_for_local_experiments(): void
    {
        config(['services.minimax.pseudonymize' => false]);
        $this->assertSame('victim@corp.com', PrivacyService::email('victim@corp.com'));
        $this->assertSame('203.0.113.50', PrivacyService::ip('203.0.113.50'));
    }

    /**
     * THE core assertion: the outgoing HTTP body (direct driver) contains
     * neither the raw email nor the raw IP.
     */
    public function test_direct_driver_request_carries_no_pii(): void
    {
        Http::fake([
            '*' => Http::response(['choices' => [['message' => ['content' => json_encode([
                'decision' => 'SAFE', 'confidence' => 0.5,
            ])]]]], 200),
        ]);

        McpSecurityService::analyzeLogin([
            'ip' => '203.0.113.50',
            'email' => 'victim@corp.com',
            'ip_attempts' => 5,
            'email_attempts' => 3,
        ]);

        Http::assertSent(function ($request) {
            $body = json_encode($request->data());

            return str_contains($request->url(), 'chat/completions')
                && !str_contains($body, 'victim@corp.com')
                && !str_contains($body, '203.0.113.50')
                && str_contains($body, 'email_id:')
                && str_contains($body, 'ip_net:203.0.113~')
                && str_contains($body, 'ip_attempts: 5')
                && str_contains($body, 'email_attempts: 3');
        });
    }

    /**
     * Same guarantee through the MCP driver: tool arguments carry no PII.
     */
    public function test_mcp_driver_tool_arguments_carry_no_pii(): void
    {
        config([
            'services.minimax.driver' => 'mcp',
            'services.minimax.mcp_server_url' => 'http://mcp.local/api/mcp',
            'services.minimax.mcp_handshake' => false,
        ]);

        Http::fake([
            'mcp.local/*' => Http::response([
                'jsonrpc' => '2.0', 'id' => 1,
                'result'  => [
                    'structuredContent' => ['decision' => 'SAFE', 'confidence' => 0.5],
                    'content'           => [['type' => 'text', 'text' => '{}']],
                ],
            ], 200),
        ]);

        $result = McpSecurityService::analyzeLogin([
            'ip' => '198.51.100.7',
            'email' => 'target@corp.com',
            'ip_attempts' => 5,
            'email_attempts' => 3,
        ]);

        $this->assertTrue($result['ai_available']);

        Http::assertSent(function ($request) {
            $body = json_encode($request->data());

            return str_contains($request->url(), 'mcp.local')
                && ($request['method'] ?? '') === 'tools/call'
                && !str_contains($body, 'target@corp.com')
                && !str_contains($body, '198.51.100.7')
                && str_contains($body, 'email_id:')
                && str_contains($body, 'ip_net:198.51.100~');
        });
    }

    /**
     * Pseudonymisation must not change detection semantics: the counters are
     * what the rules and fusion consume.
     */
    public function test_detection_unaffected_by_pseudonymization(): void
    {
        Http::fake([
            '*' => Http::response(['choices' => [['message' => ['content' => json_encode([
                'decision' => 'MALICIOUS', 'attack_type' => 'brute_force',
                'risk_score' => 60, 'confidence' => 0.9,
            ])]]]], 200),
        ]);

        $result = McpSecurityService::analyzeLogin([
            'ip' => '203.0.113.50', 'email' => 'victim@corp.com',
            'ip_attempts' => 5, 'email_attempts' => 3, // rule 55
        ]);

        $this->assertSame('MALICIOUS', $result['decision']); // F2 escalation intact
        $this->assertSame(60, $result['risk_score']);
    }
}
