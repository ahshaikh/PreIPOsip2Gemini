<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FIX 5 (P1): Company Data Immutability Post-Purchase
 *
 * Adds freeze mechanism to prevent retroactive disclosure changes
 * after company data is used in BulkPurchase/Deal
 */
return new class extends Migration
{
    public function up(): void
    {
        // Add freeze tracking to companies table
        Schema::table('companies', function (Blueprint $table) {
            $table->timestamp('frozen_at')->nullable()->after('is_verified');
            $table->unsignedBigInteger('frozen_by_admin_id')->nullable()->after('frozen_at');

            $table->foreign('frozen_by_admin_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');

            $table->index('frozen_at');
        });

        // Create company snapshots table for audit trail
        Schema::create('company_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('company_share_listing_id')->nullable()->constrained();
            $table->foreignId('bulk_purchase_id')->nullable()->constrained();
            $table->json('snapshot_data'); // Full company data at freeze time
            $table->string('snapshot_reason'); // 'listing_approval', 'deal_launch', etc.
            $table->timestamp('snapshot_at');
            $table->unsignedBigInteger('snapshot_by_admin_id')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'snapshot_at']);
            $table->foreign('snapshot_by_admin_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_snapshots');

        Schema::table('companies', function (Blueprint $table) {
            $table->dropForeign(['frozen_by_admin_id']);
            $table->dropIndex(['frozen_at']);
            $table->dropColumn(['frozen_at', 'frozen_by_admin_id']);
        });
    }
};
