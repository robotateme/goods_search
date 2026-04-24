<?php

declare(strict_types=1);

namespace Application\Commands;

final readonly class RemoveProductInSearchCommand
{
    public function __construct(
        public int $productId,
    ) {}
}
