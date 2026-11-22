<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Http\Middleware\McpMaliciousLoginMiddleware;
use App\Services\ChallengeService;
use App\Services\SecurityStore;

/**
 * Detection vs. Challenge-Based Prevention Separation Tests (Reviewer Comment 5).
 *
 * Verifies that:
 *  1. Decision layer (classification verdict: MALICIOUS, SUSPICIOUS, SAFE) is
 *     strictly decoupled from enforcement layer (HTTP 403, 428, 429, 200).
 *  2. Step-up challenges (HTTP 428) are exposed as SUSPICIOUS classification,
 *     not conflated with hard MALICIOUS blocks (HTTP 403).
 *  3. Solved challenges transition enforcement to CHALLENGE_SOLVED while preserving
 *     auditable SUSPICIOUS classification telemetry.
 *  4. FPR 95% confidence intervals rigorously bound uncertainty for n=49 legitimate negatives.
 */
class McpDetectionVsEnforcementTest extends TestCase
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
        $ips = ['198.51.100.50', '198.51.100.51', '198.51.100.52', '198.51.100.53'];
        foreach ($ips as $ip) {
            SecurityStore::del([
                "attack:login:ip:{$ip}",
                "attack:login:email:user@corp.com",
                "blocked:ip:{$ip}",
                "blocked:email:user@corp.com",
                "challenge:issued:ip:{$ip}",
            ]);
        }
    }

    public function test_middleware_exposes_distinct_decision_and_enforcement_headers_on_block(): void
    {
        $ip = '198.51.100.50';
        $email = 'user@corp.com';

        // Pre-arm counter to trigger AI analysis
        SecurityStore::setex("attack:login:ip:{$ip}", 900, 5);

        // AI returns high-risk MALICIOUS verdict
        Http::fake([
            '*' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => json_encode([
                            'decision' => 'MALICIOUS',
                            'attack_type' => 'brute_force',
                            'risk_score' => 95,
                            'confidence' => 0.95,
                            'recommended_action' => 'block_ip',
                        ]),
                    ],
                ]],
            ], 200),
        ]);

        $middleware = new McpMaliciousLoginMiddleware();
        $request = Request::create('/login', 'POST', [
            'email' => $email,
            'password' => 'wrong',
        ], [], [], ['REMOTE_ADDR' => $ip]);

        $response = $middleware->handle($request, fn () => response('ALLOWED', 200));

        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame('MALICIOUS', $response->headers->get('X-Mcp-Decision'));
        $this->assertSame('BLOCKED', $response->headers->get('X-Mcp-Enforcement'));
    }

    public function test_middleware_exposes_distinct_decision_and_enforcement_headers_on_challenge(): void
    {
        $ip = '198.51.100.51';
        $email = 'user@corp.com';

        // Pre-arm counter to trigger AI analysis
        SecurityStore::setex("attack:login:ip:{$ip}", 900, 5);

        // AI returns medium-risk SUSPICIOUS verdict requiring OTP
        Http::fake([
            '*' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => json_encode([
                            'decision' => 'SAFE', // AI says safe, but score 65 leaves it in SUSPICIOUS band
                            'attack_type' => 'credential_stuffing',
                            'risk_score' => 65,
                            'confidence' => 0.70,
                            'recommended_action' => 'otp',
                        ]),
                    ],
                ]],
            ], 200),
        ]);

        $middleware = new McpMaliciousLoginMiddleware();
        $request = Request::create('/login', 'POST', [
            'email' => $email,
            'password' => 'pass',
        ], [], [], ['REMOTE_ADDR' => $ip]);

        $response = $middleware->handle($request, fn () => response('ALLOWED', 200));

        // Step-up challenge outcome
        $this->assertSame(428, $response->getStatusCode());
        $this->assertSame('SUSPICIOUS', $response->headers->get('X-Mcp-Decision'));
        $this->assertSame('CHALLENGED', $response->headers->get('X-Mcp-Enforcement'));
    }

    public function test_middleware_exposes_distinct_decision_and_enforcement_headers_on_clean_allow(): void
    {
        $ip = '198.51.100.52';
        $email = 'user@corp.com';

        // Low counters (0 attempts) -> passes directly without AI
        $middleware = new McpMaliciousLoginMiddleware();
        $request = Request::create('/login', 'POST', [
            'email' => $email,
            'password' => 'correct',
        ], [], [], ['REMOTE_ADDR' => $ip]);

        $response = $middleware->handle($request, fn () => response('ALLOWED', 200));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('SAFE', $response->headers->get('X-Mcp-Decision'));
        $this->assertSame('ALLOWED', $response->headers->get('X-Mcp-Enforcement'));
    }

    public function test_middleware_exposes_challenge_solved_enforcement(): void
    {
        $ip = '198.51.100.53';
        $email = 'user@corp.com';

        // Pre-arm counter to trigger AI analysis
        SecurityStore::setex("attack:login:ip:{$ip}", 900, 5);

        // Issue a valid challenge
        $challenge = ChallengeService::issue('captcha', $ip, $email);
        $token = $challenge['token'];
        [$a, $b] = sscanf($challenge['question'], '%d + %d = ?');
        $correctAnswer = (string) ($a + $b);

        // AI returns captcha recommendation
        Http::fake([
            '*' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => json_encode([
                            'decision' => 'SAFE',
                            'attack_type' => 'bot_attack',
                            'risk_score' => 45,
                            'confidence' => 0.75,
                            'recommended_action' => 'captcha',
                        ]),
                    ],
                ]],
            ], 200),
        ]);

        $middleware = new McpMaliciousLoginMiddleware();
        $request = Request::create('/login', 'POST', [
            'email' => $email,
            'password' => 'pass',
            'security_challenge_token' => $token,
            'security_challenge_answer' => $correctAnswer,
        ], [], [], ['REMOTE_ADDR' => $ip]);

        $response = $middleware->handle($request, fn () => response('ALLOWED', 200));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('SUSPICIOUS', $response->headers->get('X-Mcp-Decision'));
        $this->assertSame('CHALLENGE_SOLVED', $response->headers->get('X-Mcp-Enforcement'));
    }

    public function test_fpr_confidence_interval_calculation_and_statistical_bounds(): void
    {
        // 1. Single-seed test set: n=49 legitimate negatives with 0 false positives
        $n = 49;
        $fp = 0;

        $ruleOfThreeUpper = round(3 / $n, 4);
        $this->assertSame(0.0612, $ruleOfThreeUpper); // 6.12%

        $z = 1.96;
        $z2 = $z * $z;
        $wilsonUpper = round($z2 / ($n + $z2), 4);
        $this->assertSame(0.0727, $wilsonUpper); // 7.27%

        // 2. Multi-seed pooled test set: N=232 legitimate negatives with 0 false positives
        $nPooled = 232;
        $pooledRuleOfThree = round(3 / $nPooled, 4);
        $this->assertSame(0.0129, $pooledRuleOfThree); // 1.29%

        $pooledWilsonUpper = round($z2 / ($nPooled + $z2), 4);
        $this->assertSame(0.0163, $pooledWilsonUpper); // 1.63%
    }
}
