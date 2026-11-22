<?php

namespace Tests\Unit;

use App\Mcp\MiniMaxClient;
use App\Services\McpSecurityService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Live-validation support tests (journal reviewer Comment 1).
 *
 * 1. Chat-completions endpoint construction: OpenAI-compatible hosts that
 *    already carry a version prefix (Gemini ".../v1beta/openai") must NOT
 *    receive another "/v1" - the bug that silently 404'd every live call and
 *    left the system running in degraded (rules-only) mode.
 * 2. Live-run observability counters (ai_calls / ai_invalid / ai_errors) the
 *    evaluation harness reports as the unparseable-response and
 *    transport-error rates required by the revision.
 */
class McpLiveValidationTest extends TestCase
{
    protected function tearDown(): void
    {
        config([
            'services.minimax.host' => 'https://api.minimax.io',
            'services.minimax.chat_path' => null,
            'services.minimax.driver' => 'direct',
        ]);
        parent::tearDown();
    }

    public function test_gemini_openai_compatible_host_does_not_get_a_second_v1(): void
    {
        config(['services.minimax.host' => 'https://generativelanguage.googleapis.com/v1beta/openai']);
        Http::fake(['*' => Http::response(['choices' => [['message' => ['content' => '{"decision":"SAFE"}']]]])]);

        MiniMaxClient::chat('system', 'user');

        Http::assertSent(function ($request) {
            return str_ends_with($request->url(), '/v1beta/openai/chat/completions')
                && !str_contains($request->url(), '/v1beta/openai/v1/');
        });
    }

    public function test_minimax_style_host_keeps_v1_prefix(): void
    {
        config(['services.minimax.host' => 'https://api.minimax.io']);
        Http::fake(['*' => Http::response(['choices' => [['message' => ['content' => '{}']]]])]);

        MiniMaxClient::chat('system', 'user');

        Http::assertSent(fn ($request) => $request->url() === 'https://api.minimax.io/v1/chat/completions');
    }

    public function test_chat_path_config_overrides_endpoint(): void
    {
        config([
            'services.minimax.host' => 'https://example.internal',
            'services.minimax.chat_path' => '/llm/chat',
        ]);
        Http::fake(['*' => Http::response(['choices' => [['message' => ['content' => '{}']]]])]);

        MiniMaxClient::chat('system', 'user');

        Http::assertSent(fn ($request) => $request->url() === 'https://example.internal/llm/chat');
    }

    public function test_live_stats_track_invalid_responses_and_transport_errors(): void
    {
        $data = ['ip' => '203.0.113.7', 'email' => 'live@test.com', 'ip_attempts' => 6, 'email_attempts' => 3];

        // Garbage model output -> parsed as invalid (ai_invalid), not a transport error
        Http::fake(['*' => Http::response(['choices' => [['message' => ['content' => 'not json at all']]]])]);
        McpSecurityService::$liveStats = ['ai_calls' => 0, 'ai_invalid' => 0, 'ai_errors' => 0];

        $mcp = McpSecurityService::analyzeLogin($data);

        $this->assertSame(1, McpSecurityService::$liveStats['ai_calls']);
        $this->assertSame(1, McpSecurityService::$liveStats['ai_invalid']);
        $this->assertSame(0, McpSecurityService::$liveStats['ai_errors']);
        $this->assertFalse($mcp['ai_available']);

        // Transport failure -> ai_errors
        Http::fake(fn () => throw new \Exception('connection refused'));
        McpSecurityService::$liveStats = ['ai_calls' => 0, 'ai_invalid' => 0, 'ai_errors' => 0];

        McpSecurityService::analyzeLogin($data);

        $this->assertSame(1, McpSecurityService::$liveStats['ai_calls']);
        $this->assertSame(1, McpSecurityService::$liveStats['ai_errors']);
        $this->assertSame(0, McpSecurityService::$liveStats['ai_invalid']);
    }
}
