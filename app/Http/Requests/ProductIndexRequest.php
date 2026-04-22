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
     *     in_stock?: bool|string|null,
     *     rating_from?: float|int|string|null,
     *     sort?: string|null,
     *     page?: int|string|null,
     *     per_page?: int|string|null
     * }
     */
    public function filters(): array
    {
        $filters = $this->validated();

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
}
