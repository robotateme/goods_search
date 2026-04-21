<?php

namespace Infrastructure\Search;

use Application\Contracts\Search\ProductSearch;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class DatabaseProductSearch implements ProductSearch
{
    public function search(array $filters, int $perPage, int $page): LengthAwarePaginator
    {
        $query = Product::query()
            ->with('category')
            ->searchName($filters['q'] ?? null)
            ->when(isset($filters['price_from']), fn (Builder $builder) => $builder->where('price', '>=', $filters['price_from']))
            ->when(isset($filters['price_to']), fn (Builder $builder) => $builder->where('price', '<=', $filters['price_to']))
            ->when(isset($filters['category_id']), fn (Builder $builder) => $builder->where('category_id', $filters['category_id']))
            ->when(isset($filters['in_stock']), fn (Builder $builder) => $builder->where('in_stock', $filters['in_stock']))
            ->when(isset($filters['rating_from']), fn (Builder $builder) => $builder->where('rating', '>=', $filters['rating_from']));

        match ($filters['sort'] ?? 'newest') {
            'price_asc' => $query->orderBy('price')->orderBy('id'),
            'price_desc' => $query->orderByDesc('price')->orderBy('id'),
            'rating_desc' => $query->orderByDesc('rating')->orderBy('id'),
            default => $query->orderByDesc('created_at')->orderByDesc('id'),
        };

        return $query->paginate($perPage, ['*'], 'page', $page);
    }
}
