<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('products') || $this->isIntegerColumn()) {
            return;
        }

        match (DB::getDriverName()) {
            'pgsql' => DB::statement('ALTER TABLE products ALTER COLUMN price TYPE integer USING ROUND(price * 100)::integer'),
            'mysql' => $this->convertMysqlToInteger(),
            'sqlite' => DB::statement('UPDATE products SET price = CAST(ROUND(price * 100) AS INTEGER)'),
            default => null,
        };
    }

    public function down(): void
    {
        if (! Schema::hasTable('products') || ! $this->isIntegerColumn()) {
            return;
        }

        match (DB::getDriverName()) {
            'pgsql' => DB::statement('ALTER TABLE products ALTER COLUMN price TYPE numeric(10, 2) USING (price::numeric / 100)'),
            'mysql' => $this->convertMysqlToDecimal(),
            'sqlite' => DB::statement('UPDATE products SET price = price / 100.0'),
            default => null,
        };
    }

    private function convertMysqlToInteger(): void
    {
        DB::statement('UPDATE products SET price = ROUND(price * 100)');
        DB::statement('ALTER TABLE products MODIFY price INT UNSIGNED NOT NULL');
    }

    private function convertMysqlToDecimal(): void
    {
        DB::statement('ALTER TABLE products MODIFY price DECIMAL(10, 2) NOT NULL');
        DB::statement('UPDATE products SET price = price / 100');
    }

    private function isIntegerColumn(): bool
    {
        return in_array(Schema::getColumnType('products', 'price'), ['integer', 'bigint', 'smallint'], true);
    }
};
