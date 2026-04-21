<?php
declare(strict_types=1);


namespace Application\Queries;

class SearchProductsQuery
{
    public function __construct(
        public readonly array $filters,
        public readonly int $perPage,
        public readonly int $page,
    ) {
    }
}
