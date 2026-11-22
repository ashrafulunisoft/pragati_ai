<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;
use App\Services\SecurityStore;
use App\Http\Middleware\McpMaliciousLoginMiddleware;

/**
 * CAPTCHA/OTP step-up enforcement tests (Major-fix M10).
 *
 * recommended_action values `captcha` (band 30-49) and `otp` (band 50-79)
 * are now ENFORCED, not just logged: the middleware answers such logins with
 * HTTP 428 + a single-use challenge (server-generated math captcha, or a
 * 6-digit OTP delivered via log/mail). A request carrying a valid challenge
 * response passes; scripted attack traffic cannot.
 */
class McpChallengeEnforcementTest extends TestCase
{
    private string $ip = '192.168.88.10';
    private string $email = 'challenge@test.com';

    protected function setUp(): void
    {
        parent::setUp();
        $this->clean();

        // Deterministic AI: lenient SAFE without a risk_score - the rule
        // floor decides the band (30 -> captcha, 55 -> otp)
        Http::fake(['*' => Http::response(['choices' => [['message' => ['content' => json_encode([
            'decision' => 'SAFE', 'confidence' => 0.5,
        ])]]]], 200)]);
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
            'challenge:issued:ip:'.$this->ip,
            'success:emails:ip:'.$this->ip,
        ]);
    }

    private function attempt(array $extra = []): \Symfony\Component\HttpFoundation\Response
    {
        $middleware = new McpMaliciousLoginMiddleware();

        $request = Request::create('/login', 'POST',
            array_merge(['email' => $this->email, 'password' => 'x'], $extra),
            [], [], ['REMOTE_ADDR' => $this->ip]);

        return $middleware->handle($request, fn () => response('ALLOWED'));
    }

    private function lastOtpCode(): ?string
    {
        $log = (string) @file_get_contents(storage_path('logs/mcp_security.log'));
        if (!preg_match_all('/OTP_ISSUED.*?"code":"(\d{6})"/', $log, $m)) {
            return null;
        }

        return $m[1][count($m[1]) - 1];
    }

    /*
     * OTP band (risk 50-79)
     */

    public function test_medium_risk_requires_otp_challenge(): void
    {
        Redis::setex("attack:login:ip:{$this->ip}", 900, 5);
        Redis::setex("attack:login:email:{$this->email}", 900, 3); // score 55 -> otp

        $response = $this->attempt();
        $body = json_decode($response->getContent(), true);

        $this->assertSame(428, $response->getStatusCode());
        $this->assertSame('otp', $body['required_action']);
        $this->assertNotEmpty($body['challenge']['token']);
        $this->assertArrayHasKey('hint', $body['challenge']);
        // Not a block: no blocked keys were created
        $this->assertNull(SecurityStore::get("blocked:ip:{$this->ip}"));
        $this->assertNull(SecurityStore::get("blocked:email:{$this->email}"));
    }

    public function test_correct_otp_answer_passes(): void
    {
        Redis::setex("attack:login:ip:{$this->ip}", 900, 5);
        Redis::setex("attack:login:email:{$this->email}", 900, 3);

        $first = json_decode($this->attempt()->getContent(), true);
        $token = $first['challenge']['token'];
        $code  = $this->lastOtpCode();

        $this->assertNotNull($code, 'OTP code should be delivered to the mcp_security log');

        $solved = $this->attempt([
            'security_challenge_token'  => $token,
            'security_challenge_answer' => $code,
        ]);

        $this->assertSame('ALLOWED', $solved->getContent());
    }

    public function test_wrong_otp_answer_is_rejected(): void
    {
        Redis::setex("attack:login:ip:{$this->ip}", 900, 5);
        Redis::setex("attack:login:email:{$this->email}", 900, 3);

        $first  = json_decode($this->attempt()->getContent(), true);
        $second = $this->attempt([
            'security_challenge_token'  => $first['challenge']['token'],
            'security_challenge_answer' => '000000',
        ]);

        $this->assertSame(428, $second->getStatusCode());
        $body = json_decode($second->getContent(), true);
        $this->assertSame('The verification response was invalid or expired.', $body['detail'] ?? '');
    }

    /*
     * CAPTCHA band (risk 30-49)
     */

    public function test_captcha_band_issues_math_challenge_and_solves(): void
    {
        Redis::setex("attack:login:ip:{$this->ip}", 900, 5); // score 30 -> captcha
        Redis::del(["attack:login:email:{$this->email}"]);

        $first = json_decode($this->attempt()->getContent(), true);

        $this->assertSame('captcha', $first['required_action']);
        $this->assertMatchesRegularExpression('/^\d{2} \+ \d{1,2} = \?$/', $first['challenge']['question']);

        // Solve the question
        [$a, $b] = sscanf($first['challenge']['question'], '%d + %d = ?');
        $solved = $this->attempt([
            'security_challenge_token'  => $first['challenge']['token'],
            'security_challenge_answer' => (string) ($a + $b),
        ]);

        $this->assertSame('ALLOWED', $solved->getContent());
    }

    /*
     * Token hygiene: single use + capped attempts
     */

    public function test_challenge_token_is_single_use(): void
    {
        Redis::setex("attack:login:ip:{$this->ip}", 900, 5);

        $first = json_decode($this->attempt()->getContent(), true);
        [$a, $b] = sscanf($first['challenge']['question'], '%d + %d = ?');
        $answer = (string) ($a + $b);

        $solved = $this->attempt([
            'security_challenge_token'  => $first['challenge']['token'],
            'security_challenge_answer' => $answer,
        ]);
        $this->assertSame('ALLOWED', $solved->getContent());

        // REPLAY: the token was consumed -> a fresh challenge is demanded
        $replay = $this->attempt([
            'security_challenge_token'  => $first['challenge']['token'],
            'security_challenge_answer' => $answer,
        ]);
        $this->assertSame(428, $replay->getStatusCode());
        $this->assertNotSame($first['challenge']['token'],
            json_decode($replay->getContent(), true)['challenge']['token']);
    }

    public function test_challenge_has_capped_attempts(): void
    {
        Redis::setex("attack:login:ip:{$this->ip}", 900, 5);

        $first  = json_decode($this->attempt()->getContent(), true);
        $token  = $first['challenge']['token'];
        [$a, $b] = sscanf($first['challenge']['question'], '%d + %d = ?');
        $correct = (string) ($a + $b);

        // burn the configured 3 attempts with wrong answers
        foreach ([0, 1, 2] as $i) {
            $r = $this->attempt([
                'security_challenge_token'  => $token,
                'security_challenge_answer' => '999',
            ]);
            $this->assertSame(428, $r->getStatusCode());
        }

        // correct answer no longer helps - the token was invalidated
        $late = $this->attempt([
            'security_challenge_token'  => $token,
            'security_challenge_answer' => $correct,
        ]);
        $this->assertSame(428, $late->getStatusCode());
    }

    /*
     * Guardrails
     */

    public function test_malicious_verdict_blocks_instead_of_challenging(): void
    {
        Http::fake(['*' => Http::response(['choices' => [['message' => ['content' => json_encode([
            'decision' => 'MALICIOUS', 'risk_score' => 90, 'confidence' => 0.9,
        ])]]]], 200)]);

        Redis::setex("attack:login:ip:{$this->ip}", 900, 10);
        Redis::setex("attack:login:email:{$this->email}", 900, 5);

        $response = $this->attempt();

        $this->assertSame(403, $response->getStatusCode());
        $this->assertNotNull(SecurityStore::get("blocked:ip:{$this->ip}"));
    }

    public function test_challenges_can_be_disabled_by_config(): void
    {
        config(['services.minimax.challenge_enabled' => false]);

        Redis::setex("attack:login:ip:{$this->ip}", 900, 5);
        Redis::setex("attack:login:email:{$this->email}", 900, 3);

        $response = $this->attempt();
        $this->assertSame('ALLOWED', $response->getContent());
    }

    public function test_challenge_issuance_is_rate_limited_per_ip(): void
    {
        config(['services.minimax.challenge_max_per_ip' => 3]);

        Redis::setex("attack:login:ip:{$this->ip}", 900, 5);

        for ($i = 0; $i < 3; $i++) {
            $this->assertSame(428, $this->attempt()->getStatusCode());
        }

        // 4th challenge request from the same IP -> throttled
        $this->assertSame(429, $this->attempt()->getStatusCode());
    }

    public function test_degraded_mode_does_not_challenge(): void
    {
        // AI down + medium risk -> emergency_throttle N-strike path, not challenges
        Http::fake(fn () => throw new \Exception('ai down'));

        Redis::setex("attack:login:ip:{$this->ip}", 900, 5);
        Redis::setex("attack:login:email:{$this->email}", 900, 3);

        $response = $this->attempt(); // strike 1
        $this->assertSame('ALLOWED', $response->getContent());
    }

    /**
     * Comment 3 requirement: verify that EVERY SUSPICIOUS outcome triggers
     * the planned challenge (HTTP 428) and NEVER returns 200 Allow when no token is presented.
     */
    public function test_suspicious_verdict_always_enforces_challenge_and_never_returns_allow_without_token(): void
    {
        // 1. Rule score 55 (medium band) with neutral AI -> SUSPICIOUS / otp -> 428
        Redis::setex("attack:login:ip:{$this->ip}", 900, 5);
        Redis::setex("attack:login:email:{$this->email}", 900, 3);

        $response = $this->attempt();
        $this->assertSame(428, $response->getStatusCode());
        $this->assertNotSame('ALLOWED', $response->getContent());

        // 2. F3 bounded leniency (rule score 55, AI confident SAFE) -> SUSPICIOUS -> 428
        Http::fake(['*' => Http::response(['choices' => [['message' => ['content' => json_encode([
            'decision' => 'SAFE', 'confidence' => 0.95, 'risk_score' => 60,
        ])]]]], 200)]);

        $response2 = $this->attempt();
        $this->assertSame(428, $response2->getStatusCode());
        $this->assertNotSame('ALLOWED', $response2->getContent());
    }
}
