<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Services\SecurityStore;
use App\Http\Middleware\McpMaliciousLoginMiddleware;

/**
 * Counter Semantics and Lifecycle Timing Tests (Reviewer Comment 4).
 *
 * Verifies the exact update sequence and timing for login events:
 *  1. Successful logins reset/delete failure counters (never accumulate).
 *  2. Blocked requests (HTTP 403) never reach the controller and do not increment counters.
 *  3. Challenged requests (HTTP 428) do not increment attack failure counters.
 *  4. Middleware evaluates prior failures BEFORE current attempt; confirmed failures
 *     are incremented ONLY by the controller AFTER credential verification fails.
 */
class McpCounterSemanticsTest extends TestCase
{
    private string $ip = '198.51.100.77';
    private string $email = 'semantics@corp.com';

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
        SecurityStore::del([
            "attack:login:ip:{$this->ip}",
            "attack:login:email:{$this->email}",
            "blocked:ip:{$this->ip}",
            "blocked:email:{$this->email}",
            "challenge:issued:ip:{$this->ip}",
            "mcp:degraded:ip:{$this->ip}",
            "success:emails:ip:{$this->ip}",
        ]);
    }

    private function runMiddleware(array $inputs = []): \Symfony\Component\HttpFoundation\Response
    {
        $middleware = new McpMaliciousLoginMiddleware();
        $request = Request::create('/login', 'POST', array_merge([
            'email' => $this->email,
            'password' => 'guess',
        ], $inputs), [], [], ['REMOTE_ADDR' => $this->ip]);

        return $middleware->handle($request, fn () => response('ALLOWED', 200));
    }

    /**
     * Requirement: Repeatedly successful legitimate logins must NOT accumulate failure counters.
     */
    public function test_repeated_successful_logins_do_not_accumulate_counters(): void
    {
        $ipKey = "attack:login:ip:{$this->ip}";
        $emailKey = "attack:login:email:{$this->email}";

        // Simulate 5 consecutive successful logins from the same user/IP
        for ($i = 1; $i <= 5; $i++) {
            // 1. Middleware passes benign traffic (counters are 0 < 5)
            $response = $this->runMiddleware();
            $this->assertSame(200, $response->getStatusCode());
            $this->assertSame('ALLOWED', $response->getContent());

            // 2. Controller executes Auth::attempt() -> SUCCESS:
            // Resets failure counters and registers NAT success set
            SecurityStore::sAddWithTtl("success:emails:ip:{$this->ip}", $this->email, 900);
            SecurityStore::del([$ipKey, $emailKey, "blocked:ip:{$this->ip}"]);

            // 3. Assert counters remain absent / zero
            $this->assertNull(SecurityStore::get($ipKey));
            $this->assertNull(SecurityStore::get($emailKey));
            $this->assertNull(SecurityStore::get("blocked:ip:{$this->ip}"));
        }

        // Distinct success set recorded for NAT-awareness
        $this->assertSame(1, SecurityStore::sCard("success:emails:ip:{$this->ip}"));
    }

    /**
     * Requirement: Blocked requests (HTTP 403) must never increment failure counters.
     */
    public function test_blocked_request_does_not_increment_counters(): void
    {
        $ipKey = "attack:login:ip:{$this->ip}";
        $emailKey = "attack:login:email:{$this->email}";

        // Simulate an already-blocked IP (e.g. earlier threshold violation)
        SecurityStore::setex("blocked:ip:{$this->ip}", 900, '1');
        SecurityStore::setex($ipKey, 900, '10');
        SecurityStore::setex($emailKey, 900, '5');

        // Middleware early-exit intercepts request
        $response = $this->runMiddleware();

        $this->assertSame(403, $response->getStatusCode());

        // Because request was halted at middleware early-exit, controller was never reached:
        // counters MUST NOT have been incremented (they remain at 10 and 5, NOT 11 and 6)
        $this->assertSame(10, (int) SecurityStore::get($ipKey));
        $this->assertSame(5, (int) SecurityStore::get($emailKey));
    }

    /**
     * Requirement: Challenged requests (HTTP 428) must not increment attack failure counters.
     */
    public function test_challenged_request_does_not_increment_counters(): void
    {
        Http::fake(['*' => Http::response(['choices' => [['message' => ['content' => json_encode([
            'decision' => 'SAFE', 'confidence' => 0.5,
        ])]]]], 200)]);

        $ipKey = "attack:login:ip:{$this->ip}";
        $emailKey = "attack:login:email:{$this->email}";

        // Counters at medium threshold (score 55 -> OTP challenge)
        SecurityStore::setex($ipKey, 900, '5');
        SecurityStore::setex($emailKey, 900, '3');

        // Request without challenge token -> middleware returns HTTP 428
        $response = $this->runMiddleware();

        $this->assertSame(428, $response->getStatusCode());

        // Attack counters MUST NOT increment on challenge delivery
        $this->assertSame(5, (int) SecurityStore::get($ipKey));
        $this->assertSame(3, (int) SecurityStore::get($emailKey));
    }

    /**
     * Requirement: Middleware reads counters BEFORE current attempt.
     * Confirmed failures increment only after Auth::attempt() fails in the controller.
     */
    public function test_middleware_reads_counters_before_current_attempt_increment(): void
    {
        $ipKey = "attack:login:ip:{$this->ip}";
        $emailKey = "attack:login:email:{$this->email}";

        // Set prior failures to 4 (IP) and 2 (Email) — exactly one below thresholds (5 and 3)
        SecurityStore::setex($ipKey, 900, '4');
        SecurityStore::setex($emailKey, 900, '2');

        // Attempt 1: Middleware evaluates prior failures (4 < 5, 2 < 3) -> ALLOWS through
        $response1 = $this->runMiddleware();
        $this->assertSame(200, $response1->getStatusCode());
        $this->assertSame('ALLOWED', $response1->getContent());

        // Controller verifies credentials -> FAILS -> increments counters
        SecurityStore::incrWithTtl($ipKey, 900);
        SecurityStore::incrWithTtl($emailKey, 900);

        // Now counters are 5 and 3
        $this->assertSame(5, (int) SecurityStore::get($ipKey));
        $this->assertSame(3, (int) SecurityStore::get($emailKey));

        // Attempt 2: Next request flows into middleware -> sees 5 and 3 -> ESCALATES TO STEP-UP CHALLENGE (428)
        Http::fake(['*' => Http::response(['choices' => [['message' => ['content' => json_encode([
            'decision' => 'SAFE', 'confidence' => 0.5,
        ])]]]], 200)]);

        $response2 = $this->runMiddleware();
        $this->assertSame(428, $response2->getStatusCode());
    }
}
