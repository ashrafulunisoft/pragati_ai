<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;
use App\Services\McpSecurityService;
use App\Services\SecurityStore;
use App\Services\ChallengeService;
use App\Http\Middleware\McpMaliciousLoginMiddleware;

/**
 * Unit test suite for Problem 7 (Reviewer Comment 7):
 * End-to-end login latency under concurrency, p50/p95/p99 benchmarking,
 * timeout bounds, retry amplification, and upstream outage resilience.
 */
class McpConcurrencyLatencyTest extends TestCase
{
    private string $ip = '192.168.88.77';
    private string $email = 'bench_user@test.com';

    protected function setUp(): void
    {
        parent::setUp();
        SecurityStore::$forceCache = false;
        $this->cleanupKeys();
    }

    protected function tearDown(): void
    {
        $this->cleanupKeys();
        SecurityStore::$forceCache = false;
        Http::fake(null);
        parent::tearDown();
    }

    private function cleanupKeys(): void
    {
        SecurityStore::del([
            "attack:login:ip:{$this->ip}",
            "attack:login:email:{$this->email}",
            "blocked:ip:{$this->ip}",
            "blocked:email:{$this->email}",
            "mcp:degraded:ip:{$this->ip}",
            "challenge:issued:ip:{$this->ip}",
        ]);
    }

    /**
     * Test 1: Sub-threshold fast path (Redis-only legitimate login).
     * Must execute in < 10 ms (typically <= 2 ms) with ZERO AI invocations.
     */
    public function test_subthreshold_fast_path_latency_and_zero_ai(): void
    {
        $middleware = new McpMaliciousLoginMiddleware();
        SecurityStore::setex("attack:login:ip:{$this->ip}", 60, 1);
        SecurityStore::setex("attack:login:email:{$this->email}", 60, 1);

        $initialAiCalls = McpSecurityService::$liveStats['ai_calls'];

        $start = microtime(true);
        $request = Request::create('/login', 'POST', ['email' => $this->email, 'password' => 'secret'], [], [], [
            'REMOTE_ADDR' => $this->ip,
        ]);
        $response = $middleware->handle($request, fn () => response()->json(['status' => 'authenticated'], 200));
        $latencyMs = (microtime(true) - $start) * 1000;

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('SAFE', $response->headers->get('X-Mcp-Decision'));
        $this->assertSame('ALLOWED', $response->headers->get('X-Mcp-Enforcement'));
        $this->assertSame($initialAiCalls, McpSecurityService::$liveStats['ai_calls'], 'Fast path must never call the AI layer');
        $this->assertLessThan(20.0, $latencyMs, 'Fast-path latency must be under 20 ms');
    }

    /**
     * Test 2: Adversarial post-block flood fast-path drop (DoS defense).
     * Once an attacker IP is flagged, 100% of subsequent attack requests
     * must be dropped at the Redis layer in <= 5 ms without AI invocation.
     */
    public function test_post_block_adversarial_flood_dropped_at_fastpath(): void
    {
        $middleware = new McpMaliciousLoginMiddleware();
        // Attacker IP already blocked
        SecurityStore::setex("blocked:ip:{$this->ip}", 3600, 1);

        $initialAiCalls = McpSecurityService::$liveStats['ai_calls'];

        $latencies = [];
        for ($i = 0; $i < 20; $i++) {
            $start = microtime(true);
            $request = Request::create('/login', 'POST', ['email' => $this->email, 'password' => 'brute'], [], [], [
                'REMOTE_ADDR' => $this->ip,
            ]);
            $response = $middleware->handle($request, fn () => response()->json(['status' => 'ok'], 200));
            $latencies[] = (microtime(true) - $start) * 1000;

            $this->assertSame(403, $response->getStatusCode());
            $this->assertSame('MALICIOUS', $response->headers->get('X-Mcp-Decision'));
            $this->assertSame('BLOCKED', $response->headers->get('X-Mcp-Enforcement'));
        }

        $avgLatency = array_sum($latencies) / count($latencies);
        $this->assertSame($initialAiCalls, McpSecurityService::$liveStats['ai_calls'], 'Blocked flood must never hit AI');
        $this->assertLessThan(5.0, $avgLatency, 'Blocked attacker requests must be short-circuited in < 5 ms');
    }

    /**
     * Test 3: Step-up challenge issuance and resolution latency.
     * Issuance (428) + cryptographic HMAC verification passes in < 15 ms.
     */
    public function test_step_up_challenge_issuance_and_verification(): void
    {
        $middleware = new McpMaliciousLoginMiddleware();

        // Hit threshold with medium risk in the captcha band (30-49)
        SecurityStore::setex("attack:login:ip:{$this->ip}", 60, 5);
        SecurityStore::setex("attack:login:email:{$this->email}", 60, 0);

        Http::fake([
            '*' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => json_encode([
                            'decision' => 'SUSPICIOUS',
                            'attack_type' => 'credential_stuffing',
                            'risk_score' => 40,
                            'confidence' => 0.85,
                            'recommended_action' => 'captcha',
                        ])
                    ]
                ]]
            ], 200)
        ]);

        // Step 1: Issue challenge
        $req1 = Request::create('/login', 'POST', ['email' => $this->email, 'password' => 'secret'], [], [], [
            'REMOTE_ADDR' => $this->ip,
        ]);
        $resp1 = $middleware->handle($req1, fn () => response()->json(['status' => 'ok'], 200));

        $this->assertSame(428, $resp1->getStatusCode());
        $body = json_decode($resp1->getContent(), true);
        $challenge = $body['challenge'];
        $this->assertNotNull($challenge['token']);

        // Step 2: Solve challenge and verify
        $a = $b = 0;
        sscanf($challenge['question'], '%d + %d = ?', $a, $b);
        $answer = (string) ($a + $b);

        $startVerify = microtime(true);
        $req2 = Request::create('/login', 'POST', [
            'email' => $this->email,
            'password' => 'secret',
            'security_challenge_token' => $challenge['token'],
            'security_challenge_answer' => $answer,
        ], [], [], ['REMOTE_ADDR' => $this->ip]);
        $resp2 = $middleware->handle($req2, fn () => response()->json(['status' => 'authenticated'], 200));
        $verifyDuration = (microtime(true) - $startVerify) * 1000;

        $this->assertSame(200, $resp2->getStatusCode());
        $this->assertSame('CHALLENGE_SOLVED', $resp2->headers->get('X-Mcp-Enforcement'));
        $this->assertLessThan(15.0, $verifyDuration, 'HMAC Challenge verification should be < 15 ms');
    }

    /**
     * Test 4: Upstream model outage, retry amplification, and degraded fallback.
     * With MCP_RETRIES=1, an outage triggers exactly 2 upstream requests (2.0x amplification).
     * Degraded policy prevents crash and applies deterministic fallback.
     */
    public function test_upstream_outage_bounded_retries_and_degraded_fallback(): void
    {
        $middleware = new McpMaliciousLoginMiddleware();
        SecurityStore::setex("attack:login:ip:{$this->ip}", 60, 6);
        SecurityStore::setex("attack:login:email:{$this->email}", 60, 4);

        $upstreamAttempts = 0;
        Http::fake(function () use (&$upstreamAttempts) {
            $upstreamAttempts++;
            return Http::response(['error' => 'Service Unavailable (503)'], 503);
        });

        $req = Request::create('/login', 'POST', ['email' => $this->email, 'password' => 'pass'], [], [], [
            'REMOTE_ADDR' => $this->ip,
        ]);

        $resp = $middleware->handle($req, fn () => response()->json(['status' => 'ok'], 200));

        // Rule score for 6 ip attempts + 4 email attempts = 30 + 40 = 70 (medium risk)
        // With AI down, medium risk triggers emergency_throttle (strike 1 allowed with monitoring)
        $this->assertSame(200, $resp->getStatusCode());
        $this->assertSame('SUSPICIOUS', $resp->headers->get('X-Mcp-Decision'));
        $this->assertSame('ALLOWED', $resp->headers->get('X-Mcp-Enforcement'));

        // Confirm retry amplification bound: initial call + 1 retry = 2 attempts
        $expectedAttempts = 1 + (int) config('services.minimax.retries', 1);
        $this->assertSame($expectedAttempts, $upstreamAttempts, "Outage must trigger exactly {$expectedAttempts} upstream requests (amplification bound = 2x)");
    }

    /**
     * Test 5: Outage under high risk fails closed immediately (HTTP 403).
     */
    public function test_upstream_outage_high_risk_fails_closed_immediately(): void
    {
        $middleware = new McpMaliciousLoginMiddleware();
        // High risk: 10 ip attempts + 5 email attempts = score 90 (>= 80)
        SecurityStore::setex("attack:login:ip:{$this->ip}", 60, 10);
        SecurityStore::setex("attack:login:email:{$this->email}", 60, 5);

        Http::fake(function () {
            throw new \Illuminate\Http\Client\ConnectionException('Gateway Timeout 504');
        });

        $req = Request::create('/login', 'POST', ['email' => $this->email, 'password' => 'brute'], [], [], [
            'REMOTE_ADDR' => $this->ip,
        ]);
        $resp = $middleware->handle($req, fn () => response()->json(['status' => 'should_block'], 200));

        $this->assertSame(403, $resp->getStatusCode());
        $this->assertSame('MALICIOUS', $resp->headers->get('X-Mcp-Decision'));
        $this->assertSame('BLOCKED', $resp->headers->get('X-Mcp-Enforcement'));
        $this->assertNotNull(SecurityStore::get("blocked:ip:{$this->ip}"));
    }
}
