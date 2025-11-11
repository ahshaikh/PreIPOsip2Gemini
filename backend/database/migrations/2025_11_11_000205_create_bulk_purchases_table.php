// V-PHASE2-1730-029
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * This is the core business model inventory table.
     */
    public function up(): void
    {
        Schema::create('bulk_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('restrict');
            $table->foreignId('admin_id')->constrained('users')->onDelete('restrict');
            
            $table->decimal('face_value_purchased', 14, 2);
            $table->decimal('actual_cost_paid', 14, 2);
            $table->decimal('discount_percentage', 5, 2);
            $table->decimal('extra_allocation_percentage', 5, 2);
            
            $table->decimal('total_value_received', 14, 2); // (face_value * (1 + extra_alloc/100))
            $table->decimal('value_remaining', 14, 2); // Starts as total_value_received, decrements
            
            $table->string('seller_name')->nullable();
            $table->date('purchase_date');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bulk_purchases');
    }
};