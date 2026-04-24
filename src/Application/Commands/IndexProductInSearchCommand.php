<?php

declare(strict_types=1);

namespace Application\Commands;

use Application\Contracts\Queue\DeduplicatedCommand;
use Override;

final readonly class IndexProductInSearchCommand implements DeduplicatedCommand
{
    public function __construct(
        public int $productId,
    ) {}

    #[Override]
    public function deduplicationKey(): string
    {
        return sprintf('queue-dedup:search:index:%d', $this->productId);
    }
}
