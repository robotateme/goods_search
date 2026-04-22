<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->index(['price', 'id'], 'products_price_id_idx');
            $table->index(['rating', 'id'], 'products_rating_id_idx');
            $table->index(['created_at', 'id'], 'products_created_at_id_idx');
            $table->index(['category_id', 'in_stock', 'price', 'id'], 'products_category_stock_price_id_idx');
            $table->index(['category_id', 'in_stock', 'rating', 'id'], 'products_category_stock_rating_id_idx');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropIndex('products_price_id_idx');
            $table->dropIndex('products_rating_id_idx');
            $table->dropIndex('products_created_at_id_idx');
            $table->dropIndex('products_category_stock_price_id_idx');
            $table->dropIndex('products_category_stock_rating_id_idx');
        });
    }
};
