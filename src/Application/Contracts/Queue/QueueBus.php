<?php
declare(strict_types=1);


namespace Application\Contracts\Queue;

interface QueueBus
{
    public function dispatch(object $command): mixed;

    public function dispatchSync(object $command): mixed;
}
