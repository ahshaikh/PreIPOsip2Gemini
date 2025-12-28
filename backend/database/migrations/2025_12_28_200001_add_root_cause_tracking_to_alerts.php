<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

/**
 * Migration: Add Root Cause Tracking to Alerts
 *
 * PURPOSE (addressing audit feedback):
 * - "Alert volume can itself become a failure mode" - alert fatigue
 * - Track root causes to prevent symptoms from masking systemic issues
 * - Group similar alerts by root cause for efficient resolution
 *
 * EXAMPLE:
 * Instead of: "50 stuck payments" (symptom)
 * Show: "Payment gateway timeout - affecting 50 payments since 2h ago" (root cause)
 *
 * ROOT CAUSE TYPES:
 * - payment_gateway_timeout: External gateway outage
 * - inventory_service_down: Internal service failure
 * - webhook_delivery_failure: Network/infrastructure issue
 * - concurrency_bug: Race condition in code
 * - data_integrity_violation: Business logic bug
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add columns to stuck_state_alerts
        if (Schema::hasTable('stuck_state_alerts')) {
            Schema::table('stuck_state_alerts', function (Blueprint $table) {
                // Root cause identification
                if (!Schema::hasColumn('stuck_state_alerts', 'root_cause')) {
                    if (Schema::hasColumn('stuck_state_alerts', 'description')) {
                        $table->string('root_cause')->nullable()->after('description');
                    } else {
                        $table->string('root_cause')->nullable();
                    }
                }
                if (!Schema::hasColumn('stuck_state_alerts', 'root_cause_identified_at')) {
                    $table->timestamp('root_cause_identified_at')->nullable();
                }
                if (!Schema::hasColumn('stuck_state_alerts', 'root_cause_identified_by')) {
                    $table->unsignedBigInteger('root_cause_identified_by')->nullable();
                }

                // Root cause grouping (for alert aggregation)
                if (!Schema::hasColumn('stuck_state_alerts', 'root_cause_group')) {
                    $table->string('root_cause_group')->nullable();
                }
            });

            // Add indexes separately
            if (!$this->indexExists('stuck_state_alerts', 'stuck_state_alerts_root_cause_index')) {
                Schema::table('stuck_state_alerts', function (Blueprint $table) {
                    $table->index('root_cause');
                });
            }
            if (!$this->indexExists('stuck_state_alerts', 'stuck_state_alerts_root_cause_group_index')) {
                Schema::table('stuck_state_alerts', function (Blueprint $table) {
                    $table->index('root_cause_group');
                });
            }
        }

        // Add columns to reconciliation_alerts
        if (Schema::hasTable('reconciliation_alerts')) {
            Schema::table('reconciliation_alerts', function (Blueprint $table) {
                // Root cause identification
                if (!Schema::hasColumn('reconciliation_alerts', 'root_cause')) {
                    if (Schema::hasColumn('reconciliation_alerts', 'description')) {
                        $table->string('root_cause')->nullable()->after('description');
                    } else {
                        $table->string('root_cause')->nullable();
                    }
                }
                if (!Schema::hasColumn('reconciliation_alerts', 'root_cause_identified_at')) {
                    $table->timestamp('root_cause_identified_at')->nullable();
                }
                if (!Schema::hasColumn('reconciliation_alerts', 'root_cause_identified_by')) {
                    $table->unsignedBigInteger('root_cause_identified_by')->nullable();
                }

                // Root cause grouping
                if (!Schema::hasColumn('reconciliation_alerts', 'root_cause_group')) {
                    $table->string('root_cause_group')->nullable();
                }
            });

            // Add indexes separately
            if (!$this->indexExists('reconciliation_alerts', 'reconciliation_alerts_root_cause_index')) {
                Schema::table('reconciliation_alerts', function (Blueprint $table) {
                    $table->index('root_cause');
                });
            }
            if (!$this->indexExists('reconciliation_alerts', 'reconciliation_alerts_root_cause_group_index')) {
                Schema::table('reconciliation_alerts', function (Blueprint $table) {
                    $table->index('root_cause_group');
                });
            }
        }

        // Create root cause catalog table
        Schema::create('alert_root_causes', function (Blueprint $table) {
            $table->id();

            // Root cause identification
            $table->string('root_cause_type'); // 'payment_gateway_timeout', 'service_down', etc.
            $table->text('description');

            // Impact tracking
            $table->integer('affected_alerts_count')->default(0);
            $table->decimal('total_monetary_impact', 15, 2)->default(0);
            $table->integer('affected_users_count')->default(0);

            // Timeline
            $table->timestamp('first_occurrence')->nullable();
            $table->timestamp('last_occurrence')->nullable();
            $table->timestamp('resolved_at')->nullable();

            // Resolution
            $table->boolean('is_resolved')->default(false);
            $table->unsignedBigInteger('resolved_by')->nullable();
            $table->text('resolution_notes')->nullable();

            // Severity (calculated from impact)
            $table->string('severity'); // 'low', 'medium', 'high', 'critical'

            $table->timestamps();

            // Indexes
            $table->index('root_cause_type');
            $table->index('is_resolved');
            $table->index('severity');
            $table->index('first_occurrence');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('stuck_state_alerts')) {
            // Drop indexes
            if ($this->indexExists('stuck_state_alerts', 'stuck_state_alerts_root_cause_index')) {
                Schema::table('stuck_state_alerts', function (Blueprint $table) {
                    $table->dropIndex(['root_cause']);
                });
            }
            if ($this->indexExists('stuck_state_alerts', 'stuck_state_alerts_root_cause_group_index')) {
                Schema::table('stuck_state_alerts', function (Blueprint $table) {
                    $table->dropIndex(['root_cause_group']);
                });
            }

            // Drop columns
            Schema::table('stuck_state_alerts', function (Blueprint $table) {
                $columns = ['root_cause', 'root_cause_identified_at', 'root_cause_identified_by', 'root_cause_group'];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('stuck_state_alerts', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('reconciliation_alerts')) {
            // Drop indexes
            if ($this->indexExists('reconciliation_alerts', 'reconciliation_alerts_root_cause_index')) {
                Schema::table('reconciliation_alerts', function (Blueprint $table) {
                    $table->dropIndex(['root_cause']);
                });
            }
            if ($this->indexExists('reconciliation_alerts', 'reconciliation_alerts_root_cause_group_index')) {
                Schema::table('reconciliation_alerts', function (Blueprint $table) {
                    $table->dropIndex(['root_cause_group']);
                });
            }

            // Drop columns
            Schema::table('reconciliation_alerts', function (Blueprint $table) {
                $columns = ['root_cause', 'root_cause_identified_at', 'root_cause_identified_by', 'root_cause_group'];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('reconciliation_alerts', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        Schema::dropIfExists('alert_root_causes');
    }

    /**
     * Check if an index exists on a table
     */
    private function indexExists(string $table, string $index): bool
    {
        if (!Schema::hasTable($table)) {
            return false;
        }
        $indexes = \Illuminate\Support\Facades\DB::select("SHOW INDEX FROM {$table} WHERE Key_name = '{$index}'");
        return !empty($indexes);
    }
};
