<?php
declare(strict_types=1);

namespace Application\Contracts\Search;

use Domain\Product\ProductPage;
use Domain\Product\ProductSearchCriteria;

interface ProductSearch
{
    public function search(ProductSearchCriteria $criteria): ProductPage;
}
