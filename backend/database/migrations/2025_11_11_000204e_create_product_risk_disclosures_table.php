<?php
// Split from: 2025_11_11_000204_create_products_table.php
// Table: product_risk_disclosures

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('product_risk_disclosures')) {
            Schema::create('product_risk_disclosures', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained()->onDelete('cascade');
                $table->string('risk_category');
                $table->string('severity');
                $table->string('risk_title');
                $table->text('risk_description');
                $table->integer('display_order')->default(0);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('product_risk_disclosures');
    }
};
