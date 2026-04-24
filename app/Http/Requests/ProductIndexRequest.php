<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Domain\Product\Search\ProductSort;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class ProductIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:255'],
            'price_from' => ['nullable', 'numeric', 'min:0'],
            'price_to' => ['nullable', 'numeric', 'min:0', 'gte:price_from'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'in_stock' => ['nullable', 'string'],
            'rating_from' => ['nullable', 'numeric', 'between:0,5'],
            'sort' => ['nullable', 'string', Rule::in([
                ProductSort::PriceAsc->value,
                ProductSort::PriceDesc->value,
                ProductSort::RatingDesc->value,
                ProductSort::Newest->value,
            ])],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * @return array{
     *     q?: string|null,
     *     price_from?: float|int|string|null,
     *     price_to?: float|int|string|null,
     *     category_id?: int|string|null,
     *     in_stock?: bool|null,
     *     rating_from?: float|int|string|null,
     *     sort?: string|null,
     *     page?: int|string|null,
     *     per_page?: int|string|null
     * }
     */
    public function filters(): array
    {
        $validated = $this->validated();

        if (! is_array($validated)) {
            throw ValidationException::withMessages([
                'filters' => 'Validated product filters payload is invalid.',
            ]);
        }

        $filters = [];

        if (array_key_exists('q', $validated)) {
            $filters['q'] = $this->nullableString($validated['q']);
        }

        if (array_key_exists('price_from', $validated)) {
            $filters['price_from'] = $this->nullableNumeric($validated['price_from']);
        }

        if (array_key_exists('price_to', $validated)) {
            $filters['price_to'] = $this->nullableNumeric($validated['price_to']);
        }

        if (array_key_exists('category_id', $validated)) {
            $filters['category_id'] = $this->nullableIntLike($validated['category_id']);
        }

        if (array_key_exists('in_stock', $validated)) {
            $filters['in_stock'] = $this->nullableString($validated['in_stock']);
        }

        if (array_key_exists('rating_from', $validated)) {
            $filters['rating_from'] = $this->nullableNumeric($validated['rating_from']);
        }

        if (array_key_exists('sort', $validated)) {
            $filters['sort'] = $this->nullableString($validated['sort']);
        }

        if (array_key_exists('page', $validated)) {
            $filters['page'] = $this->nullableIntLike($validated['page']);
        }

        if (array_key_exists('per_page', $validated)) {
            $filters['per_page'] = $this->nullableIntLike($validated['per_page']);
        }

        if (array_key_exists('in_stock', $filters)) {
            $normalizedInStock = filter_var($filters['in_stock'], FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);

            if ($normalizedInStock === null) {
                throw ValidationException::withMessages([
                    'in_stock' => 'The in stock field must be true or false.',
                ]);
            }

            $filters['in_stock'] = $normalizedInStock;
        }

        return $filters;
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null || is_string($value)) {
            return $value;
        }

        throw ValidationException::withMessages([
            'filters' => 'Expected string filter value.',
        ]);
    }

    private function nullableNumeric(mixed $value): int|float|string|null
    {
        if ($value === null || is_int($value) || is_float($value) || is_string($value)) {
            return $value;
        }

        throw ValidationException::withMessages([
            'filters' => 'Expected numeric filter value.',
        ]);
    }

    private function nullableIntLike(mixed $value): int|string|null
    {
        if ($value === null || is_int($value) || is_string($value)) {
            return $value;
        }

        throw ValidationException::withMessages([
            'filters' => 'Expected integer filter value.',
        ]);
    }
}
