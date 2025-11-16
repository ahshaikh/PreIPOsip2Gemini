<?php
// V-PHASE2-1730-048 (Created) | V-PHASE3-1730-062 | V-FINAL-1730-607 (Currency Fix) | V-FINAL-1730-614 (Consolidated)

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('subscription_id')->constrained()->onDelete('cascade');
            
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('INR');
            $table->string('status')->default('pending');
            $table->string('payment_type')->default('sip_installment');
            
            $table->string('gateway')->nullable();
            $table->string('gateway_order_id')->nullable()->index();
            $table->string('gateway_payment_id')->nullable()->index();
            $table->text('gateway_signature')->nullable();
            
            $table->string('method')->nullable();
            $table->string('payment_proof_path')->nullable();
            
            $table->foreignId('refunds_payment_id')->nullable()->constrained('payments')->onDelete('set null');

            $table->timestamp('paid_at')->nullable();
            $table->boolean('is_on_time')->default(false);
            $table->boolean('is_flagged')->default(false);
            $table->text('flag_reason')->nullable();
            $table->integer('retry_count')->default(0);
            $table->text('failure_reason')->nullable();
            
            $table->timestamps();
        });
        
        DB::statement('ALTER TABLE payments ADD CONSTRAINT payment_amount_must_be_positive CHECK (amount >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};