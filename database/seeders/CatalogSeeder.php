<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Faker\Generator;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CatalogSeeder extends Seeder
{
    use WithoutModelEvents;

    private const DEFAULT_CHUNK_SIZE = 1000;

    private const SEARCH_KEYWORDS = [
        'Mouse',
        'Keyboard',
        'Monitor',
        'Headphones',
        'Router',
        'Speaker',
    ];

    private const PRODUCT_PREFIXES = [
        'Wireless',
        'Smart',
        'Ultra',
        'Portable',
        'Gaming',
        'Compact',
        'Premium',
        'Budget',
        'Ergonomic',
        'Pro',
    ];

    public function __construct(
        private readonly int $categoriesCount = 12,
        private readonly int $productsCount = 5000,
    ) {}

    public function run(): void
    {
        Category::factory()
            ->count($this->categoriesCount)
            ->create();

        $categoryIds = Category::query()->pluck('id')->all();
        $faker = fake();
        $remaining = $this->productsCount;

        while ($remaining > 0) {
            $batchSize = min(self::DEFAULT_CHUNK_SIZE, $remaining);
            $rows = [];
            $timestamp = now();

            for ($index = 0; $index < $batchSize; $index++) {
                $rows[] = [
                    'name' => $this->generateProductName($faker, $index, $remaining),
                    'price' => $faker->randomFloat(2, 10, 1000),
                    'category_id' => $faker->randomElement($categoryIds),
                    'in_stock' => $faker->boolean(80),
                    'rating' => $faker->randomFloat(1, 2.5, 5),
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
            }

            Product::query()->insert($rows);
            $remaining -= $batchSize;
        }
    }

    private function generateProductName(Generator $faker, int $index, int $remaining): string
    {
        $keyword = $faker->randomElement(self::SEARCH_KEYWORDS);
        $prefix = $faker->randomElement(self::PRODUCT_PREFIXES);

        return sprintf('%s %s %d%s', $prefix, $keyword, $remaining, chr(65 + ($index % 26)));
    }
}
