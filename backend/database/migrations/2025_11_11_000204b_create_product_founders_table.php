<?php
// Split from: 2025_11_11_000204_create_products_table.php
// Table: product_founders

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('product_founders')) {
            Schema::create('product_founders', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained()->onDelete('cascade');
                $table->string('name');
                $table->string('title');
                $table->string('photo_url')->nullable();
                $table->string('linkedin_url')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('product_founders');
    }
};
