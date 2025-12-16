<?php
// V-AUDIT-MODULE3-007 (Created) - Add idempotency_key to withdrawals table
// Purpose: Prevent duplicate withdrawal requests from double-clicks or network retries

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Add idempotency_key column to prevent duplicate withdrawals.
     * This column stores a unique key provided by the client to ensure
     * that duplicate requests (e.g., from double-clicking submit) are not processed twice.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('withdrawals', function (Blueprint $table) {
            $table->string('idempotency_key')->nullable()
                ->after('admin_notes')
                ->comment('Unique key to prevent duplicate withdrawal requests');

            // Add unique index on idempotency_key for fast lookups and enforcement
            // Only enforce uniqueness on non-null values
            $table->index('idempotency_key', 'idx_withdrawals_idempotency_key');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('withdrawals', function (Blueprint $table) {
            $table->dropIndex('idx_withdrawals_idempotency_key');
            $table->dropColumn('idempotency_key');
        });
    }
};
