<?php
// V-DEPLOY-1730-013 (Created) | V-PHASE3-1730-066 | V-FINAL-1730-616 (Consolidated)

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bonus_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('subscription_id')->constrained()->onDelete('cascade');
            $table->foreignId('payment_id')->nullable()->constrained()->onDelete('set null');
            
            $table->string('type');
            $table->decimal('amount', 10, 2);
            $table->decimal('tds_deducted', 10, 2)->default(0);
            
            $table->decimal('multiplier_applied', 5, 2)->default(1.00);
            $table->decimal('base_amount', 10, 2)->nullable();
            $table->text('description');
            
            $table->timestamps();
        });

        Schema::create('referral_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->decimal('multiplier', 5, 2)->default(1.0);
            $table->decimal('bonus_amount', 10, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        
        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referrer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('referred_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('referral_campaign_id')->nullable()->constrained('referral_campaigns')->onDelete('set null');
            
            $table->string('status')->default('pending');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            
            $table->unique(['referrer_id', 'referred_id']);
            $table->unique('referred_id');
        });
        
        DB::statement('ALTER TABLE bonus_transactions ADD CONSTRAINT bonus_amount_not_zero CHECK (amount != 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('referrals');
        Schema::dropIfExists('referral_campaigns');
        Schema::dropIfExists('bonus_transactions');
    }
};