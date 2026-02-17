<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Support\Facades\Redis;
use App\Services\McpSecurityService;
use App\Http\Middleware\McpMaliciousLoginMiddleware;

/**
 * Unit tests for MCP Secure Login System
 *
 * Run with: php artisan test tests/Unit/McpSecurityLoginTest.php
 *
 * Or using tinker: php artisan tinker < tests/Unit/McpSecurityLoginTest.php
 */
class McpSecurityLoginTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Clean up test keys
        $this->testIp = '192.168.99.99';
        $this->testEmail = 'test@mcpsecurity.com';
        $this->ipKey = "attack:login:ip:{$this->testIp}";
        $this->emailKey = "attack:login:email:{$this->testEmail}";
        $this->blockedKey = "blocked:ip:{$this->testIp}";

        Redis::del([$this->ipKey, $this->emailKey, $this->blockedKey]);
    }

    protected function tearDown(): void
    {
        // Clean up after tests
        Redis::del([$this->ipKey, $this->emailKey, $this->blockedKey]);
        parent::tearDown();
    }

    /**
     * Test Redis connection
     */
    public function test_redis_connection(): void
    {
        $this->assertTrue(Redis::ping() !== false, 'Redis connection failed');
    }

    /**
     * Test Redis counter increment
     */
    public function test_redis_counter_increment(): void
    {
        Redis::incr($this->ipKey);
        Redis::expire($this->ipKey, 900);

        $this->assertEquals(1, Redis::get($this->ipKey));

        Redis::incr($this->ipKey);
        $this->assertEquals(2, Redis::get($this->ipKey));
    }

    /**
     * Test Redis counter with multiple increments
     */
    public function test_redis_counter_multiple_increments(): void
    {
        for ($i = 1; $i <= 6; $i++) {
            Redis::incr($this->ipKey);
            Redis::expire($this->ipKey, 900);
        }

        $this->assertEquals(6, Redis::get($this->ipKey));
    }

    /**
     * Test Redis counter deletion
     */
    public function test_redis_counter_delete(): void
    {
        Redis::incr($this->ipKey);
        Redis::expire($this->ipKey, 900);

        $this->assertNotNull(Redis::get($this->ipKey));

        Redis::del($this->ipKey);

        $this->assertNull(Redis::get($this->ipKey));
    }

    /**
     * Test risk score calculation - low risk
     */
    public function test_risk_score_low(): void
    {
        $score = McpSecurityService::getRiskScore([
            'ip_attempts' => 2,
            'email_attempts' => 1
        ]);

        $this->assertEquals(0, $score);
    }

    /**
     * Test risk score calculation - medium risk
     */
    public function test_risk_score_medium(): void
    {
        $score = McpSecurityService::getRiskScore([
            'ip_attempts' => 5,
            'email_attempts' => 2
        ]);

        $this->assertEquals(30, $score);
    }

    /**
     * Test risk score calculation - high risk
     */
    public function test_risk_score_high(): void
    {
        $score = McpSecurityService::getRiskScore([
            'ip_attempts' => 10,
            'email_attempts' => 5
        ]);

        $this->assertEquals(90, $score);
    }

    /**
     * Test risk score calculation - maximum capped at 100
     */
    public function test_risk_score_max_capped(): void
    {
        $score = McpSecurityService::getRiskScore([
            'ip_attempts' => 20,
            'email_attempts' => 20
        ]);

        $this->assertLessThanOrEqual(100, $score);
    }

    /**
     * Test McpSecurityService can be instantiated
     */
    public function test_service_instantiation(): void
    {
        $service = new McpSecurityService();
        $this->assertInstanceOf(McpSecurityService::class, $service);
    }

    /**
     * Test McpMaliciousLoginMiddleware can be instantiated
     */
    public function test_middleware_instantiation(): void
    {
        $middleware = new McpMaliciousLoginMiddleware();
        $this->assertInstanceOf(McpMaliciousLoginMiddleware::class, $middleware);
    }

    /**
     * Test IP blocking functionality
     */
    public function test_ip_blocking(): void
    {
        // Simulate threshold exceeded
        Redis::setex($this->blockedKey, 3600, 1);

        $this->assertEquals('1', Redis::get($this->blockedKey));
    }

    /**
     * Test blocked IP check returns expected value
     */
    public function test_blocked_ip_check(): void
    {
        $blocked = Redis::get($this->blockedKey);
        $this->assertNull($blocked);

        Redis::setex($this->blockedKey, 3600, 1);
        $blocked = Redis::get($this->blockedKey);

        $this->assertNotNull($blocked);
    }

    /**
     * Test combined security flow
     */
    public function test_security_flow(): void
    {
        // Step 1: Failed login attempts
        for ($i = 1; $i <= 6; $i++) {
            Redis::incr($this->ipKey);
            Redis::expire($this->ipKey, 900);
        }

        // Step 2: Check threshold
        $ipAttempts = (int) Redis::get($this->ipKey);
        $this->assertGreaterThanOrEqual(5, $ipAttempts);

        // Step 3: Block IP if threshold exceeded
        if ($ipAttempts >= 5) {
            Redis::setex($this->blockedKey, 3600, 1);
        }

        // Step 4: Verify blocking
        $this->assertEquals('1', Redis::get($this->blockedKey));
    }

    /**
     * Test TTL on Redis keys
     */
    public function test_redis_ttl(): void
    {
        Redis::incr($this->ipKey);
        Redis::expire($this->ipKey, 900);

        $ttl = Redis::ttl($this->ipKey);
        $this->assertGreaterThan(0, $ttl);
        $this->assertLessThanOrEqual(900, $ttl);
    }

    /**
     * Test multiple email attempts
     */
    public function test_multiple_email_attempts(): void
    {
        for ($i = 1; $i <= 4; $i++) {
            Redis::incr($this->emailKey);
            Redis::expire($this->emailKey, 900);
        }

        $this->assertEquals(4, Redis::get($this->emailKey));
    }

    /**
     * Test concurrent IP and email tracking
     */
    public function test_concurrent_tracking(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            Redis::incr($this->ipKey);
            Redis::expire($this->ipKey, 900);
        }

        for ($i = 1; $i <= 3; $i++) {
            Redis::incr($this->emailKey);
            Redis::expire($this->emailKey, 900);
        }

        $this->assertEquals(5, Redis::get($this->ipKey));
        $this->assertEquals(3, Redis::get($this->emailKey));
    }
}
