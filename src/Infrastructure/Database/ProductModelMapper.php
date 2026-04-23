<?php
declare(strict_types=1);

namespace Infrastructure\Database;

use App\Models\Product as ProductModel;
use Domain\Product\Entity\Product;
use Domain\Product\ValueObject\CategoryId;
use Domain\Product\ValueObject\Price;
use Domain\Product\ValueObject\ProductId;
use Domain\Product\ValueObject\Rating;

final class ProductModelMapper
{
    public function map(ProductModel $product): Product
    {
        return new Product(
            id: new ProductId((int) $product->getKey()),
            name: $product->name,
            price: new Price((string) $product->getRawOriginal('price')),
            categoryId: new CategoryId((int) $product->category_id),
            inStock: (bool) $product->in_stock,
            rating: new Rating((float) $product->rating),
            createdAt: $product->created_at?->toDateTimeImmutable() ?? null,
            updatedAt: $product->updated_at?->toDateTimeImmutable() ?? null,
        );
    }
}
