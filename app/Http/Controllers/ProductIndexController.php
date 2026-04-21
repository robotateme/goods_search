<?php

namespace App\Http\Controllers;

use Application\Handlers\SearchProductsHandler;
use Application\Queries\SearchProductsQuery;
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

        $paginator = $this->handler
            ->handle(new SearchProductsQuery(
                $filters,
                $filters['per_page'] ?? 15,
                $filters['page'] ?? 1,
            ))
            ->appends($request->query());

        return response()->json($paginator);
    }
}
