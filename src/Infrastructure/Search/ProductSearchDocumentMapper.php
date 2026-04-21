<?php
declare(strict_types=1);


namespace Infrastructure\Search;

use App\Models\Product;

class ProductSearchDocumentMapper
{
    public function map(Product $product): array
    {
        return [
            'id' => $product->getKey(),
            'name' => $product->name,
            'price' => (float) $product->getRawOriginal('price'),
            'category_id' => $product->category_id,
            'in_stock' => $product->in_stock,
            'rating' => $product->rating,
            'created_at_timestamp' => $product->created_at?->getTimestamp(),
            'updated_at_timestamp' => $product->updated_at?->getTimestamp(),
        ];
    }
}
