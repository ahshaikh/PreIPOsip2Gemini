// V-PHASE3-1730-063
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_investments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('restrict');
            $table->foreignId('payment_id')->constrained()->onDelete('cascade');
            $table->foreignId('bulk_purchase_id')->constrained()->onDelete('restrict');
            
            $table->decimal('units_allocated', 14, 4);
            $table->decimal('value_allocated', 14, 2); // Face value
            $table->string('source'); // 'investment' or 'bonus'
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_investments');
    }
};