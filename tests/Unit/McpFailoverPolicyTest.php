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
 * Tests for the risk-adaptive failover policy (Problem 2 fix).
 *
 * When the AI layer is unavailable:
 *   risk >= 80  -> immediate block (fail-closed)
 *   risk 50-79  -> emergency_throttle: first (N-1) pass, Nth blocks (degraded mode)
 *   risk < 50   -> allow + monitor
 *
 * Plus the Redis-unavailable cache fallback exercised via SecurityStore::$forceCache.
 */
class McpFailoverPolicyTest extends TestCase
{
    private string $ip = '192.168.77.10';
    private string $email = 'failover@test.com';

    protected function setUp(): void
    {
        parent::setUp();
        SecurityStore::$forceCache = false;
        SecurityStore::del([
            "attack:login:ip:{$this->ip}",
            "attack:login:email:{$this->email}",
            "blocked:ip:{$this->ip}",
            "mcp:degraded:ip:{$this->ip}",
        ]);
        // Every AI call fails -> simulates full AI outage
        Http::fake(function () {
            throw new \Exception('simulated connection timeout');
        });
    }

    protected function tearDown(): void
    {
        SecurityStore::del([
            "attack:login:ip:{$this->ip}",
            "attack:login:email:{$this->email}",
            "blocked:ip:{$this->ip}",
            "mcp:degraded:ip:{$this->ip}",
        ]);
        SecurityStore::$forceCache = false;
        parent::tearDown();
    }

    /**
     * Push failed-attempt counters through the real middleware pipeline.
     */
    private function runMiddleware(): \Symfony\Component\HttpFoundation\Response
    {
        $middleware = new McpMaliciousLoginMiddleware();

        $request = Request::create('/login', 'POST', ['email' => $this->email, 'password' => 'x'], [], [], [
            'REMOTE_ADDR' => $this->ip,
        ]);

        return $middleware->handle($request, fn () => response('ALLOWED'));
    }

    private function setAttempts(int $ipAttempts, int $emailAttempts): void
    {
        SecurityStore::del(["attack:login:ip:{$this->ip}", "attack:login:email:{$this->email}"]);
        if ($ipAttempts > 0) {
            Redis::setex("attack:login:ip:{$this->ip}", 900, $ipAttempts);
        }
        if ($emailAttempts > 0) {
            Redis::setex("attack:login:email:{$this->email}", 900, $emailAttempts);
        }
    }

    /**
     * P2-a: AI down + HIGH risk (>=80) -> fail-closed, blocked immediately.
     * (Old behaviour also blocked here, but via blanket defaults; now explicit fail-closed tier.)
     */
    public function test_ai_outage_high_risk_fails_closed(): void
    {
        $this->setAttempts(10, 5); // rule score 90

        $result = McpSecurityService::analyzeLogin([
            'ip' => $this->ip, 'email' => $this->email,
            'ip_attempts' => 10, 'email_attempts' => 5,
        ]);

        $this->assertFalse($result['ai_available']);
        $this->assertSame('fail_closed', $result['degraded_mode']);
        $this->assertSame('block_ip', $result['recommended_action']);
        $this->assertSame('MALICIOUS', $result['decision']);

        $response = $this->runMiddleware();
        $this->assertSame(403, $response->getStatusCode());
        $this->assertNotNull(SecurityStore::get("blocked:ip:{$this->ip}"));
    }

    /**
     * P2-b (THE GAP): AI down + MEDIUM risk (50-79).
     * Old code: allowed forever. New: strike 1..2 pass, strike 3 -> 429 + temporary block.
     */
    public function test_ai_outage_medium_risk_n_strike_block(): void
    {
        $this->setAttempts(5, 3); // rule score 55 (medium band)

        // Strike 1: allowed with monitoring
        $r1 = $this->runMiddleware();
        $this->assertSame('ALLOWED', $r1->getContent());
        $this->assertNull(SecurityStore::get("blocked:ip:{$this->ip}"));

        // Strike 2: still allowed
        $r2 = $this->runMiddleware();
        $this->assertSame('ALLOWED', $r2->getContent());

        // Strike 3 (>= degraded_threshold=3): blocked with 429
        $r3 = $this->runMiddleware();
        $this->assertSame(429, $r3->getStatusCode());
        $this->assertNotNull(SecurityStore::get("blocked:ip:{$this->ip}"));
        // Degraded block uses the shorter TTL (900), not the 3600 full block
        $ttl = Redis::ttl("blocked:ip:{$this->ip}");
        $this->assertGreaterThan(0, $ttl);
        $this->assertLessThanOrEqual(900, $ttl);
    }

    /**
     * P2-c: AI down + LOW risk (<50) -> allow + monitor (bounded fail-open, audited).
     */
    public function test_ai_outage_low_risk_monitored_allow(): void
    {
        $result = McpSecurityService::analyzeLogin([
            'ip' => $this->ip, 'email' => $this->email,
            'ip_attempts' => 3, 'email_attempts' => 0, // score 15
        ]);

        $this->assertFalse($result['ai_available']);
        $this->assertSame('fail_open_monitored', $result['degraded_mode']);
        $this->assertSame('SAFE', $result['decision']);
        $this->assertNull(SecurityStore::get("blocked:ip:{$this->ip}"));
    }

    /**
     * P2-d: applyDegradedPolicy tier boundaries.
     */
    public function test_degraded_policy_tiers(): void
    {
        $base = ['ai_available' => false, 'risk_score' => 0, 'recommended_action' => 'monitor'];

        $high = McpSecurityService::applyDegradedPolicy(['ai_available' => false, 'risk_score' => 80, 'recommended_action' => 'otp']);
        $this->assertSame(['block_ip', 'fail_closed'], [$high['recommended_action'], $high['degraded_mode']]);

        $mid = McpSecurityService::applyDegradedPolicy(['ai_available' => false, 'risk_score' => 55, 'recommended_action' => 'otp']);
        $this->assertSame(['emergency_throttle', 'heightened_throttle'], [$mid['recommended_action'], $mid['degraded_mode']]);

        $low = McpSecurityService::applyDegradedPolicy(['ai_available' => false, 'risk_score' => 15, 'recommended_action' => 'monitor']);
        $this->assertSame('fail_open_monitored', $low['degraded_mode']);

        // AI available -> no degraded tiering at all
        $ok = McpSecurityService::applyDegradedPolicy(['ai_available' => true, 'risk_score' => 55, 'recommended_action' => 'otp']);
        $this->assertNull($ok['degraded_mode']);
        $this->assertSame('otp', $ok['recommended_action']);
    }

    /**
     * P2-e: Redis unavailable -> SecurityStore falls back to the cache and still
     * counts, blocks and reads back state (emergency rate-limit policy).
     */
    public function test_security_store_cache_fallback(): void
    {
        SecurityStore::$forceCache = true; // simulate Redis down

        $key = 'attack:login:ip:10.77.77.77';

        $this->assertSame(1, SecurityStore::incrWithTtl($key, 900));
        $this->assertSame(2, SecurityStore::incrWithTtl($key, 900));
        $this->assertSame(3, SecurityStore::incrWithTtl($key, 900));

        SecurityStore::setex('blocked:ip:10.77.77.77', 900, 1);
        $this->assertSame(1, SecurityStore::get('blocked:ip:10.77.77.77'));

        SecurityStore::del([$key, 'blocked:ip:10.77.77.77']);
        $this->assertNull(SecurityStore::get('blocked:ip:10.77.77.77'));
    }

    /**
     * P2-f: With Redis UP, SecurityStore uses Redis natively (no regression).
     */
    public function test_security_store_redis_path(): void
    {
        SecurityStore::setex('blocked:ip:10.66.66.66', 60, 1);
        $this->assertSame(1, (int) Redis::get('blocked:ip:10.66.66.66')); // visible via raw Redis
        $this->assertSame(1, (int) SecurityStore::get('blocked:ip:10.66.66.66'));

        $count = SecurityStore::incrWithTtl('attack:login:ip:10.66.66.66', 900);
        $this->assertSame(1, $count);
        $this->assertSame(1, (int) Redis::get('attack:login:ip:10.66.66.66'));

        SecurityStore::del(['blocked:ip:10.66.66.66', 'attack:login:ip:10.66.66.66']);
    }
}
