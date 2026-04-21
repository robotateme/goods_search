<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use Application\Handlers\SearchProductsHandler;
use Application\Queries\SearchProductsQuery;
use Domain\Product\Product;
use Domain\Product\ProductPage;
use Domain\Product\ProductSearchCriteria;
use Domain\Product\ProductSort;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProductIndexController extends Controller
{
    public function __construct(
        private readonly SearchProductsHandler $handler,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'price_from' => ['nullable', 'numeric', 'min:0'],
            'price_to' => ['nullable', 'numeric', 'min:0', 'gte:price_from'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'in_stock' => ['nullable', 'string'],
            'rating_from' => ['nullable', 'numeric', 'between:0,5'],
            'sort' => ['nullable', 'string', Rule::in(['price_asc', 'price_desc', 'rating_desc', 'newest'])],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        if (array_key_exists('in_stock', $filters)) {
            $normalizedInStock = filter_var($filters['in_stock'], FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);

            if ($normalizedInStock === null) {
                throw ValidationException::withMessages([
                    'in_stock' => 'The in stock field must be true or false.',
                ]);
            }

            $filters['in_stock'] = $normalizedInStock;
        }

        $page = isset($filters['page']) ? (int) $filters['page'] : 1;
        $perPage = isset($filters['per_page']) ? (int) $filters['per_page'] : 15;
        $result = $this->handler->handle(new SearchProductsQuery(
            $this->mapCriteria($filters, $perPage, $page),
        ));

        return response()->json($this->toResponse($result, $request));
    }

    private function mapCriteria(array $filters, int $perPage, int $page): ProductSearchCriteria
    {
        return new ProductSearchCriteria(
            query: $filters['q'] ?? null,
            priceFrom: isset($filters['price_from']) ? (float) $filters['price_from'] : null,
            priceTo: isset($filters['price_to']) ? (float) $filters['price_to'] : null,
            categoryId: isset($filters['category_id']) ? (int) $filters['category_id'] : null,
            inStock: $filters['in_stock'] ?? null,
            ratingFrom: isset($filters['rating_from']) ? (float) $filters['rating_from'] : null,
            sort: ProductSort::tryFrom((string) ($filters['sort'] ?? ProductSort::Newest->value)) ?? ProductSort::Newest,
            perPage: $perPage,
            page: $page,
        );
    }

    private function toResponse(ProductPage $page, Request $request): array
    {
        return [
            'current_page' => $page->currentPage,
            'data' => array_map(fn (Product $product) => $this->serializeProduct($product), $page->items),
            'from' => $page->from(),
            'last_page' => $page->lastPage(),
            'path' => $request->url(),
            'per_page' => $page->perPage,
            'to' => $page->to(),
            'total' => $page->total,
        ];
    }

    private function serializeProduct(Product $product): array
    {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'price' => $product->price,
            'category_id' => $product->categoryId,
            'in_stock' => $product->inStock,
            'rating' => $product->rating,
            'created_at' => $product->createdAt?->format(DATE_ATOM),
            'updated_at' => $product->updatedAt?->format(DATE_ATOM),
        ];
    }
}
