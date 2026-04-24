<?php

declare(strict_types=1);

namespace Application\Commands;

final readonly class IndexProductInSearchCommand
{
    public function __construct(
        public int $productId,
    ) {}
}
