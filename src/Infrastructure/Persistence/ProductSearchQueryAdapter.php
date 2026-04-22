<?php
declare(strict_types=1);

namespace Infrastructure\Persistence;

use App\Models\Product as ProductModel;
use Domain\Product\ProductSearchCriteria;
use Domain\Product\ProductSort;
use Illuminate\Database\Eloquent\Builder;

final readonly class ProductSearchQueryAdapter
{
    /**
     * @return Builder<ProductModel>
     */
    public function build(ProductSearchCriteria $criteria): Builder
    {
        $query = ProductModel::query()
            ->with('category')
            ->when($criteria->priceFrom !== null, fn (Builder $builder) => $builder->where('price', '>=', $criteria->priceFrom))
            ->when($criteria->priceTo !== null, fn (Builder $builder) => $builder->where('price', '<=', $criteria->priceTo))
            ->when($criteria->categoryId !== null, fn (Builder $builder) => $builder->where('category_id', $criteria->categoryId))
            ->when($criteria->inStock !== null, fn (Builder $builder) => $builder->where('in_stock', $criteria->inStock))
            ->when($criteria->ratingFrom !== null, fn (Builder $builder) => $builder->where('rating', '>=', $criteria->ratingFrom));

        if ($criteria->hasQuery()) {
            $query->where('name', 'like', '%'.$criteria->query.'%');
        }

        match ($criteria->sort) {
            ProductSort::PriceAsc => $query->orderBy('price')->orderBy('id'),
            ProductSort::PriceDesc => $query->orderByDesc('price')->orderBy('id'),
            ProductSort::RatingDesc => $query->orderByDesc('rating')->orderBy('id'),
            default => $query->orderByDesc('created_at')->orderByDesc('id'),
        };

        return $query;
    }
}
