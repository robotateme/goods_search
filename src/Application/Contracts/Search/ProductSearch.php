<?php
declare(strict_types=1);


namespace Application\Contracts\Search;

use Illuminate\Pagination\LengthAwarePaginator;

interface ProductSearch
{
    public function search(array $filters, int $perPage, int $page): LengthAwarePaginator;
}
