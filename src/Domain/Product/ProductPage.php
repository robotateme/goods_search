<?php
declare(strict_types=1);

namespace Domain\Product;

final readonly class ProductPage
{
    /**
     * @param  list<Product>  $items
     */
    public function __construct(
        public array $items,
        public int $total,
        public int $perPage,
        public int $currentPage,
    ) {
    }

    public function lastPage(): int
    {
        return max(1, (int) ceil($this->total / $this->perPage));
    }

    public function from(): ?int
    {
        if ($this->items === []) {
            return null;
        }

        return (($this->currentPage - 1) * $this->perPage) + 1;
    }

    public function to(): ?int
    {
        if ($this->items === []) {
            return null;
        }

        return min($this->total, $this->currentPage * $this->perPage);
    }
}
