<?php
declare(strict_types=1);

namespace Infrastructure\Persistence;

use Application\Contracts\Repositories\ProductRepositoryInterface;
use App\Models\Product as ProductModel;
use Closure;
use Domain\Product\Product;

class ProductRepository implements ProductRepositoryInterface
{
    public function __construct(
        private readonly ProductModelMapper $mapper,
    ) {
    }

    public function findById(int $productId): ?Product
    {
        $product = ProductModel::query()->find($productId);

        return $product === null ? null : $this->mapper->map($product);
    }

    public function getByIds(array $ids): array
    {
        return ProductModel::query()
            ->with('category')
            ->whereIn('id', $ids)
            ->get()
            ->map(fn (ProductModel $product) => $this->mapper->map($product))
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
                        ->map(fn (ProductModel $product) => $this->mapper->map($product))
                        ->values()
                        ->all(),
                );
            });
    }
}
