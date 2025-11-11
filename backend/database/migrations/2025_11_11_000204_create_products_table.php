<?php
// V-PHASE2-1730-028

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "Swiggy"
            $table->string('slug')->unique();
            $table->string('company_logo')->nullable();
            $table->string('sector')->nullable();
            $table->decimal('face_value_per_unit', 10, 2);
            $table->decimal('min_investment', 10, 2)->default(0);
            $table->date('expected_ipo_date')->nullable();
            $table->json('description')->nullable(); // For details, financials, risks
            $table->string('status')->default('active'); // active, upcoming, listed
            $table->boolean('is_featured')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};