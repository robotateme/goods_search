<?php
declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CatalogSeeder extends Seeder
{
    use WithoutModelEvents;

    public function __construct(
        private readonly int $categoriesCount = 12,
        private readonly int $productsCount = 5000,
    ) {
    }

    public function run(): void
    {
        $categories = Category::factory()
            ->count($this->categoriesCount)
            ->create();

        $categoryIds = $categories->modelKeys();
        $faker = fake();
        $chunkSize = 1000;
        $remaining = $this->productsCount;

        while ($remaining > 0) {
            $batchSize = min($chunkSize, $remaining);
            $rows = [];

            for ($index = 0; $index < $batchSize; $index++) {
                $timestamp = now();

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

    private function generateProductName(\Faker\Generator $faker, int $index, int $remaining): string
    {
        $keyword = $faker->randomElement([
            'Mouse',
            'Keyboard',
            'Monitor',
            'Headphones',
            'Router',
            'Speaker',
        ]);
        $prefix = $faker->randomElement([
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
        ]);

        return sprintf('%s %s %d%s', $prefix, $keyword, $remaining, chr(65 + ($index % 26)));
    }
}
