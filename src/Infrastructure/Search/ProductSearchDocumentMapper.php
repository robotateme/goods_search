<?php
declare(strict_types=1);

namespace Infrastructure\Search;

use Domain\Product\Product;

final readonly class ProductSearchDocumentMapper
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
            'id' => $product->id,
            'name' => $product->name,
            'price' => (float) $product->price->value(),
            'category_id' => $product->categoryId,
            'in_stock' => $product->inStock,
            'rating' => $product->rating->value(),
            'created_at_timestamp' => $product->createdAt?->getTimestamp(),
            'updated_at_timestamp' => $product->updatedAt?->getTimestamp(),
        ];
    }
}
