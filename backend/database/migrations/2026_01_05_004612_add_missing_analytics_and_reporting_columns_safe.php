<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /**
         * PERFORMANCE METRICS
         */
        if (Schema::hasTable('performance_metrics')) {
            Schema::table('performance_metrics', function (Blueprint $table) {
                if (!Schema::hasColumn('performance_metrics', 'endpoint')) {
                    $table->string('endpoint')->nullable();
                }

                if (!Schema::hasColumn('performance_metrics', 'value')) {
                    $table->float('value')->default(0);
                }

                if (!Schema::hasColumn('performance_metrics', 'unit')) {
                    $table->string('unit')->default('ms');
                }

                if (!Schema::hasColumn('performance_metrics', 'metadata')) {
                    $table->json('metadata')->nullable();
                }
            });
        }

        /**
         * SYSTEM HEALTH CHECKS
         */
        if (Schema::hasTable('system_health_checks')) {
            Schema::table('system_health_checks', function (Blueprint $table) {
                if (!Schema::hasColumn('system_health_checks', 'status')) {
                    $table->string('status')->default('healthy');
                }

                if (!Schema::hasColumn('system_health_checks', 'response_time')) {
                    $table->integer('response_time')->nullable();
                }

                if (!Schema::hasColumn('system_health_checks', 'details')) {
                    $table->json('details')->nullable();
                }
            });
        }

        /**
         * COMPANY ANALYTICS
         */
        if (Schema::hasTable('company_analytics')) {
            Schema::table('company_analytics', function (Blueprint $table) {
                if (!Schema::hasColumn('company_analytics', 'profile_views')) {
                    $table->unsignedInteger('profile_views')->default(0);
                }

                if (!Schema::hasColumn('company_analytics', 'document_downloads')) {
                    $table->unsignedInteger('document_downloads')->default(0);
                }

                if (!Schema::hasColumn('company_analytics', 'financial_report_downloads')) {
                    $table->unsignedInteger('financial_report_downloads')->default(0);
                }

                if (!Schema::hasColumn('company_analytics', 'deal_views')) {
                    $table->unsignedInteger('deal_views')->default(0);
                }

                if (!Schema::hasColumn('company_analytics', 'investor_interest_clicks')) {
                    $table->unsignedInteger('investor_interest_clicks')->default(0);
                }
            });
        }

        /**
         * REPORTS
         */
        if (Schema::hasTable('reports')) {
            Schema::table('reports', function (Blueprint $table) {
                if (!Schema::hasColumn('reports', 'filters')) {
                    $table->json('filters')->nullable();
                }

                if (!Schema::hasColumn('reports', 'columns')) {
                    $table->json('columns')->nullable();
                }
            });
        }

        /**
         * REPORT RUNS
         */
        if (Schema::hasTable('report_runs')) {
            Schema::table('report_runs', function (Blueprint $table) {
                if (!Schema::hasColumn('report_runs', 'started_at')) {
                    $table->timestamp('started_at')->nullable();
                }

                if (!Schema::hasColumn('report_runs', 'completed_at')) {
                    $table->timestamp('completed_at')->nullable();
                }

                if (!Schema::hasColumn('report_runs', 'status')) {
                    $table->string('status')->default('pending');
                }

                if (!Schema::hasColumn('report_runs', 'error_message')) {
                    $table->text('error_message')->nullable();
                }
            });
        }

        /**
         * SCHEDULED REPORTS
         */
        if (Schema::hasTable('scheduled_reports')) {
            Schema::table('scheduled_reports', function (Blueprint $table) {
                if (!Schema::hasColumn('scheduled_reports', 'is_active')) {
                    $table->boolean('is_active')->default(true);
                }

                if (!Schema::hasColumn('scheduled_reports', 'last_run_at')) {
                    $table->timestamp('last_run_at')->nullable();
                }
            });
        }

        /**
         * BULK IMPORT JOBS
         */
        if (Schema::hasTable('bulk_import_jobs')) {
            Schema::table('bulk_import_jobs', function (Blueprint $table) {
                if (!Schema::hasColumn('bulk_import_jobs', 'errors')) {
                    $table->json('errors')->nullable();
                }
            });
        }

        /**
         * DATA EXPORT JOBS
         */
        if (Schema::hasTable('data_export_jobs')) {
            Schema::table('data_export_jobs', function (Blueprint $table) {
                if (!Schema::hasColumn('data_export_jobs', 'filters')) {
                    $table->json('filters')->nullable();
                }

                if (!Schema::hasColumn('data_export_jobs', 'columns')) {
                    $table->json('columns')->nullable();
                }

                if (!Schema::hasColumn('data_export_jobs', 'record_count')) {
                    $table->unsignedInteger('record_count')->default(0);
                }
            });
        }
    }

    public function down(): void
    {
        // Additive-only analytics migration
    }
};
