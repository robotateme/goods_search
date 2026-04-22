<?php
declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Queue;

use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Illuminate\Redis\Connections\Connection;
use Infrastructure\Queue\RedisQueueDeduplicator;
use Infrastructure\Support\ScriptResolver;
use PHPUnit\Framework\TestCase;

class RedisQueueDeduplicatorTest extends TestCase
{
    // Проверяет, что deduplicator интерпретирует успешный claim ключа как true.
    public function test_it_claims_new_dedup_key(): void
    {
        $deduplicator = new RedisQueueDeduplicator(
            new FakeRedisFactory(new FakeEvalRedisConnection(1)),
            new ScriptResolver(),
            new ConfigRepository(['queue.dedup.redis_connection' => 'default']),
        );

        self::assertTrue($deduplicator->claim('queue-dedup:test', 30));
    }

    // Проверяет, что deduplicator возвращает false, если ключ уже был занят.
    public function test_it_rejects_existing_dedup_key(): void
    {
        $deduplicator = new RedisQueueDeduplicator(
            new FakeRedisFactory(new FakeEvalRedisConnection(0)),
            new ScriptResolver(),
            new ConfigRepository(['queue.dedup.redis_connection' => 'default']),
        );

        self::assertFalse($deduplicator->claim('queue-dedup:test', 30));
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

final class FakeEvalRedisConnection extends Connection
{
    public function __construct(
        private readonly int $evalResult,
    ) {
    }

    /**
     * @param array<int, string>|string $channels
     */
    public function createSubscription($channels, \Closure $callback, $method = 'subscribe'): void
    {
    }

    /**
     * @return int
     */
    public function eval(string $script, int $numberOfKeys, string ...$arguments): int
    {
        return $this->evalResult;
    }
}
