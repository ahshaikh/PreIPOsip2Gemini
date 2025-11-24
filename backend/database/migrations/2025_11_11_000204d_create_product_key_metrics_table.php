<?php
// Split from: 2025_11_11_000204_create_products_table.php
// Table: product_key_metrics

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('product_key_metrics')) {
            Schema::create('product_key_metrics', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained()->onDelete('cascade');
                $table->string('metric_name');
                $table->string('value');
                $table->string('unit');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('product_key_metrics');
    }
};
