<?php
declare(strict_types=1);


namespace Application\Handlers;

use Application\Contracts\Search\ProductSearch;
use Application\Queries\SearchProductsQuery;
use Illuminate\Pagination\LengthAwarePaginator;

class SearchProductsHandler
{
    public function __construct(
        private readonly ProductSearch $productSearch,
    ) {
    }

    public function handle(SearchProductsQuery $query): LengthAwarePaginator
    {
        return $this->productSearch->search(
            $query->filters,
            $query->perPage,
            $query->page,
        );
    }
}
