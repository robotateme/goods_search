<?php
declare(strict_types=1);

namespace Tests\Unit\Infrastructure\RateLimit;

use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Illuminate\Redis\Connections\Connection;
use Infrastructure\RateLimit\LuaScriptResolver;
use Infrastructure\RateLimit\RedisSlidingWindowRateLimiter;
use PHPUnit\Framework\TestCase;

class RedisSlidingWindowRateLimiterTest extends TestCase
{
    public function test_it_maps_lua_response_to_domain_result(): void
    {
        $connection = new FakeRedisConnection([1, 57, 0]);
        $factory = new FakeRedisFactory($connection);

        $limiter = new RedisSlidingWindowRateLimiter($factory, new LuaScriptResolver(), $this->config());
        $result = $limiter->attempt('rate-limit:products:test', 60, 60, 1_000);

        self::assertTrue($result->allowed);
        self::assertSame(57, $result->remaining);
        self::assertSame(0, $result->retryAfterSeconds);
        self::assertCount(1, $connection->calls);
    }

    public function test_it_converts_retry_after_from_milliseconds_to_seconds(): void
    {
        $factory = new FakeRedisFactory(new FakeRedisConnection([0, 60, 1500]));

        $limiter = new RedisSlidingWindowRateLimiter($factory, new LuaScriptResolver(), $this->config());
        $result = $limiter->attempt('rate-limit:products:test', 60, 60, 1_000);

        self::assertFalse($result->allowed);
        self::assertSame(60, $result->remaining);
        self::assertSame(2, $result->retryAfterSeconds);
    }

    private function config(): ConfigRepository
    {
        return new ConfigRepository([
            'rate_limit.redis_connection' => 'default',
        ]);
    }
}

final class FakeRedisFactory implements RedisFactory
{
    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    public function connection($name = null): Connection
    {
        return $this->connection;
    }
}

final class FakeRedisConnection extends Connection
{
    /** @var list<array{script: string, number_of_keys: int, arguments: array<int|string, string>}> */
    public array $calls = [];

    /**
     * @param  array{0:int,1:int,2:int}  $evalResult
     */
    public function __construct(
        private readonly array $evalResult,
    ) {
    }

    /**
     * @param array<int, string>|string $channels
     */
    public function createSubscription($channels, \Closure $callback, $method = 'subscribe'): void
    {
    }

    /**
     * @return array{0:int,1:int,2:int}
     */
    public function eval(string $script, int $numberOfKeys, string ...$arguments): array
    {
        $this->calls[] = [
            'script' => $script,
            'number_of_keys' => $numberOfKeys,
            'arguments' => $arguments,
        ];

        return $this->evalResult;
    }
}
