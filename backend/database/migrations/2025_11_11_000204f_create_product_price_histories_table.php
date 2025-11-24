<?php
// Split from: 2025_11_11_000204_create_products_table.php
// Table: product_price_histories

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('product_price_histories')) {
            Schema::create('product_price_histories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained()->onDelete('cascade');
                $table->decimal('price', 10, 2);
                $table->date('recorded_at');
                $table->timestamps();
                $table->unique(['product_id', 'recorded_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('product_price_histories');
    }
};
