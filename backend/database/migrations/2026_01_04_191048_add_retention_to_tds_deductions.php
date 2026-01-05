<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tds_deductions')) {
            return;
        }

        Schema::table('tds_deductions', function (Blueprint $table) {
            if (!Schema::hasColumn('tds_deductions', 'is_archived')) {
                $table->boolean('is_archived')->default(false)->after('form_16a_generated_at');
            }

            if (!Schema::hasColumn('tds_deductions', 'archived_at')) {
                $table->timestamp('archived_at')->nullable()->after('is_archived');
            }

            if (!Schema::hasColumn('tds_deductions', 'retention_period')) {
                $table->string('retention_period')
                    ->default('7years')
                    ->comment('Income Tax Act retention')
                    ->after('archived_at');
            }
        });

        Schema::table('tds_deductions', function (Blueprint $table) {
            $table->index('is_archived');
        });
    }

    public function down(): void
    {
        Schema::table('tds_deductions', function (Blueprint $table) {
            if (Schema::hasColumn('tds_deductions', 'is_archived')) {
                $table->dropIndex(['is_archived']);
                $table->dropColumn('is_archived');
            }

            if (Schema::hasColumn('tds_deductions', 'archived_at')) {
                $table->dropColumn('archived_at');
            }

            if (Schema::hasColumn('tds_deductions', 'retention_period')) {
                $table->dropColumn('retention_period');
            }
        });
    }
};
