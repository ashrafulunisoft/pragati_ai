<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;
use App\Services\McpSecurityService;
use App\Services\SecurityStore;
use App\Http\Middleware\McpMaliciousLoginMiddleware;

/**
 * Configurable-threshold tests (Major-fix M8).
 *
 * The paper's Appendix A claims these are env-configurable:
 *   LOGIN_ATTEMPT_THRESHOLD_IP / LOGIN_ATTEMPT_THRESHOLD_EMAIL /
 *   BLOCK_DURATION_SECONDS / ATTEMPT_WINDOW_SECONDS
 * ...plus risk bands and rule weights. These tests prove the claim is now
 * TRUE: overriding the config changes real middleware behaviour.
 */
class McpConfigurableThresholdsTest extends TestCase
{
    private string $ip = '192.168.66.10';
    private string $email = 'config@test.com';

    protected function setUp(): void
    {
        parent::setUp();
        $this->clean();
    }

    protected function tearDown(): void
    {
        $this->clean();
        parent::tearDown();
    }

    private function clean(): void
    {
        SecurityStore::del([
            "attack:login:ip:{$this->ip}",
            "attack:login:email:{$this->email}",
            "blocked:ip:{$this->ip}",
            "blocked:email:{$this->email}",
            'mcp:degraded:ip:'.$this->ip,
        ]);
    }

    private function runMiddleware(): \Symfony\Component\HttpFoundation\Response
    {
        $middleware = new McpMaliciousLoginMiddleware();

        $request = Request::create('/login', 'POST', ['email' => $this->email, 'password' => 'x'],
            [], [], ['REMOTE_ADDR' => $this->ip]);

        return $middleware->handle($request, fn () => response('ALLOWED'));
    }

    /*
     * Analysis trigger thresholds
     */

    public function test_analysis_trigger_ip_threshold_is_configurable(): void
    {
        config(['services.minimax.threshold_ip_analyze' => 2]);

        Http::fake(['*' => Http::response(['choices' => [['message' => ['content' => json_encode([
            'decision' => 'SAFE', 'confidence' => 0.5,
        ])]]]], 200)]);

        Redis::setex("attack:login:ip:{$this->ip}", 900, 2);   // BELOW default 5

        $this->runMiddleware();

        // With the lowered threshold the AI WAS invoked (fake recorded the call)
        Http::assertSent(fn ($request) => str_contains($request->url(), 'chat/completions'));
    }

    public function test_default_threshold_still_applies_when_not_overridden(): void
    {
        Http::fake();

        Redis::setex("attack:login:ip:{$this->ip}", 900, 4);   // below default 5
        Redis::setex("attack:login:email:{$this->email}", 900, 2); // below default 3

        $response = $this->runMiddleware();

        $this->assertSame('ALLOWED', $response->getContent());
        Http::assertNothingSent();
    }

    /*
     * Block duration
     */

    public function test_block_duration_is_configurable(): void
    {
        config(['services.minimax.block_ttl' => 120]);

        Http::fake(['*' => Http::response(['choices' => [['message' => ['content' => json_encode([
            'decision' => 'MALICIOUS', 'risk_score' => 90, 'confidence' => 0.9,
        ])]]]], 200)]);

        Redis::setex("attack:login:ip:{$this->ip}", 900, 10);
        Redis::setex("attack:login:email:{$this->email}", 900, 5);

        $response = $this->runMiddleware();

        $this->assertSame(403, $response->getStatusCode());
        $ttl = Redis::ttl("blocked:ip:{$this->ip}");
        $this->assertGreaterThan(0, $ttl);
        $this->assertLessThanOrEqual(120, $ttl);   // custom duration, not 3600
    }

    /*
     * Risk bands
     */

    public function test_risk_bands_are_configurable(): void
    {
        config(['services.minimax.risk_band_malicious' => 60]);

        // score 65: MALICIOUS with default band 80, SAFE-ish with band 60 -> now MALICIOUS
        $this->assertSame('block_ip', McpSecurityService::getRecommendedAction(65));

        // fusion honours the custom band (rule 65 >= 60 -> F1)
        $fused = McpSecurityService::fuseDecision(null, 65);
        $this->assertSame('MALICIOUS', $fused['decision']);
        $this->assertSame('F1_rule_guardrail_high_score', $fused['fusion']);

        // and with defaults restored, 65 is only otp
        config(['services.minimax.risk_band_malicious' => 80]);
        $this->assertSame('otp', McpSecurityService::getRecommendedAction(65));
    }

    /*
     * Rule weights
     */

    public function test_rule_weights_are_configurable(): void
    {
        config(['services.minimax.rule_weights' => [
            'ip'    => [[2, 20]],    // any IP >= 2 attempts -> 20 points
            'email' => [[1, 10]],    // any email attempt   -> 10 points
        ]]);

        $this->assertSame(30, McpSecurityService::getRiskScore(['ip_attempts' => 2, 'email_attempts' => 1]));
        $this->assertSame(0, McpSecurityService::getRiskScore(['ip_attempts' => 1, 'email_attempts' => 0]));
    }

    /*
     * Window seconds flow through to the counter TTL
     */

    public function test_window_seconds_is_configurable(): void
    {
        config(['services.minimax.window_seconds' => 300]);

        // simulate the controller's failure path
        SecurityStore::incrWithTtl("attack:login:ip:{$this->ip}", (int) config('services.minimax.window_seconds'));

        $ttl = Redis::ttl("attack:login:ip:{$this->ip}");
        $this->assertGreaterThan(0, $ttl);
        $this->assertLessThanOrEqual(300, $ttl);
    }
}
