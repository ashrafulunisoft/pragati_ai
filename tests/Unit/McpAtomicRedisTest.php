<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Support\Facades\Redis;
use App\Services\SecurityStore;

/**
 * Atomicity + fixed-window tests for SecurityStore (Major-fix M7).
 *
 * INCR and TTL-arming execute as ONE Lua script:
 *  - counters can never be observed/lost between INCR and EXPIRE
 *  - the TTL is armed ONLY when the window opens (first increment),
 *    so sustained attacks still decay after one full window
 *  - legacy keys without a TTL are self-healed on the next increment
 */
class McpAtomicRedisTest extends TestCase
{
    private string $key = 'atomic:test:counter';

    protected function tearDown(): void
    {
        SecurityStore::del([$this->key, 'atomic:test:set']);
        SecurityStore::$forceCache = false;
        parent::tearDown();
    }

    public function test_atomic_counter_counts_and_arms_ttl(): void
    {
        $this->assertSame(1, SecurityStore::incrWithTtl($this->key, 900));
        $this->assertSame(2, SecurityStore::incrWithTtl($this->key, 900));
        $this->assertSame(3, SecurityStore::incrWithTtl($this->key, 900));

        $this->assertSame(3, (int) Redis::get($this->key));
        $ttl = Redis::ttl($this->key);
        $this->assertGreaterThan(0, $ttl);
        $this->assertLessThanOrEqual(900, $ttl);
    }

    /**
     * THE old defect: EXPIRE was re-issued on every failure, so under
     * sustained attack the counter NEVER decayed (rolling-TTL accumulator).
     * Now: a manual TTL change survives further increments within the window.
     */
    public function test_ttl_is_not_reset_by_subsequent_increments(): void
    {
        SecurityStore::incrWithTtl($this->key, 900);   // window opens, TTL=900
        Redis::expire($this->key, 60);                 // simulate window aging

        SecurityStore::incrWithTtl($this->key, 900);   // must NOT re-arm

        $this->assertLessThanOrEqual(60, Redis::ttl($this->key));
        $this->assertGreaterThan(0, Redis::ttl($this->key));
    }

    /**
     * Self-heal: a counter key left WITHOUT a TTL (legacy crash between the
     * old separate INCR/EXPIRE calls) gets a TTL on its next increment, so
     * no immortal counters can survive the migration.
     */
    public function test_legacy_no_ttl_key_is_self_healed(): void
    {
        Redis::set($this->key, 41);                    // no TTL (legacy state)
        $this->assertSame(-1, Redis::ttl($this->key));

        $count = SecurityStore::incrWithTtl($this->key, 900);

        $this->assertSame(42, $count);
        $this->assertGreaterThan(0, Redis::ttl($this->key));
    }

    /**
     * Distinct-member set (shared-NAT evidence, M9) counts DISTINCT values
     * and arms a TTL on creation.
     */
    public function test_set_add_counts_distinct_members(): void
    {
        $key = 'atomic:test:set';

        $this->assertSame(1, SecurityStore::sAddWithTtl($key, 'a@corp.com', 900));
        $this->assertSame(1, SecurityStore::sAddWithTtl($key, 'a@corp.com', 900)); // duplicate ignored
        $this->assertSame(2, SecurityStore::sAddWithTtl($key, 'b@corp.com', 900));
        $this->assertSame(3, SecurityStore::sAddWithTtl($key, 'c@corp.com', 900));

        $this->assertSame(3, SecurityStore::scard($key));
        $this->assertGreaterThan(0, Redis::ttl($key));
    }

    /**
     * The cache fallback keeps the same fixed-window semantics when Redis
     * is unavailable (window expiry resets the counter, TTL never re-armed).
     */
    public function test_cache_fallback_fixed_window_semantics(): void
    {
        SecurityStore::$forceCache = true;

        $this->assertSame(1, SecurityStore::incrWithTtl($this->key, 900));
        $this->assertSame(2, SecurityStore::incrWithTtl($this->key, 900));
        $this->assertSame(2, (int) SecurityStore::get($this->key));

        SecurityStore::setex('blocked:email:x@y.com', 60, 1);
        $this->assertSame(1, SecurityStore::get('blocked:email:x@y.com'));
    }
}
