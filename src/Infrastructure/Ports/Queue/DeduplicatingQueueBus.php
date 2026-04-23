<?php
declare(strict_types=1);

namespace Infrastructure\Ports\Queue;

use App\Jobs\IndexProductInSearchJob;
use Application\Contracts\Queue\QueueBus;
use Infrastructure\Redis\Queue\RedisQueueDeduplicator;

final readonly class DeduplicatingQueueBus implements QueueBus
{
    public function __construct(
        private QueueBus $inner,
        private RedisQueueDeduplicator $deduplicator,
    ) {
    }

    public function dispatch(object $command): mixed
    {
        if (! $this->shouldDispatch($command)) {
            return null;
        }

        return $this->inner->dispatch($command);
    }

    public function dispatchSync(object $command): mixed
    {
        return $this->inner->dispatchSync($command);
    }

    private function shouldDispatch(object $command): bool
    {
        if ((string) config('queue.default', 'sync') !== 'redis') {
            return true;
        }

        if (! $command instanceof IndexProductInSearchJob) {
            return true;
        }

        return $this->deduplicator->claim(
            sprintf('queue-dedup:search:index:%d', $command->productId),
            (int) config('queue.dedup.ttl_seconds', 30),
        );
    }
}
