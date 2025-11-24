<?php
// Split from: 2025_11_11_000204_create_products_table.php
// Table: product_highlights

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('product_highlights')) {
            Schema::create('product_highlights', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained()->onDelete('cascade');
                $table->string('content');
                $table->integer('display_order')->default(0);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('product_highlights');
    }
};
