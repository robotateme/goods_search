<?php

declare(strict_types=1);

namespace Infrastructure\Redis\Queue;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Illuminate\Redis\Connections\Connection;
use Infrastructure\Redis\ScriptResolver;

final readonly class RedisQueueDeduplicator
{
    public function __construct(
        private RedisFactory $redisFactory,
        private ScriptResolver $scriptResolver,
        private ConfigRepository $config,
    ) {}

    public function claim(string $key, int $ttlSeconds): bool
    {
        /** @var array{0:int|string}|int|string $result */
        $result = $this->redis()->command('eval', [
            $this->scriptResolver->resolve('queue/claim_dedup_key.lua'),
            1,
            $key,
            (string) $ttlSeconds,
            (string) microtime(true),
        ]);

        if (is_array($result)) {
            return (int) $result[0] === 1;
        }

        return (int) $result === 1;
    }

    private function redis(): Connection
    {
        return $this->redisFactory->connection($this->connectionName());
    }

    private function connectionName(): string
    {
        $connection = $this->config->get('queue.dedup.redis_connection', 'default');

        if (! is_string($connection)) {
            return 'default';
        }

        return $connection;
    }
}
