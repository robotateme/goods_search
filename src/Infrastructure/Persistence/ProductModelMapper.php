<?php
declare(strict_types=1);

namespace Infrastructure\Persistence;

use App\Models\Product as ProductModel;
use Domain\Product\Price;
use Domain\Product\Product;
use Domain\Product\Rating;

final class ProductModelMapper
{
    public function map(ProductModel $product): Product
    {
        return new Product(
            id: (int) $product->getKey(),
            name: $product->name,
            price: new Price((string) $product->getRawOriginal('price')),
            categoryId: (int) $product->category_id,
            inStock: (bool) $product->in_stock,
            rating: new Rating((float) $product->rating),
            createdAt: $product->created_at?->toDateTimeImmutable() ?? null,
            updatedAt: $product->updated_at?->toDateTimeImmutable() ?? null,
        );
    }
}
