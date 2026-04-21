<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->decimal('price', 10, 2);
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->boolean('in_stock')->default(true);
            $table->float('rating');
            $table->timestamps();

            $table->index('price');
            $table->index('category_id');
            $table->index('in_stock');
            $table->index('rating');
            $table->index('created_at');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');
            DB::statement('CREATE INDEX products_name_trgm_idx ON products USING gin (name gin_trgm_ops)');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS products_name_trgm_idx');
        }

        Schema::dropIfExists('products');
    }
};
