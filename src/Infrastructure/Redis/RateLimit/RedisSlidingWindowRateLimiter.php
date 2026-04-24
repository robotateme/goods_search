<?php

declare(strict_types=1);

namespace Infrastructure\Redis\RateLimit;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Illuminate\Redis\Connections\Connection;
use Infrastructure\Redis\ScriptResolver;

final readonly class RedisSlidingWindowRateLimiter
{
    public function __construct(
        private RedisFactory $redisFactory,
        private ScriptResolver $scriptResolver,
        private ConfigRepository $config,
    ) {}

    public function attempt(string $key, int $maxRequests, int $windowSeconds, ?int $nowMs = null): SlidingWindowResult
    {
        $nowMs ??= (int) floor(microtime(true) * 1000);
        $windowMs = $windowSeconds * 1000;
        $member = sprintf('%d-%s', $nowMs, bin2hex(random_bytes(8)));

        /** @var array{0:int|string,1:int|string,2:int|string} $response */
        $response = $this->redis()
            ->command('eval', [
                $this->scriptResolver->resolve('rate-limit/sliding_window_rate_limiter.lua'),
                1,
                $key,
                (string) $nowMs,
                (string) $windowMs,
                (string) $maxRequests,
                $member,
            ]);

        $allowed = (int) $response[0] === 1;

        return new SlidingWindowResult(
            allowed: $allowed,
            remaining: max(0, (int) $response[1]),
            retryAfterSeconds: (int) ceil(((int) $response[2]) / 1000),
        );
    }

    private function redis(): Connection
    {
        return $this->redisFactory->connection($this->connectionName());
    }

    private function connectionName(): string
    {
        $connection = $this->config->get('rate_limit.redis_connection', 'default');

        if (! is_string($connection)) {
            return 'default';
        }

        return $connection;
    }
}
