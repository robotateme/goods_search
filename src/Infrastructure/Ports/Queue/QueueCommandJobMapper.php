<?php

declare(strict_types=1);

namespace Infrastructure\Ports\Queue;

interface QueueCommandJobMapper
{
    public function map(object $command): object;
}
