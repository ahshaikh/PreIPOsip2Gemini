<?php
// V-FINAL-1730-577 (Created)

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * FSD-PLAN-017: Proration & Refund Tracking
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // e.g., 'upgrade_charge', 'downgrade_credit'
            $table->string('payment_type')->default('sip_installment')->after('status');
            
            // Link to the payment this is a refund for
            $table->foreignId('refunds_payment_id')->nullable()->constrained('payments')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['refunds_payment_id']);
            $table->dropColumn(['payment_type', 'refunds_payment_id']);
        });
    }
};