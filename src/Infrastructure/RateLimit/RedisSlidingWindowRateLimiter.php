<?php
declare(strict_types=1);

namespace Infrastructure\RateLimit;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Redis\Factory as RedisFactory;

final readonly class RedisSlidingWindowRateLimiter
{
    public function __construct(
        private RedisFactory $redisFactory,
        private LuaScriptResolver $luaScriptResolver,
        private ConfigRepository $config,
    ) {
    }

    public function attempt(string $key, int $maxRequests, int $windowSeconds, ?int $nowMs = null): SlidingWindowResult
    {
        $nowMs ??= (int) floor(microtime(true) * 1000);
        $windowMs = $windowSeconds * 1000;
        $member = sprintf('%d-%s', $nowMs, bin2hex(random_bytes(8)));

        /** @var array{0:int|string,1:int|string,2:int|string} $response */
        $response = $this->redis()
            ->eval(
                $this->luaScriptResolver->resolve('sliding_window_rate_limiter'),
                1,
                $key,
                (string) $nowMs,
                (string) $windowMs,
                (string) $maxRequests,
                $member,
            );

        $allowed = (int) $response[0] === 1;

        return new SlidingWindowResult(
            allowed: $allowed,
            remaining: max(0, (int) $response[1]),
            retryAfterSeconds: (int) ceil(((int) $response[2]) / 1000),
        );
    }

    private function redis(): mixed
    {
        return $this->redisFactory->connection(
            (string) $this->config->get('rate_limit.products.redis_connection', 'default'),
        );
    }
}
