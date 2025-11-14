<?php
// V-FINAL-1730-354

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // A unique, non-guessable ID for receipts/tracking
            $table->uuid('transaction_id')->unique()->after('id');
            
            // For 2-step transactions (like Withdrawals)
            $table->string('status')->default('completed')->after('type'); 
            
            // For a true auditable ledger
            $table->decimal('balance_before', 14, 2)->default(0)->after('amount');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['transaction_id', 'status', 'balance_before']);
        });
    }
};