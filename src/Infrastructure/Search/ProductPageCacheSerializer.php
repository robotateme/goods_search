<?php

declare(strict_types=1);

namespace Infrastructure\Search;

use DateTimeImmutable;
use Domain\Product\Entity\Product;
use Domain\Product\Search\ProductPage;
use Domain\Product\ValueObject\CategoryId;
use Domain\Product\ValueObject\Page;
use Domain\Product\ValueObject\PerPage;
use Domain\Product\ValueObject\Price;
use Domain\Product\ValueObject\ProductId;
use Domain\Product\ValueObject\Rating;
use InvalidArgumentException;

final class ProductPageCacheSerializer
{
    /**
     * @return array{
     *     total: int,
     *     per_page: int,
     *     current_page: int,
     *     items: list<array{
     *         id: int,
     *         name: string,
     *         price: string,
     *         category_id: int,
     *         in_stock: bool,
     *         rating: float,
     *         created_at: string|null,
     *         updated_at: string|null
     *     }>
     * }
     */
    public function serialize(ProductPage $page): array
    {
        return [
            'total' => $page->total,
            'per_page' => $page->perPage->value(),
            'current_page' => $page->currentPage->value(),
            'items' => array_map(
                fn (Product $product): array => [
                    'id' => $product->id->value(),
                    'name' => $product->name,
                    'price' => $product->price->value(),
                    'category_id' => $product->categoryId->value(),
                    'in_stock' => $product->inStock,
                    'rating' => $product->rating->value(),
                    'created_at' => $product->createdAt?->format(DATE_ATOM),
                    'updated_at' => $product->updatedAt?->format(DATE_ATOM),
                ],
                $page->items,
            ),
        ];
    }

    /**
     * @param  array<mixed, mixed>  $payload
     */
    public function deserialize(array $payload): ProductPage
    {
        if (
            ! isset($payload['total'], $payload['per_page'], $payload['current_page'], $payload['items'])
            || ! is_int($payload['total'])
            || ! is_int($payload['per_page'])
            || ! is_int($payload['current_page'])
            || ! is_array($payload['items'])
        ) {
            throw new InvalidArgumentException('Cached product page payload is invalid.');
        }

        $items = [];

        foreach ($payload['items'] as $item) {
            if (! is_array($item)) {
                throw new InvalidArgumentException('Cached product page item payload is invalid.');
            }

            $items[] = new Product(
                id: new ProductId($this->int($item['id'] ?? null)),
                name: $this->string($item['name'] ?? null),
                price: new Price($this->string($item['price'] ?? null)),
                categoryId: new CategoryId($this->int($item['category_id'] ?? null)),
                inStock: $this->bool($item['in_stock'] ?? null),
                rating: new Rating($this->float($item['rating'] ?? null)),
                createdAt: $this->dateTime($item['created_at'] ?? null),
                updatedAt: $this->dateTime($item['updated_at'] ?? null),
            );
        }

        return new ProductPage(
            items: $items,
            total: $payload['total'],
            perPage: new PerPage($payload['per_page']),
            currentPage: new Page($payload['current_page']),
        );
    }

    private function int(mixed $value): int
    {
        if (! is_int($value)) {
            throw new InvalidArgumentException('Expected int value in cached product page.');
        }

        return $value;
    }

    private function string(mixed $value): string
    {
        if (! is_string($value)) {
            throw new InvalidArgumentException('Expected string value in cached product page.');
        }

        return $value;
    }

    private function bool(mixed $value): bool
    {
        if (! is_bool($value)) {
            throw new InvalidArgumentException('Expected bool value in cached product page.');
        }

        return $value;
    }

    private function float(mixed $value): float
    {
        if (! is_float($value) && ! is_int($value)) {
            throw new InvalidArgumentException('Expected float value in cached product page.');
        }

        return (float) $value;
    }

    private function dateTime(mixed $value): ?DateTimeImmutable
    {
        if ($value === null) {
            return null;
        }

        if (! is_string($value)) {
            throw new InvalidArgumentException('Expected datetime string in cached product page.');
        }

        return new DateTimeImmutable($value);
    }
}
