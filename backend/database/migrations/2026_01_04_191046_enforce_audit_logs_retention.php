<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('audit_logs')) {
            return;
        }

        Schema::table('audit_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('audit_logs', 'is_archived')) {
                $table->boolean('is_archived')->default(false)->after('requires_review');
            }

            if (!Schema::hasColumn('audit_logs', 'archived_at')) {
                $table->timestamp('archived_at')->nullable()->after('is_archived');
            }

            if (!Schema::hasColumn('audit_logs', 'retention_period')) {
                $table->string('retention_period')
                    ->default('permanent')
                    ->comment('permanent, 7years, etc.')
                    ->after('archived_at');
            }
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->index('is_archived');
        });

        // Enforce valid risk levels at DB level
        DB::statement("
            ALTER TABLE audit_logs
            ADD CONSTRAINT check_audit_logs_valid_risk_level
            CHECK (risk_level IN ('low','medium','high','critical'))
        ");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE audit_logs DROP CONSTRAINT IF EXISTS check_audit_logs_valid_risk_level");

        Schema::table('audit_logs', function (Blueprint $table) {
            if (Schema::hasColumn('audit_logs', 'is_archived')) {
                $table->dropIndex(['is_archived']);
                $table->dropColumn('is_archived');
            }

            if (Schema::hasColumn('audit_logs', 'archived_at')) {
                $table->dropColumn('archived_at');
            }

            if (Schema::hasColumn('audit_logs', 'retention_period')) {
                $table->dropColumn('retention_period');
            }
        });
    }
};
