<?php
declare(strict_types=1);

namespace Infrastructure\Persistence;

use Application\Contracts\Repositories\ProductRepositoryInterface;
use App\Models\Product as ProductModel;
use Closure;
use Domain\Product\Product;
use Domain\Product\ProductPage;
use Domain\Product\ProductSearchCriteria;
use Domain\Product\ProductSort;
use Illuminate\Database\Eloquent\Builder;

class ProductRepository implements ProductRepositoryInterface
{
    public function search(ProductSearchCriteria $criteria): ProductPage
    {
        $query = ProductModel::query()
            ->with('category')
            ->searchName($criteria->query)
            ->when($criteria->priceFrom !== null, fn (Builder $builder) => $builder->where('price', '>=', $criteria->priceFrom))
            ->when($criteria->priceTo !== null, fn (Builder $builder) => $builder->where('price', '<=', $criteria->priceTo))
            ->when($criteria->categoryId !== null, fn (Builder $builder) => $builder->where('category_id', $criteria->categoryId))
            ->when($criteria->inStock !== null, fn (Builder $builder) => $builder->where('in_stock', $criteria->inStock))
            ->when($criteria->ratingFrom !== null, fn (Builder $builder) => $builder->where('rating', '>=', $criteria->ratingFrom));

        match ($criteria->sort) {
            ProductSort::PriceAsc => $query->orderBy('price')->orderBy('id'),
            ProductSort::PriceDesc => $query->orderByDesc('price')->orderBy('id'),
            ProductSort::RatingDesc => $query->orderByDesc('rating')->orderBy('id'),
            default => $query->orderByDesc('created_at')->orderByDesc('id'),
        };

        $paginator = $query->paginate($criteria->perPage, ['*'], 'page', $criteria->page);

        return new ProductPage(
            $paginator->getCollection()->map(fn (ProductModel $product) => $this->map($product))->values()->all(),
            $paginator->total(),
            $paginator->perPage(),
            $paginator->currentPage(),
        );
    }

    public function findById(int $productId): ?Product
    {
        $product = ProductModel::query()->find($productId);

        return $product === null ? null : $this->map($product);
    }

    public function getByIds(array $ids): array
    {
        return ProductModel::query()
            ->with('category')
            ->whereIn('id', $ids)
            ->get()
            ->map(fn (ProductModel $product) => $this->map($product))
            ->values()
            ->all();
    }

    public function chunkById(int $chunkSize, Closure $callback): void
    {
        ProductModel::query()
            ->orderBy('id')
            ->chunk($chunkSize, function ($products) use ($callback): void {
                $callback(
                    $products
                        ->map(fn (ProductModel $product) => $this->map($product))
                        ->values()
                        ->all(),
                );
            });
    }

    private function map(ProductModel $product): Product
    {
        return new Product(
            id: (int) $product->getKey(),
            name: $product->name,
            price: (string) $product->getRawOriginal('price'),
            categoryId: (int) $product->category_id,
            inStock: (bool) $product->in_stock,
            rating: (float) $product->rating,
            createdAt: $product->created_at?->toDateTimeImmutable() ?? null,
            updatedAt: $product->updated_at?->toDateTimeImmutable() ?? null,
        );
    }
}
