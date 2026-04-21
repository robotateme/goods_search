<?php
declare(strict_types=1);

namespace Application\Contracts\Repositories;

use Closure;
use Domain\Product\Product;
use Domain\Product\ProductPage;
use Domain\Product\ProductSearchCriteria;

interface ProductRepositoryInterface
{
    public function search(ProductSearchCriteria $criteria): ProductPage;

    public function findById(int $productId): ?Product;

    /**
     * @param  list<int>  $ids
     * @return list<Product>
     */
    public function getByIds(array $ids): array;

    public function chunkById(int $chunkSize, Closure $callback): void;
}
