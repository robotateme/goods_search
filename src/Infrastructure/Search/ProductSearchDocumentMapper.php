<?php
declare(strict_types=1);

namespace Infrastructure\Search;

use Domain\Product\Product;

class ProductSearchDocumentMapper
{
    public function map(Product $product): array
    {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'price' => (float) $product->price,
            'category_id' => $product->categoryId,
            'in_stock' => $product->inStock,
            'rating' => $product->rating,
            'created_at_timestamp' => $product->createdAt?->getTimestamp(),
            'updated_at_timestamp' => $product->updatedAt?->getTimestamp(),
        ];
    }
}
