// V-PHASE3-1730-066
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('withdrawals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('wallet_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 14, 2);
            $table->decimal('fee', 14, 2)->default(0.00);
            $table->decimal('net_amount', 14, 2);
            $table->string('status')->default('pending'); // pending, approved, processing, completed, rejected
            $table->json('bank_details');
            $table->foreignId('admin_id')->nullable()->constrained('users'); // Admin who approved/rejected
            $table->string('utr_number')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('withdrawals');
    }
};