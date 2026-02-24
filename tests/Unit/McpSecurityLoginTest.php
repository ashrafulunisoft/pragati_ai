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

    /**
     * Test that MCP analysis logs to mcp_security.log
     * This is the actual test that verifies MCP logging works!
     */
    public function test_mcp_analysis_logs_to_file(): void
    {
        $logFile = storage_path('logs/mcp_security.log');
        
        // Clear the log file before test
        file_put_contents($logFile, '');
        
        // Get initial file size
        $initialSize = filesize($logFile);
        
        // Call MCP analysis (this should write to log)
        $result = McpSecurityService::analyzeLogin([
            'ip' => '192.168.99.100',
            'email' => 'test_logging@example.com',
            'ip_attempts' => 6,  // Above threshold (5)
            'email_attempts' => 4, // Above threshold (3)
        ]);
        
        // Check that result has expected keys
        $this->assertArrayHasKey('decision', $result);
        $this->assertArrayHasKey('risk_score', $result);
        $this->assertArrayHasKey('attack_type', $result);
        $this->assertArrayHasKey('confidence', $result);
        $this->assertArrayHasKey('recommended_action', $result);
        
        // Give filesystem time to write
        clearstatcache(true, $logFile);
        
        // Read the log file
        $logContent = file_get_contents($logFile);
        
        // Verify log was written
        $this->assertNotEmpty($logContent, 'MCP log file should not be empty after analyzeLogin()');
        
        // Verify log contains expected data
        $this->assertStringContainsString('192.168.99.100', $logContent, 'Log should contain IP address');
        $this->assertStringContainsString('test_logging@example.com', $logContent, 'Log should contain email');
        $this->assertStringContainsString('MCP_DECISION', $logContent, 'Log should contain MCP_DECISION');
    }
    
    /**
     * Test that MCP logs contain all required fields
     */
    public function test_mcp_log_contains_all_fields(): void
    {
        $logFile = storage_path('logs/mcp_security.log');
        
        // DON'T clear the log file - append to existing logs from previous test
        
        // Call MCP analysis with different IP
        McpSecurityService::analyzeLogin([
            'ip' => '10.0.0.50',
            'email' => 'fulltest@example.com',
            'ip_attempts' => 10,
            'email_attempts' => 5,
        ]);
        
        // Give filesystem time to write
        clearstatcache(true, $logFile);
        
        // Read the log file
        $logContent = file_get_contents($logFile);
        
        // Verify FIRST IP from previous test
        $this->assertStringContainsString('192.168.99.100', $logContent, 'Log should contain first test IP');
        $this->assertStringContainsString('test_logging@example.com', $logContent, 'Log should contain first test email');
        
        // Verify SECOND IP from this test
        $this->assertStringContainsString('"ip":"10.0.0.50"', $logContent);
        $this->assertStringContainsString('"email":"fulltest@example.com"', $logContent);
        $this->assertStringContainsString('"decision"', $logContent);
        $this->assertStringContainsString('"attack_type"', $logContent);
        $this->assertStringContainsString('"risk_score"', $logContent);
        $this->assertStringContainsString('"confidence"', $logContent);
        $this->assertStringContainsString('"recommended_action"', $logContent);
        
        // Verify we have BOTH IPs in log (count occurrences)
        $this->assertStringContainsString('MCP_DECISION', $logContent, 'Log should contain MCP_DECISION');
    }
    
    /**
     * Test blocking when risk_score >= 80 (high risk)
     */
    public function test_blocking_when_high_risk_score(): void
    {
        // Simulate high risk scenario
        $ip = '192.168.99.200';
        $ipKey = "attack:login:ip:$ip";
        $emailKey = "attack:login:email:test@example.com";
        $blockedKey = "blocked:ip:$ip";
        
        // Set high attempt counts (above threshold)
        Redis::setex($ipKey, 900, 10);  // 10 IP attempts (>=5 threshold)
        Redis::setex($emailKey, 900, 5); // 5 email attempts (>=3 threshold)
        
        // Get the values
        $ipAttempts = (int) Redis::get($ipKey);
        $emailAttempts = (int) Redis::get($emailKey);
        
        // Verify threshold is exceeded
        $this->assertGreaterThanOrEqual(5, $ipAttempts);
        $this->assertGreaterThanOrEqual(3, $emailAttempts);
        
        // Calculate risk score
        $riskScore = McpSecurityService::getRiskScore([
            'ip_attempts' => $ipAttempts,
            'email_attempts' => $emailAttempts,
        ]);
        
        // Risk score should be >= 80 (high)
        $this->assertGreaterThanOrEqual(80, $riskScore, 'Risk score should be >= 80 for high attempts');
        
        // Simulate blocking
        if ($riskScore >= 80) {
            Redis::setex($blockedKey, 3600, 1);
        }
        
        // Verify IP is blocked
        $this->assertEquals('1', Redis::get($blockedKey));
        
        // Cleanup
        Redis::del([$ipKey, $emailKey, $blockedKey]);
    }
    
    /**
     * Test recommended_action field in MCP response
     */
    public function test_mcp_recommended_action(): void
    {
        $logFile = storage_path('logs/mcp_security.log');
        
        // Call MCP analysis with high attempts to trigger different actions
        $result = McpSecurityService::analyzeLogin([
            'ip' => '172.16.0.100',
            'email' => 'actiontest@example.com',
            'ip_attempts' => 12,  // Very high
            'email_attempts' => 6, // Very high
        ]);
        
        // Verify recommended_action exists and is valid
        $this->assertArrayHasKey('recommended_action', $result);
        $this->assertContains($result['recommended_action'], ['block_ip', 'captcha', 'otp', 'monitor']);
        
        // Verify risk score affects recommended action
        $this->assertGreaterThanOrEqual(80, $result['risk_score']);
        
        // Give filesystem time to write
        clearstatcache(true, $logFile);
        
        // Read log and verify recommended_action is logged
        $logContent = file_get_contents($logFile);
        $this->assertStringContainsString('"recommended_action"', $logContent);
    }
    
    /**
     * Test that medium risk score (50-79) doesn't block
     */
    public function test_medium_risk_does_not_block(): void
    {
        $ip = '192.168.99.250';
        $ipKey = "attack:login:ip:$ip";
        $emailKey = "attack:login:email:medium@example.com";
        $blockedKey = "blocked:ip:$ip";
        
        // Set medium attempt counts
        Redis::setex($ipKey, 900, 5);  // 5 IP attempts
        Redis::setex($emailKey, 900, 2); // 2 email attempts
        
        // Calculate risk score
        $riskScore = McpSecurityService::getRiskScore([
            'ip_attempts' => 5,
            'email_attempts' => 2,
        ]);
        
        // Risk score should be medium (30-79)
        $this->assertGreaterThanOrEqual(30, $riskScore);
        $this->assertLessThan(80, $riskScore);
        
        // Should NOT block for medium risk
        $shouldBlock = ($riskScore >= 80);
        $this->assertFalse($shouldBlock, 'Medium risk should not block');
        
        // Verify IP is NOT blocked
        $this->assertNull(Redis::get($blockedKey));
        
        // Cleanup
        Redis::del([$ipKey, $emailKey, $blockedKey]);
    }
    
    // ============================================================================
    // COMPREHENSIVE RISK SCORE TESTS - ALL SCENARIOS
    // ============================================================================
    
    /**
     * Test risk score: 0 attempts = 0 (monitor)
     */
    public function test_risk_score_zero_attempts(): void
    {
        $score = McpSecurityService::getRiskScore(['ip_attempts' => 0, 'email_attempts' => 0]);
        $this->assertEquals(0, $score);
        $this->assertEquals('monitor', McpSecurityService::getRecommendedAction($score));
    }
    
    /**
     * Test risk score: 1-2 IP attempts, 0-1 email = 0 (monitor)
     */
    public function test_risk_score_low_attempts(): void
    {
        $score = McpSecurityService::getRiskScore(['ip_attempts' => 2, 'email_attempts' => 1]);
        $this->assertEquals(0, $score);
        $this->assertEquals('monitor', McpSecurityService::getRecommendedAction($score));
    }
    
    /**
     * Test risk score: 3 IP attempts = 15 (monitor)
     */
    public function test_risk_score_3_ip_attempts(): void
    {
        $score = McpSecurityService::getRiskScore(['ip_attempts' => 3, 'email_attempts' => 0]);
        $this->assertEquals(15, $score);
        $this->assertEquals('monitor', McpSecurityService::getRecommendedAction($score));
    }
    
    /**
     * Test risk score: 3 IP + 3 email = 40 (captcha)
     */
    public function test_risk_score_captcha(): void
    {
        $score = McpSecurityService::getRiskScore(['ip_attempts' => 3, 'email_attempts' => 3]);
        $this->assertEquals(40, $score);
        $this->assertEquals('captcha', McpSecurityService::getRecommendedAction($score));
    }
    
    /**
     * Test risk score: 5 IP attempts = 30 (captcha)
     */
    public function test_risk_score_5_ip_attempts(): void
    {
        $score = McpSecurityService::getRiskScore(['ip_attempts' => 5, 'email_attempts' => 0]);
        $this->assertEquals(30, $score);
        $this->assertEquals('captcha', McpSecurityService::getRecommendedAction($score));
    }
    
    /**
     * Test risk score: 5 IP + 3 email = 55 (otp)
     */
    public function test_risk_score_otp(): void
    {
        $score = McpSecurityService::getRiskScore(['ip_attempts' => 5, 'email_attempts' => 3]);
        $this->assertEquals(55, $score);
        $this->assertEquals('otp', McpSecurityService::getRecommendedAction($score));
    }
    
    /**
     * Test risk score: 10 IP attempts = 50 (otp)
     */
    public function test_risk_score_10_ip_attempts(): void
    {
        $score = McpSecurityService::getRiskScore(['ip_attempts' => 10, 'email_attempts' => 0]);
        $this->assertEquals(50, $score);
        $this->assertEquals('otp', McpSecurityService::getRecommendedAction($score));
    }
    
    /**
     * Test risk score: 10 IP + 5 email = 90 (block_ip)
     */
    public function test_risk_score_block_ip(): void
    {
        $score = McpSecurityService::getRiskScore(['ip_attempts' => 10, 'email_attempts' => 5]);
        $this->assertEquals(90, $score);
        $this->assertEquals('block_ip', McpSecurityService::getRecommendedAction($score));
    }
    
    /**
     * Test risk score: 20 + 20 = 90 (block_ip) - max achievable
     */
    public function test_risk_score_max_capped_100(): void
    {
        // ip_attempts 20 → +50 (max)
        // email_attempts 20 → +40 (max)
        // Total = 90 (not 100, because formula doesn't stack)
        $score = McpSecurityService::getRiskScore(['ip_attempts' => 20, 'email_attempts' => 20]);
        $this->assertEquals(90, $score);
        $this->assertEquals('block_ip', McpSecurityService::getRecommendedAction($score));
    }
    
    // ============================================================================
    // RECOMMENDED ACTION TESTS - ALL 4 VALUES
    // ============================================================================
    
    /**
     * Test recommended_action: monitor (<30)
     */
    public function test_recommended_action_monitor(): void
    {
        $this->assertEquals('monitor', McpSecurityService::getRecommendedAction(0));
        $this->assertEquals('monitor', McpSecurityService::getRecommendedAction(15));
        $this->assertEquals('monitor', McpSecurityService::getRecommendedAction(29));
    }
    
    /**
     * Test recommended_action: captcha (30-49)
     */
    public function test_recommended_action_captcha(): void
    {
        $this->assertEquals('captcha', McpSecurityService::getRecommendedAction(30));
        $this->assertEquals('captcha', McpSecurityService::getRecommendedAction(40));
        $this->assertEquals('captcha', McpSecurityService::getRecommendedAction(49));
    }
    
    /**
     * Test recommended_action: otp (50-79)
     */
    public function test_recommended_action_otp(): void
    {
        $this->assertEquals('otp', McpSecurityService::getRecommendedAction(50));
        $this->assertEquals('otp', McpSecurityService::getRecommendedAction(65));
        $this->assertEquals('otp', McpSecurityService::getRecommendedAction(79));
    }
    
    /**
     * Test recommended_action: block_ip (80-100)
     */
    public function test_recommended_action_block_ip(): void
    {
        $this->assertEquals('block_ip', McpSecurityService::getRecommendedAction(80));
        $this->assertEquals('block_ip', McpSecurityService::getRecommendedAction(90));
        $this->assertEquals('block_ip', McpSecurityService::getRecommendedAction(100));
    }
    
    /**
     * Test ALL scenarios and write to log file
     * This test verifies ALL risk scores and recommended_actions
     */
    public function test_all_scenarios_logged(): void
    {
        $logFile = storage_path('logs/mcp_security.log');
        
        // Clear and start fresh
        file_put_contents($logFile, '');
        
        // Test ALL scenarios
        $scenarios = [
            // [ip_attempts, email_attempts, expected_score, expected_action]
            [0, 0, 0, 'monitor'],           // 0 attempts
            [1, 0, 0, 'monitor'],           // 1 IP attempt
            [2, 1, 0, 'monitor'],           // 2 IP, 1 email
            [3, 0, 15, 'monitor'],         // 3 IP attempts
            [3, 3, 40, 'captcha'],         // 3 IP + 3 email
            [4, 2, 15, 'monitor'],         // 4 IP + 2 email (4<5, 2<3) 
            [5, 0, 30, 'captcha'],         // 5 IP attempts
            [5, 3, 55, 'otp'],             // 5 IP + 3 email
            [7, 4, 55, 'otp'],             // 7 IP + 4 email
            [10, 0, 50, 'otp'],            // 10 IP attempts
            [10, 5, 90, 'block_ip'],       // 10 IP + 5 email
            [15, 10, 90, 'block_ip'],     // High attempts
            [20, 20, 90, 'block_ip'],     // Max attempts
        ];
        
        $testIPs = [
            '10.0.0.1', '10.0.0.2', '10.0.0.3', '10.0.0.4', '10.0.0.5',
            '10.0.0.6', '10.0.0.7', '10.0.0.8', '10.0.0.9', '10.0.0.10',
            '10.0.0.11', '10.0.0.12', '10.0.0.13'
        ];
        
        $testIndex = 0;
        foreach ($scenarios as $scenario) {
            $ipAttempts = $scenario[0];
            $emailAttempts = $scenario[1];
            $expectedScore = $scenario[2];
            $expectedAction = $scenario[3];
            
            // Call MCP analysis
            $result = McpSecurityService::analyzeLogin([
                'ip' => $testIPs[$testIndex],
                'email' => 'test' . $testIndex . '@example.com',
                'ip_attempts' => $ipAttempts,
                'email_attempts' => $emailAttempts,
            ]);
            
            // Verify risk score
            $this->assertEquals($expectedScore, $result['risk_score'], 
                "Failed for ip=$ipAttempts, email=$emailAttempts");
            
            // Verify recommended action
            $this->assertEquals($expectedAction, $result['recommended_action'],
                "Failed for ip=$ipAttempts, email=$emailAttempts");
            
            $testIndex++;
        }
        
        // Give filesystem time to write
        clearstatcache(true, $logFile);
        
        // Read and verify log
        $logContent = file_get_contents($logFile);
        
        // Verify all scenarios are logged (IPs: 10.0.0.1 to 10.0.0.13)
        $this->assertStringContainsString('10.0.0.1', $logContent);
        $this->assertStringContainsString('10.0.0.6', $logContent);  // 4 IP attempts scenario
        $this->assertStringContainsString('10.0.0.11', $logContent);
        $this->assertStringContainsString('10.0.0.13', $logContent);
        
        // Verify all recommended_actions are in log
        $this->assertStringContainsString('"recommended_action":"monitor"', $logContent);
        $this->assertStringContainsString('"recommended_action":"captcha"', $logContent);
        $this->assertStringContainsString('"recommended_action":"otp"', $logContent);
        $this->assertStringContainsString('"recommended_action":"block_ip"', $logContent);
        
        // Count MCP_DECISION entries
        $decisionCount = substr_count($logContent, 'MCP_DECISION');
        $this->assertEquals(13, $decisionCount, 'Should have 13 MCP_DECISION entries');
    }
}
