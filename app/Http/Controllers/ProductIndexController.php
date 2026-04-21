<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProductIndexController extends Controller
{
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

        $query = Product::query()
            ->with('category')
            ->searchName($filters['q'] ?? null)
            ->when(isset($filters['price_from']), fn ($builder) => $builder->where('price', '>=', $filters['price_from']))
            ->when(isset($filters['price_to']), fn ($builder) => $builder->where('price', '<=', $filters['price_to']))
            ->when(isset($filters['category_id']), fn ($builder) => $builder->where('category_id', $filters['category_id']))
            ->when(isset($filters['in_stock']), fn ($builder) => $builder->where('in_stock', $filters['in_stock']))
            ->when(isset($filters['rating_from']), fn ($builder) => $builder->where('rating', '>=', $filters['rating_from']));

        match ($filters['sort'] ?? 'newest') {
            'price_asc' => $query->orderBy('price')->orderBy('id'),
            'price_desc' => $query->orderByDesc('price')->orderBy('id'),
            'rating_desc' => $query->orderByDesc('rating')->orderBy('id'),
            default => $query->orderByDesc('created_at')->orderByDesc('id'),
        };

        $paginator = $query
            ->paginate($filters['per_page'] ?? 15)
            ->appends($request->query());

        return response()->json($paginator);
    }
}
