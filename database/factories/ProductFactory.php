<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Infrastructure\Database\Eloquent\Category;
use App\Infrastructure\Database\Eloquent\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    #[Override]
    public function definition(): array
    {
        $prefix = fake()->randomElement([
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
        $productType = fake()->randomElement([
            'Mouse',
            'Keyboard',
            'Monitor',
            'Headphones',
            'Speaker',
            'Router',
            'Laptop Stand',
            'Webcam',
            'Microphone',
            'SSD',
        ]);

        return [
            'name' => fake()->unique()->bothify($prefix.' '.$productType.' ##??'),
            'price' => fake()->randomFloat(2, 10, 1000),
            'category_id' => Category::factory(),
            'in_stock' => fake()->boolean(),
            'rating' => fake()->randomFloat(1, 0, 5),
        ];
    }
}
