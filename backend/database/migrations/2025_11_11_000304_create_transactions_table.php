// V-PHASE3-1730-065
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('type'); // deposit, withdrawal, bonus, investment, refund
            $table->decimal('amount', 14, 2); // Positive for credit, negative for debit
            $table->decimal('balance_after', 14, 2);
            $table->string('description');
            $table->morphs('reference'); // Can link to Payment, Withdrawal, Bonus, etc.
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};