<?php
// V-FINAL-1730-507 (Created)

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * FSD-PROD-009: Risk Disclosures
     */
    public function up(): void
    {
        Schema::create('product_risk_disclosures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('risk_category'); // Market, Business, Financial
            $table->string('severity'); // Low, Medium, High, Critical
            $table->string('risk_title');
            $table->text('risk_description');
            $table->integer('display_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_risk_disclosures');
    }
};