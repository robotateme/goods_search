<?php
declare(strict_types=1);

namespace Application\Handlers;

use Application\Contracts\Search\ProductSearch;
use Application\Queries\SearchProductsQuery;
use Domain\Product\ProductPage;

final readonly class SearchProductsHandler
{
    public function __construct(
        private readonly ProductSearch $productSearch,
    ) {
    }

    public function handle(SearchProductsQuery $query): ProductPage
    {
        return $this->productSearch->search($query->criteria);
    }
}
