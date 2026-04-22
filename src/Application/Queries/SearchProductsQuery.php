<?php
declare(strict_types=1);

namespace Application\Queries;

use Domain\Product\ProductSearchCriteria;

final readonly class SearchProductsQuery
{
    public function __construct(
        public readonly ProductSearchCriteria $criteria,
    ) {
    }
}
