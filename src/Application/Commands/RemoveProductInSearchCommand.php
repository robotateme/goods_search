<?php

declare(strict_types=1);

namespace Application\Commands;

use Application\Contracts\Queue\QueuedCommand;

final readonly class RemoveProductInSearchCommand implements QueuedCommand
{
    public function __construct(
        public int $productId,
    ) {}
}
