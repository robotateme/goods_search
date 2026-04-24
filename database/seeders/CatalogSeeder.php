<?php

declare(strict_types=1);

namespace Database\Seeders;

use Application\Contracts\Catalog\CatalogSeeder as CatalogSeederContract;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CatalogSeeder extends Seeder
{
    use WithoutModelEvents;

    public function __construct(
        private readonly int $categoriesCount = 12,
        private readonly int $productsCount = 5000,
        private readonly ?CatalogSeederContract $catalogSeeder = null,
    ) {}

    public function run(): void
    {
        ($this->catalogSeeder ?? app(CatalogSeederContract::class))
            ->seed($this->productsCount, $this->categoriesCount);
    }
}
