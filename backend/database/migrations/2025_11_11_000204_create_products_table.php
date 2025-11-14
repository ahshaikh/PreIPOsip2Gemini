<?php
// V-PHASE2-1730-036 (Created) | V-FINAL-1730-410 (SoftDeletes Added)

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('sector')->nullable();
            
            $table->decimal('face_value_per_unit', 10, 2);
            $table->decimal('current_market_price', 10, 2)->nullable();
            $table->decimal('min_investment', 10, 2);
            
            $table->date('expected_ipo_date')->nullable();
            $table->string('status')->default('active'); // active, upcoming, listed, closed
            
            $table->boolean('is_featured')->default(false);
            $table->integer('display_order')->default(0);
            
            $table->json('description')->nullable(); // For {summary}, {highlights}, etc.
            
            $table->timestamps();
            $table->softDeletes(); // <-- ADD THIS LINE
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};