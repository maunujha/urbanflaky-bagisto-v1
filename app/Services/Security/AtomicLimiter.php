<?php

namespace App\Services\Security;

use Illuminate\Support\Facades\Redis;

/**
 * All-or-nothing, multi-bucket counter reservation in a single Redis Lua call.
 *
 * Every bucket is checked first; only if ALL are under their limit are ALL
 * incremented. Because Redis runs a script atomically, concurrent requests
 * from any number of php-fpm workers cannot interleave between the check and
 * the increment, so a burst can never overshoot a limit. A rejected request
 * consumes nothing.
 */
class AtomicLimiter
{
    /*
     * KEYS[i]   = bucket key
     * ARGV[2i-1]= limit, ARGV[2i] = ttl seconds
     * Returns {0} on success, or {i, ttl} for the first exhausted bucket.
     */
    private const RESERVE = <<<'LUA'
for i = 1, #KEYS do
    local limit = tonumber(ARGV[2 * i - 1])
    local current = tonumber(redis.call('GET', KEYS[i]) or '0')
    if current >= limit then
        return {i, redis.call('TTL', KEYS[i])}
    end
end
for i = 1, #KEYS do
    local n = redis.call('INCR', KEYS[i])
    if n == 1 or redis.call('TTL', KEYS[i]) < 0 then
        redis.call('EXPIRE', KEYS[i], tonumber(ARGV[2 * i]))
    end
end
return {0}
LUA;

    /**
     * @param  array<string, array{key: string, limit: int, ttl: int}>  $buckets  name => spec
     * @return array{allowed: bool, bucket: ?string, retry_after: int}
     */
    public function reserve(array $buckets): array
    {
        $keys = [];
        $args = [];

        foreach ($buckets as $spec) {
            $keys[] = $this->key($spec['key']);
            $args[] = max(0, (int) $spec['limit']);
            $args[] = max(1, (int) $spec['ttl']);
        }

        $result = (array) $this->redis()->eval(self::RESERVE, count($keys), ...$keys, ...$args);

        $index = (int) ($result[0] ?? 0);

        if ($index === 0) {
            return ['allowed' => true, 'bucket' => null, 'retry_after' => 0];
        }

        return [
            'allowed'     => false,
            'bucket'      => array_keys($buckets)[$index - 1],
            'retry_after' => max(1, (int) ($result[1] ?? 60)),
        ];
    }

    /**
     * Atomically claim a one-shot marker (e.g. a used reCAPTCHA token).
     * True the first time, false for every later claim within the TTL.
     */
    public function claimOnce(string $key, int $ttl): bool
    {
        return (bool) $this->redis()->eval(
            "return redis.call('SET', KEYS[1], '1', 'NX', 'EX', tonumber(ARGV[1])) and 1 or 0",
            1,
            $this->key($key),
            max(1, $ttl)
        );
    }

    public function key(string $suffix): string
    {
        return config('otp_security.key_prefix').$suffix;
    }

    public function redis()
    {
        return Redis::connection(config('otp_security.redis_connection'));
    }
}
