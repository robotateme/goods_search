<?php
declare(strict_types=1);

namespace App\Jobs;

use App\Models\ProductSearchRequest;
use Application\Handlers\SearchProductsHandler;
use Application\Queries\SearchProductsQuery;
use Domain\Product\Product;
use Domain\Product\ProductPage;
use Domain\Product\ProductSearchCriteria;
use Domain\Product\ProductSort;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class RunProductSearchJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $searchRequestId,
    ) {
    }

    public function handle(SearchProductsHandler $handler): void
    {
        $searchRequest = ProductSearchRequest::query()->find($this->searchRequestId);

        if ($searchRequest === null) {
            return;
        }

        $searchRequest->forceFill([
            'status' => ProductSearchRequest::STATUS_PROCESSING,
            'error' => null,
        ])->save();

        try {
            $criteria = $searchRequest->criteria;
            $result = $handler->handle(new SearchProductsQuery(
                $this->mapCriteria($criteria),
            ));

            $searchRequest->forceFill([
                'status' => ProductSearchRequest::STATUS_COMPLETED,
                'result' => $this->serializePage($result),
                'completed_at' => now(),
            ])->save();
        } catch (Throwable $exception) {
            $searchRequest->forceFill([
                'status' => ProductSearchRequest::STATUS_FAILED,
                'error' => $exception->getMessage(),
                'completed_at' => now(),
            ])->save();

            throw $exception;
        }
    }

    private function mapCriteria(array $criteria): ProductSearchCriteria
    {
        return new ProductSearchCriteria(
            query: $criteria['query'] ?? null,
            priceFrom: isset($criteria['price_from']) ? (float) $criteria['price_from'] : null,
            priceTo: isset($criteria['price_to']) ? (float) $criteria['price_to'] : null,
            categoryId: isset($criteria['category_id']) ? (int) $criteria['category_id'] : null,
            inStock: array_key_exists('in_stock', $criteria) && $criteria['in_stock'] !== null ? (bool) $criteria['in_stock'] : null,
            ratingFrom: isset($criteria['rating_from']) ? (float) $criteria['rating_from'] : null,
            sort: ProductSort::from((string) $criteria['sort']),
            perPage: (int) $criteria['per_page'],
            page: (int) $criteria['page'],
        );
    }

    private function serializePage(ProductPage $page): array
    {
        return [
            'current_page' => $page->currentPage,
            'data' => array_map(fn (Product $product) => $this->serializeProduct($product), $page->items),
            'from' => $page->from(),
            'last_page' => $page->lastPage(),
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
