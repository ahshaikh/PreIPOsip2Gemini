<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Canonical Version - Deterministic
 *
 * Adds approval and review tracking fields to deal_approvals.
 * No defensive schema checks.
 * Fresh rebuild safe.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deal_approvals', function (Blueprint $table) {

            $table->foreignId('reviewed_by')
                ->nullable()
                ->after('status')
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('review_started_at')
                ->nullable()
                ->after('reviewed_by');

            $table->foreignId('approved_by')
                ->nullable()
                ->after('review_started_at')
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('approved_at')
                ->nullable()
                ->after('approved_by');

            $table->foreignId('rejected_by')
                ->nullable()
                ->after('approved_at')
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('rejected_at')
                ->nullable()
                ->after('rejected_by');

            // Let Laravel generate index names
            $table->index('approved_at');
            $table->index('review_started_at');
        });
    }

    public function down(): void
    {
        Schema::table('deal_approvals', function (Blueprint $table) {

            // Drop foreign keys first
            $table->dropForeign(['reviewed_by']);
            $table->dropForeign(['approved_by']);
            $table->dropForeign(['rejected_by']);

            // Drop indexes using column-based syntax
            $table->dropIndex(['approved_at']);
            $table->dropIndex(['review_started_at']);

            $table->dropColumn([
                'rejected_at',
                'rejected_by',
                'approved_at',
                'approved_by',
                'review_started_at',
                'reviewed_by',
            ]);
        });
    }
};
