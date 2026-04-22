<?php
declare(strict_types=1);

namespace Infrastructure\Persistence;

use Application\Contracts\Repositories\ProductRepositoryInterface;
use App\Models\Product as ProductModel;
use Closure;
use Domain\Product\Entity\Product;

final readonly class ProductRepository implements ProductRepositoryInterface
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

    /**
     * @param  list<int>  $ids
     * @return list<Product>
     */
    public function getByIds(array $ids): array
    {
        return $this->mapProducts(
            ProductModel::whereIn('id', $ids)->cursor(),
        );
    }

    /**
     * @param  Closure(list<Product>): void  $callback
     */
    public function chunkById(int $chunkSize, Closure $callback): void
    {
        $mapped = [];

        foreach (ProductModel::orderBy('id')->lazy($chunkSize) as $product) {
            $mapped[] = $this->mapper->map($product);

            if (count($mapped) === $chunkSize) {
                $callback($mapped);
                $mapped = [];
            }
        }

        if ($mapped !== []) {
            $callback($mapped);
        }
    }

    /**
     * @param  iterable<ProductModel>  $products
     * @return list<Product>
     */
    private function mapProducts(iterable $products): array
    {
        $mapped = [];

        foreach ($products as $product) {
            $mapped[] = $this->mapper->map($product);
        }

        return $mapped;
    }
}
