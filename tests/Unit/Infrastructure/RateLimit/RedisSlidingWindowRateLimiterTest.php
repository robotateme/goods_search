<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\RateLimit;

use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Illuminate\Redis\Connections\Connection;
use Infrastructure\Redis\RateLimit\RedisSlidingWindowRateLimiter;
use Infrastructure\Redis\ScriptResolver;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class RedisSlidingWindowRateLimiterTest extends TestCase
{
    /**
     * Проверяет преобразование ответа Lua-скрипта в результат domain-level rate limiter.
     *
     * @param  array{0:int,1:int,2:int}  $evalResult
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

        $limiter = new RedisSlidingWindowRateLimiter($factory, new ScriptResolver, $this->config());
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
    ) {}

    #[Override]
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
    ) {}

    /**
     * @param  array<array-key, mixed>|string  $channels
     */
    #[Override]
    public function createSubscription($channels, \Closure $callback, $method = 'subscribe'): void {}

    /**
     * @return array{0:int,1:int,2:int}
     */
    /**
     * @param  array<int, mixed>  $parameters
     */
    #[Override]
    public function command($method, array $parameters = []): mixed
    {
        if ($method !== 'eval') {
            throw new \InvalidArgumentException(sprintf('Unexpected Redis command: %s', $method));
        }

        $this->calls[] = [
            'script' => $this->stringParameter($parameters[0] ?? ''),
            'number_of_keys' => $this->intParameter($parameters[1] ?? 0),
            'arguments' => array_map(fn (mixed $argument): string => $this->stringParameter($argument), array_slice($parameters, 2)),
        ];

        return $this->evalResult;
    }

    private function intParameter(mixed $value): int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && is_numeric($value)) {
            return (int) $value;
        }

        throw new \InvalidArgumentException('Expected int-like Redis command parameter.');
    }

    private function stringParameter(mixed $value): string
    {
        if (is_string($value) || is_int($value) || is_float($value) || is_bool($value)) {
            return (string) $value;
        }

        throw new \InvalidArgumentException('Expected scalar Redis command parameter.');
    }
}
