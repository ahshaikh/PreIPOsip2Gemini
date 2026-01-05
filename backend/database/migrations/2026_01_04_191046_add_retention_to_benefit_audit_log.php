<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('benefit_audit_log')) {
            return;
        }

        Schema::table('benefit_audit_log', function (Blueprint $table) {
            if (!Schema::hasColumn('benefit_audit_log', 'is_archived')) {
                $table->boolean('is_archived')->default(false)->after('created_at');
            }

            if (!Schema::hasColumn('benefit_audit_log', 'archived_at')) {
                $table->timestamp('archived_at')->nullable()->after('is_archived');
            }
        });

        Schema::table('benefit_audit_log', function (Blueprint $table) {
            $table->index('is_archived');
        });
    }

    public function down(): void
    {
        Schema::table('benefit_audit_log', function (Blueprint $table) {
            if (Schema::hasColumn('benefit_audit_log', 'is_archived')) {
                $table->dropIndex(['is_archived']);
                $table->dropColumn('is_archived');
            }

            if (Schema::hasColumn('benefit_audit_log', 'archived_at')) {
                $table->dropColumn('archived_at');
            }
        });
    }
};
