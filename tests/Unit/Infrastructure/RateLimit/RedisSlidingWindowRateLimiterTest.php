<?php
declare(strict_types=1);

namespace Tests\Unit\Infrastructure\RateLimit;

use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Illuminate\Redis\Connections\Connection;
use Infrastructure\RateLimit\LuaScriptResolver;
use Infrastructure\RateLimit\RedisSlidingWindowRateLimiter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class RedisSlidingWindowRateLimiterTest extends TestCase
{
    /**
     * Проверяет преобразование ответа Lua-скрипта в результат domain-level rate limiter.
     *
     * @param array{0:int,1:int,2:int} $evalResult
     */
    #[DataProvider('attemptProvider')]
    public function test_it_maps_lua_response_to_domain_result(
        array $evalResult,
        bool $expectedAllowed,
        int $expectedRemaining,
        int $expectedRetryAfterSeconds,
    ): void {
        $connection = new FakeRedisConnection($evalResult);
        $factory = new FakeRedisFactory($connection);

        $limiter = new RedisSlidingWindowRateLimiter($factory, new LuaScriptResolver(), $this->config());
        $result = $limiter->attempt('rate-limit:products:test', 60, 60, 1_000);

        self::assertSame($expectedAllowed, $result->allowed);
        self::assertSame($expectedRemaining, $result->remaining);
        self::assertSame($expectedRetryAfterSeconds, $result->retryAfterSeconds);
        self::assertCount(1, $connection->calls);
    }

    /**
     * Набор сценариев ответа Redis/Lua для rate limiter.
     *
     * @return array<string, array{array{0:int,1:int,2:int}, bool, int, int}>
     */
    public static function attemptProvider(): array
    {
        return [
            'allowed request' => [[1, 57, 0], true, 57, 0],
            'blocked request' => [[0, 60, 1500], false, 60, 2],
        ];
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
