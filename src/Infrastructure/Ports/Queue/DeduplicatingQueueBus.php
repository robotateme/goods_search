<?php

declare(strict_types=1);

namespace Infrastructure\Ports\Queue;

use Application\Contracts\Queue\QueueBus;
use Application\Contracts\Queue\QueuedCommand;
use Infrastructure\Redis\Queue\RedisQueueDeduplicator;
use Override;

final readonly class DeduplicatingQueueBus implements QueueBus
{
    public function __construct(
        private QueueBus $inner,
        private RedisQueueDeduplicator $deduplicator,
        private CommandDeduplicationKeyResolver $keyResolver,
    ) {}

    #[Override]
    public function dispatch(QueuedCommand $command): mixed
    {
        if (! $this->shouldDispatch($command)) {
            return null;
        }

        return $this->inner->dispatch($command);
    }

    #[Override]
    public function dispatchSync(QueuedCommand $command): mixed
    {
        return $this->inner->dispatchSync($command);
    }

    private function shouldDispatch(QueuedCommand $command): bool
    {
        if ((string) config('queue.default', 'sync') !== 'redis') {
            return true;
        }

        $key = $this->keyResolver->resolve($command);

        if ($key === null) {
            return true;
        }

        return $this->deduplicator->claim(
            $key,
            (int) config('queue.dedup.ttl_seconds', 30),
        );
    }
}
