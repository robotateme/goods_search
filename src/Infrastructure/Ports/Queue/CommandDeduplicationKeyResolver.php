<?php

declare(strict_types=1);

namespace Infrastructure\Ports\Queue;

use Application\Commands\IndexProductInSearchCommand;

final class CommandDeduplicationKeyResolver
{
    public function resolve(object $command): ?string
    {
        return match (true) {
            $command instanceof IndexProductInSearchCommand => sprintf('queue-dedup:search:index:%d', $command->productId),
            default => null,
        };
    }
}
