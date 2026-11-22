<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use App\Services\SecurityStore;
use App\Http\Middleware\McpMaliciousLoginMiddleware;

/**
 * Below-threshold joint rotation evaluation tests (Reviewer Comment 2).
 *
 * Verifies the escalation gate behaviour of Algorithm 1:
 *  1. Joint IP + account rotation staying strictly below theta_ip (5) and
 *     theta_email (3) never escalates to AI (0 invocations, 100% non-escalated).
 *  2. Distributed stuffing across many IPs targeting a single account
 *     escalates as soon as per-account failure count reaches theta_email (3).
 *  3. Security evaluation harness runs the below_threshold_rotation scenario cleanly.
 */
class McpBelowThresholdRotationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->cleanRedis();
    }

    protected function tearDown(): void
    {
        $this->cleanRedis();
        parent::tearDown();
    }

    private function cleanRedis(): void
    {
        for ($i = 0; $i < 20; $i++) {
            $ip = "198.51.100." . (100 + $i);
            $email = "belowrot{$i}@corp.com";
            SecurityStore::del([
                "attack:login:ip:{$ip}",
                "attack:login:email:{$email}",
                "blocked:ip:{$ip}",
                "blocked:email:{$email}",
            ]);
        }
        SecurityStore::del([
            'attack:login:email:single_target@corp.com',
            'blocked:email:single_target@corp.com',
        ]);
    }

    public function test_joint_rotation_below_thresholds_never_invokes_ai_and_is_not_blocked(): void
    {
        Http::fake();

        $middleware = new McpMaliciousLoginMiddleware();
        $ips = ['198.51.100.101', '198.51.100.102', '198.51.100.103', '198.51.100.104', '198.51.100.105'];
        $emails = [
            'belowrot0@corp.com',
            'belowrot1@corp.com',
            'belowrot2@corp.com',
            'belowrot3@corp.com',
            'belowrot4@corp.com',
        ];

        // 5 IPs x 2 attempts each = 10 attempts
        // Each IP makes 2 attempts (< 5)
        // Each email receives 2 attempts (< 3)
        $events = [];
        for ($i = 0; $i < 5; $i++) {
            $events[] = [$ips[$i], $emails[$i]];
            $events[] = [$ips[$i], $emails[($i + 1) % 5]];
        }

        foreach ($events as [$ip, $email]) {
            $ipKey = "attack:login:ip:{$ip}";
            $emailKey = "attack:login:email:{$email}";

            $request = Request::create('/login', 'POST', [
                'email' => $email,
                'password' => 'wrong-pass',
            ], [], [], ['REMOTE_ADDR' => $ip]);

            $response = $middleware->handle($request, fn () => response('ALLOWED', 200));

            // Under threshold -> never blocked by middleware gate
            $this->assertSame(200, $response->getStatusCode());
            $this->assertSame('ALLOWED', $response->getContent());

            // Controller increments failure counter on failed attempt
            SecurityStore::incrWithTtl($ipKey, 900);
            SecurityStore::incrWithTtl($emailKey, 900);
        }

        // Verify that LLM was NEVER invoked (0 HTTP requests sent)
        Http::assertNothingSent();

        // Verify that no IP or account got blocked
        foreach ($ips as $ip) {
            $this->assertNull(SecurityStore::get("blocked:ip:{$ip}"));
            $this->assertLessThan(5, (int) SecurityStore::get("attack:login:ip:{$ip}"));
        }
        foreach ($emails as $email) {
            $this->assertNull(SecurityStore::get("blocked:email:{$email}"));
            $this->assertLessThan(3, (int) SecurityStore::get("attack:login:email:{$email}"));
        }
    }

    public function test_distributed_stuffing_escalates_when_per_account_volume_crosses_threshold(): void
    {
        Http::fake([
            '*' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => json_encode([
                            'decision' => 'MALICIOUS',
                            'attack_type' => 'credential_stuffing',
                            'risk_score' => 95,
                            'confidence' => 0.95,
                            'recommended_action' => 'block_email',
                        ]),
                    ],
                ]],
            ], 200),
        ]);

        $middleware = new McpMaliciousLoginMiddleware();
        $targetEmail = 'single_target@corp.com';
        $emailKey = "attack:login:email:{$targetEmail}";

        // 4 different bots (IPs) attack the same email
        for ($b = 1; $b <= 4; $b++) {
            $ip = "198.51.100.1" . str_pad((string) $b, 2, '0', STR_PAD_LEFT);
            $ipKey = "attack:login:ip:{$ip}";

            $request = Request::create('/login', 'POST', [
                'email' => $targetEmail,
                'password' => 'guess-pass',
            ], [], [], ['REMOTE_ADDR' => $ip]);

            $response = $middleware->handle($request, fn () => response('ALLOWED', 200));

            if ($b <= 3) {
                // Attempts 1, 2, 3: email attempt counter in Redis is 0, 1, 2 (all < 3)
                $this->assertSame(200, $response->getStatusCode());
                // Controller logs failure
                SecurityStore::incrWithTtl($ipKey, 900);
                SecurityStore::incrWithTtl($emailKey, 900);
            } else {
                // Attempt 4: email counter is 3 (>= theta_email = 3) -> ESCALATES TO AI!
                $this->assertSame(403, $response->getStatusCode());
            }
        }

        // At attempt 4, the email threshold was crossed -> AI was called
        Http::assertSentCount(1);
    }

    public function test_security_evaluate_command_below_threshold_rotation_scenario(): void
    {
        $code = Artisan::call('security:evaluate', [
            '--ai' => 'off',
            '--scenario' => 'below_threshold_rotation',
            '--seed' => 42,
        ]);

        $this->assertSame(0, $code);
        $output = Artisan::output();
        $this->assertStringContainsString('below_threshold_rotation', $output);
        $this->assertStringContainsString('100.0%', $output);
    }
}
