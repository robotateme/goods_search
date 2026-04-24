<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Illuminate\Redis\Connections\Connection;
use Infrastructure\Redis\RateLimit\RedisSlidingWindowRateLimiter;
use Infrastructure\Redis\ScriptResolver;
use Override;
use Tests\TestCase;

class RedisSlidingWindowRateLimitTest extends TestCase
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate:fresh');
    }

    // Проверяет, что middleware rate limit возвращает 429 и заголовок Retry-After.
    public function test_it_returns_429_when_route_rate_limit_is_exceeded(): void
    {
        $this->app->instance(RedisFactory::class, new FeatureFakeRedisFactory(
            new FeatureFakeRedisConnection([0, 0, 12_000]),
        ));
        $this->app->instance(
            RedisSlidingWindowRateLimiter::class,
            new RedisSlidingWindowRateLimiter(
                $this->app->make(RedisFactory::class),
                new ScriptResolver,
                $this->app->make(ConfigRepository::class),
            ),
        );

        $response = $this->getJson('/api/products');

        $response
            ->assertTooManyRequests()
            ->assertHeader('Retry-After', '12')
            ->assertJsonPath('message', 'Too many requests.');
    }
}

final class FeatureFakeRedisFactory implements RedisFactory
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

final class FeatureFakeRedisConnection extends Connection
{
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
    public function eval(string $script, int $numberOfKeys, string ...$arguments): array
    {
        return $this->evalResult;
    }
}
