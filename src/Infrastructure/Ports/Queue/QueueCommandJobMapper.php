<?php

declare(strict_types=1);

namespace Infrastructure\Ports\Queue;

use Application\Contracts\Queue\QueuedCommand;

interface QueueCommandJobMapper
{
    public function map(QueuedCommand $command): object;
}
