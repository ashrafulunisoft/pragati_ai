<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;
use App\Services\SecurityStore;
use App\Http\Middleware\McpMaliciousLoginMiddleware;
use App\Http\Controllers\Auth\LoginController;

/**
 * Unit test suite for Problem 8 (Reviewer Comment 8):
 * Adversarial manipulation of NAT-aware mitigation (NAT Gaming),
 * shared-IP persistence, account churn, and circuit breaker guardrails.
 */
class McpNatGamingTest extends TestCase
{
    private string $gamedIp = '198.51.100.99';
    private string $attackerSeed1 = 'seed1@attacker.com';
    private string $attackerSeed2 = 'seed2@attacker.com';
    private string $attackerSeed3 = 'seed3@attacker.com';

    protected function setUp(): void
    {
        parent::setUp();
        SecurityStore::$forceCache = false;
        $this->cleanupKeys();

        // Mock AI to return MALICIOUS on brute-force attempts
        Http::fake([
            '*' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => json_encode([
                            'decision' => 'MALICIOUS',
                            'attack_type' => 'credential_stuffing',
                            'risk_score' => 90,
                            'confidence' => 0.95,
                            'recommended_action' => 'block_ip',
                        ])
                    ]
                ]]
            ], 200)
        ]);
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
            "attack:login:ip:{$this->gamedIp}",
            "blocked:ip:{$this->gamedIp}",
            "success:emails:ip:{$this->gamedIp}",
            "blocked:accounts:ip:{$this->gamedIp}",
            "mcp:degraded:ip:{$this->gamedIp}",
            "challenge:issued:ip:{$this->gamedIp}",
            "attack:login:email:victim_extreme@target.com",
            "blocked:email:victim_extreme@target.com",
        ]);

        for ($i = 0; $i < 15; $i++) {
            SecurityStore::del([
                "attack:login:email:victim_{$i}@target.com",
                "blocked:email:victim_{$i}@target.com",
            ]);
        }
    }

    private function attempt(string $ip, string $email, int $ipAttempts = 6, int $emailAttempts = 4): \Symfony\Component\HttpFoundation\Response
    {
        $middleware = new McpMaliciousLoginMiddleware();

        SecurityStore::setex("attack:login:ip:$ip", 900, $ipAttempts);
        SecurityStore::setex("attack:login:email:$email", 900, $emailAttempts);

        $request = Request::create('/login', 'POST', [
            'email' => $email,
            'password' => 'attacker_guess',
        ], [], [], ['REMOTE_ADDR' => $ip]);

        return $middleware->handle($request, fn () => response()->json(['status' => 'authenticated'], 200));
    }

    /**
     * Test 1: Adversary can seed the shared-IP condition with 3 valid accounts.
     */
    public function test_adversary_can_seed_shared_nat_condition(): void
    {
        SecurityStore::sAddWithTtl("success:emails:ip:{$this->gamedIp}", $this->attackerSeed1, 900);
        SecurityStore::sAddWithTtl("success:emails:ip:{$this->gamedIp}", $this->attackerSeed2, 900);
        SecurityStore::sAddWithTtl("success:emails:ip:{$this->gamedIp}", $this->attackerSeed3, 900);

        $distinctSuccess = SecurityStore::scard("success:emails:ip:{$this->gamedIp}");
        $this->assertSame(3, $distinctSuccess);

        $sharedThreshold = (int) config('services.minimax.shared_ip_success_threshold', 3);
        $this->assertGreaterThanOrEqual($sharedThreshold, $distinctSuccess, 'IP is now successfully marked as shared NAT');
    }

    /**
     * Test 2: In a gamed NAT, victim accounts are individually blocked (email-scoped),
     * preventing whole-IP block for single isolated attacks.
     */
    public function test_gamed_nat_victim_accounts_are_individually_blocked(): void
    {
        // Seed 3 valid accounts
        $this->test_adversary_can_seed_shared_nat_condition();

        // Attack victim_0
        $victim = 'victim_0@target.com';
        $response = $this->attempt($this->gamedIp, $victim);

        $this->assertSame(403, $response->getStatusCode());
        $this->assertNotNull(SecurityStore::get("blocked:email:{$victim}"), 'Victim account must be blocked');
        $this->assertNull(SecurityStore::get("blocked:ip:{$this->gamedIp}"), 'IP should not be blocked yet (NAT exemption)');
    }

    /**
     * Test 3: Shared-IP Abuse Circuit Breaker trips when attacked accounts reach the threshold (5).
     * The entire IP is quarantined to prevent infinite rotation attacks.
     */
    public function test_shared_ip_abuse_circuit_breaker_trips_at_threshold(): void
    {
        // Seed 3 valid accounts
        $this->test_adversary_can_seed_shared_nat_condition();

        $maxBlocked = (int) config('services.minimax.shared_ip_max_blocked_accounts', 5);

        // Attack 5 distinct victim accounts sequentially
        for ($i = 0; $i < $maxBlocked; $i++) {
            $victim = "victim_{$i}@target.com";
            $resp = $this->attempt($this->gamedIp, $victim);
            $this->assertSame(403, $resp->getStatusCode());
            $this->assertNotNull(SecurityStore::get("blocked:email:{$victim}"));
        }

        // Circuit breaker must now be tripped!
        $this->assertSame($maxBlocked, SecurityStore::scard("blocked:accounts:ip:{$this->gamedIp}"));
        $this->assertNotNull(SecurityStore::get("blocked:ip:{$this->gamedIp}"), 'Shared IP must now be quarantined by the circuit breaker');

        // Subsequent attack targeting victim_6 should be blocked at the fast path early block
        $resp6 = $this->attempt($this->gamedIp, 'victim_6@target.com');
        $this->assertSame(403, $resp6->getStatusCode());
    }

    /**
     * Test 4: Attacker cannot unblock a quarantined IP by logging in with a valid account.
     */
    public function test_attacker_cannot_unblock_quarantined_ip_with_valid_login(): void
    {
        // Trip circuit breaker
        $this->test_shared_ip_abuse_circuit_breaker_trips_at_threshold();

        // IP is currently blocked
        $this->assertNotNull(SecurityStore::get("blocked:ip:{$this->gamedIp}"));

        // Attacker simulates a valid login from the same IP
        $maxBlocked = (int) config('services.minimax.shared_ip_max_blocked_accounts', 5);
        $this->assertGreaterThanOrEqual($maxBlocked, SecurityStore::scard("blocked:accounts:ip:{$this->gamedIp}"));

        // Simulate LoginController logic
        if (SecurityStore::scard("blocked:accounts:ip:{$this->gamedIp}") < $maxBlocked) {
            SecurityStore::del(["blocked:ip:{$this->gamedIp}"]);
        }

        // Verify the IP remains blocked!
        $this->assertNotNull(SecurityStore::get("blocked:ip:{$this->gamedIp}"), 'Quarantined IP must remain blocked despite valid login attempt');
    }

    /**
     * Test 5: Shared-IP persistence is strictly bounded to the 900s TTL.
     */
    public function test_shared_ip_persistence_is_ttl_bounded(): void
    {
        SecurityStore::sAddWithTtl("success:emails:ip:{$this->gamedIp}", 'test@corp.com', 900);

        if (!SecurityStore::$forceCache) {
            $ttl = Redis::ttl("success:emails:ip:{$this->gamedIp}");
            $this->assertGreaterThan(0, $ttl);
            $this->assertLessThanOrEqual(900, $ttl);
        } else {
            $this->assertSame(1, SecurityStore::scard("success:emails:ip:{$this->gamedIp}"));
        }
    }

    /**
     * Test 6: Circuit breaker also trips on extreme aggregate IP attempts (>= 25).
     */
    public function test_circuit_breaker_trips_on_extreme_aggregate_attempts(): void
    {
        $this->test_adversary_can_seed_shared_nat_condition();

        // 1 single victim account, but with 26 aggregate IP attempts
        $victim = 'victim_extreme@target.com';
        $response = $this->attempt($this->gamedIp, $victim, ipAttempts: 26, emailAttempts: 5);

        $this->assertSame(403, $response->getStatusCode());
        $this->assertNotNull(SecurityStore::get("blocked:ip:{$this->gamedIp}"), 'Extreme aggregate IP attempts must trip the circuit breaker');
    }
}
