<?php
declare(strict_types=1);

namespace Application\Handlers;

use Application\Contracts\Search\ProductSearch;
use Application\Queries\SearchProductsQuery;
use Domain\Product\Search\ProductPage;

final readonly class SearchProductsHandler
{
    public function __construct(
        private ProductSearch $productSearch,
    ) {
    }

    public function handle(SearchProductsQuery $query): ProductPage
    {
        return $this->productSearch->search($query->criteria);
    }
}
