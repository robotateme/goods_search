<?php
declare(strict_types=1);

namespace App\Http\Responses;

use Domain\Product\Product;

final readonly class ProductResponseData
{
    public function __construct(
        public int $id,
        public string $name,
        public string $price,
        public int $category_id,
        public bool $in_stock,
        public float $rating,
        public ?string $created_at,
        public ?string $updated_at,
    ) {
    }

    public static function fromProduct(Product $product): self
    {
        return new self(
            id: $product->id,
            name: $product->name,
            price: $product->price->value(),
            category_id: $product->categoryId,
            in_stock: $product->inStock,
            rating: $product->rating->value(),
            created_at: $product->createdAt?->format(DATE_ATOM),
            updated_at: $product->updatedAt?->format(DATE_ATOM),
        );
    }

    /**
     * @return array{
     *     id: int,
     *     name: string,
     *     price: string,
     *     category_id: int,
     *     in_stock: bool,
     *     rating: float,
     *     created_at: string|null,
     *     updated_at: string|null
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'price' => $this->price,
            'category_id' => $this->category_id,
            'in_stock' => $this->in_stock,
            'rating' => $this->rating,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
