<?php

declare(strict_types=1);

namespace Infrastructure\Ports\Queue;

use Application\Contracts\Queue\QueueBus;
use Illuminate\Contracts\Bus\Dispatcher;
use Override;

final readonly class LaravelQueueBus implements QueueBus
{
    public function __construct(
        private readonly Dispatcher $dispatcher,
        private readonly QueueCommandJobMapper $jobMapper,
    ) {}

    #[Override]
    public function dispatch(object $command): mixed
    {
        return $this->dispatcher->dispatch($this->jobMapper->map($command));
    }

    #[Override]
    public function dispatchSync(object $command): mixed
    {
        return $this->dispatcher->dispatchSync($this->jobMapper->map($command));
    }
}
