<?php
// V-PHASE3-1730-064 (Created) | V-FINAL-1730-615 (Consolidated)

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('balance', 14, 2)->default(0);
            $table->decimal('locked_balance', 14, 2)->default(0);
            $table->timestamps();
        });
        
// V-PHASE3-1730-065
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('transaction_id')->unique();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('wallet_id')->constrained()->onDelete('cascade');
            
            $table->string('type'); // deposit, withdrawal, bonus, etc.
            $table->string('status')->default('completed'); // pending, completed
            
            $table->decimal('amount', 14, 2);
            $table->decimal('balance_before', 14, 2)->default(0);
            $table->decimal('balance_after', 14, 2);
            
            $table->decimal('tds_deducted', 10, 2)->default(0);
            $table->text('description');

            $table->nullableMorphs('reference'); // Links to Payment, Withdrawal, Bonus
            $table->timestamps();
        });

// V-PHASE3-1730-066
        Schema::create('withdrawals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('wallet_id')->constrained()->onDelete('cascade');
            
            $table->decimal('amount', 10, 2);
            $table->decimal('fee', 10, 2)->default(0);
            $table->decimal('tds_deducted', 10, 2)->default(0);
            $table->decimal('net_amount', 10, 2); // amount - fee - tds
            
            $table->string('status')->default('pending');
            $table->json('bank_details');
            
            $table->foreignId('admin_id')->nullable()->constrained('users');
            $table->string('utr_number')->nullable();
            $table->text('rejection_reason')->nullable();
            
            $table->timestamps();
        });
        
        DB::statement('ALTER TABLE wallets ADD CONSTRAINT balance_must_be_positive CHECK (balance >= 0)');
        DB::statement('ALTER TABLE wallets ADD CONSTRAINT locked_balance_must_be_positive CHECK (locked_balance >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('withdrawals');
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('wallets');
    }
};