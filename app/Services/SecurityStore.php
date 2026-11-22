<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Throwable;

/**
 * Redis-backed security state store with an emergency cache fallback
 * (Problem 2 fix: "Redis unavailable -> emergency rate-limit policy";
 * Major-fix M7: atomic counter/window updates via Lua).
 *
 * All security counters/blocks go through this class. If Redis is down
 * (connection error, timeout, ...) operations transparently fall back to the
 * Laravel cache so login protection degrades instead of throwing 500s.
 * A CRITICAL alert is logged once per process so monitoring can trip.
 *
 * Atomicity (M7): INCR + TTL arming executes as a single Lua script, so no
 * interleaving can leave a counter without a TTL, and the TTL is armed only
 * when the window actually starts (first increment) or when a legacy/no-TTL
 * key is detected (self-healing) - counters always decay.
 */
class SecurityStore
{
    /**
     * Test hook: force the cache fallback path (used by unit tests to exercise
     * the Redis-unavailable branch on a machine where Redis is up).
     */
    public static bool $forceCache = false;

    private static bool $redisAlerted = false;

    /**
     * Atomic INCR + window arming:
     *
     *   local count = INCR key
     *   if count == 1 or TTL(key) == -1 then EXPIRE key ttl end
     *   return count
     *
     * - single round trip, no INCR/EXPIRE race (M7)
     * - TTL armed only when the window opens (count == 1) -> true fixed window
     * - TTL(key) == -1 self-heals legacy keys created without a TTL
     */
    private const INCR_WITH_TTL_LUA = <<<'LUA'
local count = redis.call('INCR', KEYS[1])
if count == 1 or redis.call('TTL', KEYS[1]) == -1 then
    redis.call('EXPIRE', KEYS[1], ARGV[1])
end
return count
LUA;

    /**
     * Atomic SADD + TTL arming (distinct-value sets, e.g. successful emails
     * per IP for shared-NAT detection, M9). Returns the set cardinality.
     */
    private const SADD_WITH_TTL_LUA = <<<'LUA'
redis.call('SADD', KEYS[1], ARGV[1])
if redis.call('TTL', KEYS[1]) == -1 then
    redis.call('EXPIRE', KEYS[1], ARGV[2])
end
return redis.call('SCARD', KEYS[1])
LUA;

    /**
     * Get a raw value (null when missing).
     */
    public static function get(string $key): mixed
    {
        if (self::useRedis()) {
            try {
                return Redis::get($key);
            } catch (Throwable $e) {
                self::alertRedisDown($e);
            }
        }

        return Cache::get('security_store:'.$key);
    }

    /**
     * Set a key with a TTL (seconds).
     */
    public static function setex(string $key, int $ttl, mixed $value): void
    {
        if (self::useRedis()) {
            try {
                Redis::setex($key, $ttl, $value);

                return;
            } catch (Throwable $e) {
                self::alertRedisDown($e);
            }
        }

        Cache::put('security_store:'.$key, $value, now()->addSeconds($ttl));
    }

    /**
     * Increment a counter inside a fixed TTL window (atomic).
     * Returns the new counter value.
     */
    public static function incrWithTtl(string $key, int $ttl): int
    {
        if (self::useRedis()) {
            try {
                // Laravel normalises eval(script, numKeys, keys..., args...) for phpredis
                $count = Redis::eval(self::INCR_WITH_TTL_LUA, 1, $key, $ttl);

                return (int) $count;
            } catch (Throwable $e) {
                self::alertRedisDown($e);
            }
        }

        $count = (int) Cache::get('security_store:'.$key, 0) + 1;
        $expiresAt = Cache::get('security_store:ttl:'.$key);

        if ($expiresAt === null || now()->gte($expiresAt)) {
            Cache::put('security_store:ttl:'.$key, now()->addSeconds($ttl), now()->addSeconds($ttl + 3600));
            $count = 1; // window expired -> fresh counter
        }

        Cache::put('security_store:'.$key, $count, now()->addSeconds($ttl + 3600));

        return $count;
    }

    /**
     * Add a member to a TTL-bound set and return the set's cardinality.
     * (Used to track DISTINCT successful emails per IP -> shared-NAT evidence, M9.)
     */
    public static function sAddWithTtl(string $key, string $member, int $ttl): int
    {
        if (self::useRedis()) {
            try {
                return (int) Redis::eval(self::SADD_WITH_TTL_LUA, 1, $key, $member, $ttl);
            } catch (Throwable $e) {
                self::alertRedisDown($e);
            }
        }

        $set = (array) Cache::get('security_store:set:'.$key, []);
        $set[$member] = true;
        Cache::put('security_store:set:'.$key, $set, now()->addSeconds($ttl));

        return count($set);
    }

    /**
     * Cardinality of a TTL-bound set (0 when absent).
     */
    public static function scard(string $key): int
    {
        if (self::useRedis()) {
            try {
                return (int) Redis::scard($key);
            } catch (Throwable $e) {
                self::alertRedisDown($e);
            }
        }

        return count((array) Cache::get('security_store:set:'.$key, []));
    }

    /**
     * Delete one or more keys.
     */
    public static function del(array $keys): void
    {
        if (self::useRedis()) {
            try {
                Redis::del($keys);

                return;
            } catch (Throwable $e) {
                self::alertRedisDown($e);
            }
        }

        foreach ($keys as $key) {
            Cache::forget('security_store:'.$key);
            Cache::forget('security_store:ttl:'.$key);
            Cache::forget('security_store:set:'.$key);
        }
    }

    private static function useRedis(): bool
    {
        return !self::$forceCache;
    }

    private static function alertRedisDown(Throwable $e): void
    {
        if (!self::$redisAlerted) {
            Log::critical('[SecurityStore] Redis unavailable - using emergency cache fallback', [
                'error' => $e->getMessage(),
            ]);
            self::$redisAlerted = true;
        }
    }
}
