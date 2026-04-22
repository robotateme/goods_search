<?php
declare(strict_types=1);

namespace Application\Contracts\Search;

use Domain\Product\Search\ProductPage;
use Domain\Product\Search\ProductSearchCriteria;

interface ProductSearch
{
    public function search(ProductSearchCriteria $criteria): ProductPage;
}
