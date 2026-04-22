<?php
declare(strict_types=1);

namespace Domain\Product\Search;

use Domain\Product\Entity\Product;
use Domain\Product\ValueObject\Page;
use Domain\Product\ValueObject\PerPage;

final readonly class ProductPage
{
    /**
     * @param  list<Product>  $items
     */
    public function __construct(
        public array $items,
        public int $total,
        public PerPage $perPage,
        public Page $currentPage,
    ) {
    }

    public function lastPage(): int
    {
        return max(1, (int) ceil($this->total / $this->perPage->value()));
    }

    public function from(): ?int
    {
        if ($this->items === []) {
            return null;
        }

        return (($this->currentPage->value() - 1) * $this->perPage->value()) + 1;
    }

    public function to(): ?int
    {
        if ($this->items === []) {
            return null;
        }

        return min($this->total, $this->currentPage->value() * $this->perPage->value());
    }
}
