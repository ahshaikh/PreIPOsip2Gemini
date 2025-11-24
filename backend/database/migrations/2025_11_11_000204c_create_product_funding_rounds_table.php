<?php
// Split from: 2025_11_11_000204_create_products_table.php
// Table: product_funding_rounds

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('product_funding_rounds')) {
            Schema::create('product_funding_rounds', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained()->onDelete('cascade');
                $table->string('round_name');
                $table->date('date');
                $table->decimal('amount', 14, 2);
                $table->decimal('valuation', 14, 2);
                $table->text('investors');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('product_funding_rounds');
    }
};
