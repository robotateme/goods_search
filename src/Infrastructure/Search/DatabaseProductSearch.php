<?php
declare(strict_types=1);

namespace Infrastructure\Search;

use Application\Contracts\Repositories\ProductRepositoryInterface;
use Application\Contracts\Search\ProductSearch;
use Domain\Product\ProductPage;
use Domain\Product\ProductSearchCriteria;

class DatabaseProductSearch implements ProductSearch
{
    public function __construct(
        private readonly ProductRepositoryInterface $products,
    ) {
    }

    public function search(ProductSearchCriteria $criteria): ProductPage
    {
        return $this->products->search($criteria);
    }
}
