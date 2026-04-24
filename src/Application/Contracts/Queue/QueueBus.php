<?php

declare(strict_types=1);

namespace Application\Contracts\Queue;

interface QueueBus
{
    public function dispatch(QueuedCommand $command): mixed;

    public function dispatchSync(QueuedCommand $command): mixed;
}
