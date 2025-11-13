<?php
// V-REMEDIATE-1730-192

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Store path to the uploaded screenshot
            $table->string('payment_proof_path')->nullable()->after('gateway_order_id');
            // Allow status 'pending_approval'
            // We don't need a schema change for string values, but we note it here.
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('payment_proof_path');
        });
    }
};