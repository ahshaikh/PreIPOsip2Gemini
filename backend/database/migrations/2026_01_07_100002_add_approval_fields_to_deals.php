<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FIX 6 (P1): Deal Approval Workflow
 *
 * Adds explicit approval/rejection tracking for company-created deals
 * Ensures admin review before deals are visible to investors
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deals', function (Blueprint $table) {
            // Approval tracking
            $table->unsignedBigInteger('approved_by_admin_id')->nullable()->after('status');
            $table->timestamp('approved_at')->nullable()->after('approved_by_admin_id');

            // Rejection tracking
            $table->unsignedBigInteger('rejected_by_admin_id')->nullable()->after('approved_at');
            $table->timestamp('rejected_at')->nullable()->after('rejected_by_admin_id');
            $table->text('rejection_reason')->nullable()->after('rejected_at');

            // Foreign keys
            $table->foreign('approved_by_admin_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');

            $table->foreign('rejected_by_admin_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');

            // Indexes
            $table->index('approved_at');
            $table->index('rejected_at');
        });
    }

    public function down(): void
    {
        Schema::table('deals', function (Blueprint $table) {
            $table->dropForeign(['approved_by_admin_id']);
            $table->dropForeign(['rejected_by_admin_id']);
            $table->dropIndex(['approved_at']);
            $table->dropIndex(['rejected_at']);
            $table->dropColumn([
                'approved_by_admin_id',
                'approved_at',
                'rejected_by_admin_id',
                'rejected_at',
                'rejection_reason',
            ]);
        });
    }
};
