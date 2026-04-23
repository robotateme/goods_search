<?php
declare(strict_types=1);

namespace Infrastructure\Database;

use App\Models\Product as ProductModel;
use Domain\Product\Search\ProductSearchCriteria;
use Domain\Product\Search\ProductSort;
use Illuminate\Database\Eloquent\Builder;

final class ProductSearchQueryAdapter
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
            ->when($criteria->inStock !== null, fn (Builder $builder) => $builder->where('in_stock', $criteria->inStock))
            ->when($criteria->ratingFrom !== null, fn (Builder $builder) => $builder->where('rating', '>=', $criteria->ratingFrom));

        if ($criteria->categoryId !== null) {
            $query->where('category_id', $criteria->categoryId->value());
        }

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
