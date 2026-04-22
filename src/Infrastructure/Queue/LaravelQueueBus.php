<?php
declare(strict_types=1);


namespace Infrastructure\Queue;

use Application\Contracts\Queue\QueueBus;
use Illuminate\Contracts\Bus\Dispatcher;

final readonly class LaravelQueueBus implements QueueBus
{
    public function __construct(
        private readonly Dispatcher $dispatcher,
    ) {
    }

    public function dispatch(object $command): mixed
    {
        return $this->dispatcher->dispatch($command);
    }

    public function dispatchSync(object $command): mixed
    {
        return $this->dispatcher->dispatchSync($command);
    }
}
