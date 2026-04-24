<?php

declare(strict_types=1);

namespace Infrastructure\Search;

use Domain\Product\Entity\Product;

final class ProductSearchDocumentMapper
{
    /**
     * @return array{
     *     id: int,
     *     name: string,
     *     price: float,
     *     category_id: int,
     *     in_stock: bool,
     *     rating: float,
     *     created_at_timestamp: int|null,
     *     updated_at_timestamp: int|null
     * }
     */
    public function map(Product $product): array
    {
        return [
            'id' => $product->id->value(),
            'name' => $product->name,
            'price' => (float) $product->price->value(),
            'category_id' => $product->categoryId->value(),
            'in_stock' => $product->inStock,
            'rating' => $product->rating->value(),
            'created_at_timestamp' => $product->createdAt?->getTimestamp(),
            'updated_at_timestamp' => $product->updatedAt?->getTimestamp(),
        ];
    }
}
