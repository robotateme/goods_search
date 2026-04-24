<?php

declare(strict_types=1);

namespace Application\Commands;

use Application\Contracts\Queue\QueuedCommand;

final readonly class IndexProductInSearchCommand implements QueuedCommand
{
    public function __construct(
        public int $productId,
    ) {}
}
