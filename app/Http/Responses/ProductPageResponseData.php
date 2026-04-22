<?php
declare(strict_types=1);

namespace App\Http\Responses;

use Domain\Product\Search\ProductPage;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ProductPageResponse',
    required: ['current_page', 'data', 'from', 'last_page', 'path', 'per_page', 'to', 'total'],
)]
final readonly class ProductPageResponseData
{
    /**
     * @param  list<ProductResponseData>  $data
     */
    public function __construct(
        #[OA\Property(example: 1)]
        public int $current_page,
        /** @var list<ProductResponseData> */
        #[OA\Property(type: 'array', items: new OA\Items(ref: '#/components/schemas/ProductResponse'))]
        public array $data,
        #[OA\Property(example: 1, nullable: true)]
        public ?int $from,
        #[OA\Property(example: 10)]
        public int $last_page,
        #[OA\Property(example: 'http://localhost/api/products')]
        public string $path,
        #[OA\Property(example: 20)]
        public int $per_page,
        #[OA\Property(example: 20, nullable: true)]
        public ?int $to,
        #[OA\Property(example: 200)]
        public int $total,
    ) {
    }

    public static function fromPage(ProductPage $page, string $path): self
    {
        return new self(
            current_page: $page->currentPage->value(),
            data: array_map(
                fn ($product) => ProductResponseData::fromProduct($product),
                $page->items,
            ),
            from: $page->from(),
            last_page: $page->lastPage(),
            path: $path,
            per_page: $page->perPage->value(),
            to: $page->to(),
            total: $page->total,
        );
    }

    /**
     * @return array{
     *     current_page: int,
     *     data: list<array{
     *         id: int,
     *         name: string,
     *         price: string,
     *         category_id: int,
     *         in_stock: bool,
     *         rating: float,
     *         created_at: string|null,
     *         updated_at: string|null
     *     }>,
     *     from: int|null,
     *     last_page: int,
     *     path: string,
     *     per_page: int,
     *     to: int|null,
     *     total: int
     * }
     */
    public function toArray(): array
    {
        return [
            'current_page' => $this->current_page,
            'data' => array_map(
                static fn (ProductResponseData $product) => $product->toArray(),
                $this->data,
            ),
            'from' => $this->from,
            'last_page' => $this->last_page,
            'path' => $this->path,
            'per_page' => $this->per_page,
            'to' => $this->to,
            'total' => $this->total,
        ];
    }
}
