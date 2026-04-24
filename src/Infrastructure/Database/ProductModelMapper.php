<?php

declare(strict_types=1);

namespace Infrastructure\Database;

use Domain\Product\Entity\Product;
use Domain\Product\ValueObject\CategoryId;
use Domain\Product\ValueObject\Price;
use Domain\Product\ValueObject\ProductId;
use Domain\Product\ValueObject\Rating;
use Infrastructure\Database\Eloquent\Product as ProductModel;

final class ProductModelMapper
{
    public function map(ProductModel $product): Product
    {
        $productId = $product->getKey();
        $price = $product->getRawOriginal('price');

        if (! is_int($productId)) {
            throw new \UnexpectedValueException('Product key must be an integer.');
        }

        if (! is_int($price) && ! (is_string($price) && ctype_digit($price))) {
            throw new \UnexpectedValueException('Stored product price must be an integer minor-units value.');
        }

        return new Product(
            id: new ProductId($productId),
            name: $product->name,
            price: Price::fromMinorUnits(is_int($price) ? $price : (int) $price),
            categoryId: new CategoryId((int) $product->category_id),
            inStock: (bool) $product->in_stock,
            rating: new Rating((float) $product->rating),
            createdAt: $product->created_at?->toDateTimeImmutable() ?? null,
            updatedAt: $product->updated_at?->toDateTimeImmutable() ?? null,
        );
    }
}
