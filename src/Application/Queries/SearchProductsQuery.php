<?php

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
