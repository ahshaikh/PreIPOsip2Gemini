<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('legal_agreement_audit_trail')) {
            return;
        }

        Schema::table('legal_agreement_audit_trail', function (Blueprint $table) {
            if (!Schema::hasColumn('legal_agreement_audit_trail', 'is_archived')) {
                $table->boolean('is_archived')->default(false)->after('created_at');
            }

            if (!Schema::hasColumn('legal_agreement_audit_trail', 'archived_at')) {
                $table->timestamp('archived_at')->nullable()->after('is_archived');
            }

            if (!Schema::hasColumn('legal_agreement_audit_trail', 'retention_period')) {
                $table->string('retention_period')
                    ->default('permanent')
                    ->comment('Legal record retention')
                    ->after('archived_at');
            }
        });

        Schema::table('legal_agreement_audit_trail', function (Blueprint $table) {
            $table->index('is_archived');
        });
    }

    public function down(): void
    {
        Schema::table('legal_agreement_audit_trail', function (Blueprint $table) {
            if (Schema::hasColumn('legal_agreement_audit_trail', 'is_archived')) {
                $table->dropIndex(['is_archived']);
                $table->dropColumn('is_archived');
            }

            if (Schema::hasColumn('legal_agreement_audit_trail', 'archived_at')) {
                $table->dropColumn('archived_at');
            }

            if (Schema::hasColumn('legal_agreement_audit_trail', 'retention_period')) {
                $table->dropColumn('retention_period');
            }
        });
    }
};
