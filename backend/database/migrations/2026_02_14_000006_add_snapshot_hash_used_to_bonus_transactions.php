<?php
// V-CONTRACT-HARDENING-FINAL: Track which snapshot hash was used for each bonus calculation
// This creates an immutable audit trail linking bonus awards to specific contract versions.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bonus_transactions', function (Blueprint $table) {
            // The SHA256 hash (32 chars) of the snapshot used at calculation time
            // This MUST match subscription.config_snapshot_version for integrity verification
            $table->char('snapshot_hash_used', 32)
                ->nullable()
                ->after('override_delta')
                ->comment('V-CONTRACT-HARDENING-FINAL: SHA256 hash of snapshot config at calculation time');

            // Index for audit queries
            $table->index('snapshot_hash_used', 'idx_bonus_transactions_snapshot_hash');
        });
    }

    public function down(): void
    {
        Schema::table('bonus_transactions', function (Blueprint $table) {
            $table->dropIndex('idx_bonus_transactions_snapshot_hash');
            $table->dropColumn('snapshot_hash_used');
        });
    }
};
