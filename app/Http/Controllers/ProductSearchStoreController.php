<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Jobs\RunProductSearchJob;
use App\Models\ProductSearchRequest;
use Application\Contracts\Queue\QueueBus;
use Domain\Product\ProductSort;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProductSearchStoreController extends Controller
{
    public function __construct(
        private readonly QueueBus $queueBus,
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

        $searchRequest = ProductSearchRequest::query()->create([
            'uuid' => (string) Str::uuid(),
            'status' => ProductSearchRequest::STATUS_PENDING,
            'criteria' => [
                'query' => $filters['q'] ?? null,
                'price_from' => isset($filters['price_from']) ? (float) $filters['price_from'] : null,
                'price_to' => isset($filters['price_to']) ? (float) $filters['price_to'] : null,
                'category_id' => isset($filters['category_id']) ? (int) $filters['category_id'] : null,
                'in_stock' => $filters['in_stock'] ?? null,
                'rating_from' => isset($filters['rating_from']) ? (float) $filters['rating_from'] : null,
                'sort' => ProductSort::tryFrom((string) ($filters['sort'] ?? ProductSort::Newest->value))?->value ?? ProductSort::Newest->value,
                'page' => isset($filters['page']) ? (int) $filters['page'] : 1,
                'per_page' => isset($filters['per_page']) ? (int) $filters['per_page'] : 15,
            ],
        ]);

        $this->queueBus->dispatch(new RunProductSearchJob($searchRequest->id));

        return response()->json([
            'id' => $searchRequest->uuid,
            'status' => $searchRequest->status,
            'status_url' => route('product-searches.show', $searchRequest),
        ], 202);
    }
}
