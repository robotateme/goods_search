<?php
declare(strict_types=1);

namespace Application\Contracts\Repositories;

use Closure;
use Domain\Product\Product;

interface ProductRepositoryInterface
{
    public function findById(int $productId): ?Product;

    /**
     * @param  list<int>  $ids
     * @return list<Product>
     */
    public function getByIds(array $ids): array;

    public function chunkById(int $chunkSize, Closure $callback): void;
}
