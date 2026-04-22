<?php
declare(strict_types=1);

namespace App\Http\Responses;

use Domain\Product\Entity\Product;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ProductResponse',
    required: ['id', 'name', 'price', 'category_id', 'in_stock', 'rating', 'created_at', 'updated_at'],
)]
final readonly class ProductResponseData
{
    public function __construct(
        #[OA\Property(example: 1)]
        public int $id,
        #[OA\Property(example: 'Wireless Mouse Pro')]
        public string $name,
        #[OA\Property(type: 'string', pattern: '^\\d+(\\.\\d{1,2})?$', example: '149.99')]
        public string $price,
        #[OA\Property(example: 2)]
        public int $category_id,
        #[OA\Property(example: true)]
        public bool $in_stock,
        #[OA\Property(example: 4.8)]
        public float $rating,
        #[OA\Property(type: 'string', format: 'date-time', example: '2026-04-21T15:00:00+00:00', nullable: true)]
        public ?string $created_at,
        #[OA\Property(type: 'string', format: 'date-time', example: '2026-04-21T15:00:00+00:00', nullable: true)]
        public ?string $updated_at,
    ) {
    }

    public static function fromProduct(Product $product): self
    {
        return new self(
            id: $product->id->value(),
            name: $product->name,
            price: $product->price->value(),
            category_id: $product->categoryId->value(),
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
