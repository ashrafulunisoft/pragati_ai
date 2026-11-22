<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;
use App\Services\SecurityStore;
use App\Http\Middleware\McpMaliciousLoginMiddleware;

/**
 * Shared-NAT collateral-damage mitigation tests (Major-fix M9).
 *
 * When several DISTINCT accounts have successfully logged in from one IP
 * (office / campus / mobile NAT), blocking the whole IP would take out every
 * legitimate user behind it for the block TTL. Such IPs are now detected and
 * blocks there are scoped to the attacking ACCOUNT instead:
 *
 *   blocked:ip:{ip}    -> whole-IP block (default, no shared evidence)
 *   blocked:email:{e}  -> account-scoped block (shared NAT detected)
 */
class McpNatMitigationTest extends TestCase
{
    private string $sharedIp = '100.64.10.1';
    private string $victim   = 'victim@corp.com';
    private string $other    = 'office.worker@corp.com';

    /** @var string[] every key this test class can create */
    private array $legitEmails = ['office0@corp.com', 'office1@corp.com', 'office2@corp.com', 'solo@corp.com'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->clean();

        // Deterministic AI verdict: MALICIOUS -> block path
        Http::fake(['*' => Http::response(['choices' => [['message' => ['content' => json_encode([
            'decision' => 'MALICIOUS', 'attack_type' => 'brute_force',
            'risk_score' => 90, 'confidence' => 0.9,
        ])]]]], 200)]);
    }

    protected function tearDown(): void
    {
        $this->clean();
        parent::tearDown();
    }

    private function clean(): void
    {
        $keys = [
            "attack:login:ip:{$this->sharedIp}",
            "blocked:ip:{$this->sharedIp}",
            'mcp:degraded:ip:'.$this->sharedIp,
            'success:emails:ip:'.$this->sharedIp,
        ];
        foreach (array_merge([$this->victim, $this->other], $this->legitEmails) as $email) {
            $keys[] = "attack:login:email:{$email}";
            $keys[] = "blocked:email:{$email}";
        }
        SecurityStore::del($keys);
    }

    private function attempt(string $ip, string $email): \Symfony\Component\HttpFoundation\Response
    {
        $middleware = new McpMaliciousLoginMiddleware();

        $request = Request::create('/login', 'POST', ['email' => $email, 'password' => 'x'],
            [], [], ['REMOTE_ADDR' => $ip]);

        return $middleware->handle($request, fn () => response('ALLOWED'));
    }

    private function seedSharedNatEvidence(string $ip): void
    {
        foreach ($this->legitEmails as $legitEmail) {
            SecurityStore::sAddWithTtl("success:emails:ip:$ip", $legitEmail, 900);
        }
    }

    private function seedAttackerEvidence(string $ip, string $email): void
    {
        Redis::setex("attack:login:ip:$ip", 900, 10);
        Redis::setex("attack:login:email:$email", 900, 5);
    }

    /**
     * Shared NAT (>= threshold distinct successful accounts): the block lands
     * on the ATTACKING ACCOUNT, not the whole office IP.
     */
    public function test_shared_ip_blocks_account_not_the_whole_nat(): void
    {
        $this->seedSharedNatEvidence($this->sharedIp); // 4 distinct successful accounts
        $this->assertGreaterThanOrEqual(3, SecurityStore::scard("success:emails:ip:{$this->sharedIp}"));

        $this->seedAttackerEvidence($this->sharedIp, $this->victim);

        $response = $this->attempt($this->sharedIp, $this->victim);
        $this->assertSame(403, $response->getStatusCode());

        // Account-scoped block: the victim account is locked...
        $this->assertNotNull(SecurityStore::get("blocked:email:{$this->victim}"));
        // ...but the whole NAT is NOT
        $this->assertNull(SecurityStore::get("blocked:ip:{$this->sharedIp}"));
    }

    /**
     * The collateral-damage scenario that motivated this fix: while the
     * attacker's account is blocked, a DIFFERENT legitimate user behind the
     * same NAT still logs in fine.
     */
    public function test_legitimate_user_on_shared_nat_is_not_collateral(): void
    {
        $this->seedSharedNatEvidence($this->sharedIp);
        $this->seedAttackerEvidence($this->sharedIp, $this->victim);

        $this->attempt($this->sharedIp, $this->victim); // triggers account-scoped block
        $this->assertNotNull(SecurityStore::get("blocked:email:{$this->victim}"));

        // Legitimate user's earlier SUCCESS reset the shared IP counter
        SecurityStore::del(["attack:login:ip:{$this->sharedIp}"]);

        // Same IP, different account: must NOT be blocked
        $response = $this->attempt($this->sharedIp, $this->other);
        $this->assertSame('ALLOWED', $response->getContent());

        // ...while the attacker's own account IS rejected on retry
        $retry = $this->attempt($this->sharedIp, $this->victim);
        $this->assertSame(403, $retry->getStatusCode());
    }

    /**
     * No shared evidence (single-user IP): whole-IP blocking still applies,
     * so residential attacker IPs are blocked exactly as before.
     */
    public function test_unshared_ip_still_blocks_whole_ip(): void
    {
        $soloIp = '203.0.113.77';

        try {
            SecurityStore::sAddWithTtl("success:emails:ip:$soloIp", 'solo@corp.com', 900); // only 1 distinct
            $this->seedAttackerEvidence($soloIp, $this->victim);

            $response = $this->attempt($soloIp, $this->victim);
            $this->assertSame(403, $response->getStatusCode());
            $this->assertNotNull(SecurityStore::get("blocked:ip:$soloIp"));
            $this->assertNull(SecurityStore::get("blocked:email:{$this->victim}"));
        } finally {
            SecurityStore::del(["attack:login:ip:$soloIp", "blocked:ip:$soloIp", 'success:emails:ip:'.$soloIp, 'mcp:degraded:ip:'.$soloIp]);
        }
    }

    /**
     * The shared-IP evidence threshold itself is configurable.
     */
    public function test_shared_ip_threshold_is_configurable(): void
    {
        config(['services.minimax.shared_ip_success_threshold' => 1]);

        // a single successful account now marks the IP as shared
        SecurityStore::sAddWithTtl("success:emails:ip:{$this->sharedIp}", 'solo@corp.com', 900);
        $this->seedAttackerEvidence($this->sharedIp, $this->victim);

        $response = $this->attempt($this->sharedIp, $this->victim);
        $this->assertSame(403, $response->getStatusCode());
        $this->assertNotNull(SecurityStore::get("blocked:email:{$this->victim}"));
        $this->assertNull(SecurityStore::get("blocked:ip:{$this->sharedIp}"));

        config(['services.minimax.shared_ip_success_threshold' => 3]); // restore
    }

    /**
     * Blocked emails are rejected on the early check - no repeated (billable)
     * AI analysis for already-blocked identifiers.
     */
    public function test_blocked_email_early_check_skips_ai(): void
    {
        Http::fake(); // nothing should be sent

        SecurityStore::setex("blocked:email:{$this->victim}", 900, 1);

        $response = $this->attempt($this->sharedIp, $this->victim);

        $this->assertSame(403, $response->getStatusCode());
        Http::assertNothingSent();
    }
}
