<?php

declare(strict_types=1);

namespace App\Services\Seeder;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Extracts required column constraints from the live database schema
 *
 * Uses INFORMATION_SCHEMA to identify columns that:
 * - Cannot be NULL (IS_NULLABLE = 'NO')
 * - Have no default value (COLUMN_DEFAULT IS NULL)
 * - Are not auto-managed (timestamps, IDs, etc.)
 *
 * This represents the "contract" that seeders must fulfill.
 */
final class SchemaInspector
{
    /**
     * Columns that are auto-managed and should be excluded from validation
     */
    private const AUTO_MANAGED_COLUMNS = [
        'id',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * Column patterns that are typically auto-generated
     */
    private const AUTO_GENERATED_PATTERNS = [
        '/^uuid$/i',
        // '/_id$/',  // Foreign keys might have defaults or be auto-set
    ];

    /**
     * Tables that must NEVER be validated against seeders.
     *
     * These tables are:
     * - Runtime-generated
     * - Event-driven
     * - Audit / log / queue based
     * - System-internal
     *
     * They are NOT part of initial data contracts.
     */
    protected array $ignoredTables = [

        // ─────────────────────────────────────────
        // Audit & Logging (runtime only)
        // ─────────────────────────────────────────
        'activity_logs',
        'audit_logs',
        'admin_action_audit',
        'benefit_audit_log',
        'legal_agreement_audit_trail',
        'ticket_agent_activity',

        // ─────────────────────────────────────────
        // Messaging & Communication Logs
        // ─────────────────────────────────────────
        'email_logs',
        'sms_logs',
        'push_logs',
        'webhook_logs',
        'outbound_message_queue',
        'unified_inbox_messages',

        // ─────────────────────────────────────────
        // Background Jobs / Queues / Sagas
        // ─────────────────────────────────────────
        'jobs',
        'job_batches',
        'job_executions',
        'job_state_tracking',
        'saga_executions',
        'saga_steps',

        // ─────────────────────────────────────────
        // System Monitoring & Alerts
        // ─────────────────────────────────────────
        'error_logs',
        'reconciliation_alerts',
        'stuck_state_alerts',
        'system_health_checks',
        'system_health_metrics',

        // ─────────────────────────────────────────
        // Cache / Ephemeral System Tables
        // ─────────────────────────────────────────
        'cache',
        'cache_locks',

        // ─────────────────────────────────────────
        // Runtime User Interaction Traces
        // ─────────────────────────────────────────
        'live_chat_messages',
        'live_chat_sessions',
        'chat_typing_indicators',
        'user_help_interactions',

        // ─────────────────────────────────────────
        // OTP / Security / Device Runtime Data
        // ─────────────────────────────────────────
        'otps',
        'user_devices',
        'ip_whitelist',

        // ─────────────────────────────────────────
        // Reporting Runtime Outputs
        // ─────────────────────────────────────────
        'report_runs',
        'generated_reports',
        'scheduled_reports',
        'scheduled_tasks',
        'performance_metrics',
        'data_export_jobs',

        // ─────────────────────────────────────────
        // Laravel / Framework Internals
        // ─────────────────────────────────────────
        'failed_jobs',
        'password_reset_tokens',
        'personal_access_tokens',
    ];

    /**
     * Get required columns for all tables in the database
     *
     * @return array<string, array<string>> Map of table => [required_columns]
     * @throws \RuntimeException If database connection fails
     */
    public function getRequiredColumnsByTable(): array
    {
        $database = config('database.connections.mysql.database');

        if (!$database) {
            throw new \RuntimeException('Database name not configured');
        }

        $query = <<<SQL
            SELECT
                TABLE_NAME,
                COLUMN_NAME,
                IS_NULLABLE,
                COLUMN_DEFAULT,
                COLUMN_KEY,
                EXTRA
            FROM
                INFORMATION_SCHEMA.COLUMNS
            WHERE
                TABLE_SCHEMA = ?
                AND IS_NULLABLE = 'NO'
                AND COLUMN_DEFAULT IS NULL
            ORDER BY
                TABLE_NAME, ORDINAL_POSITION
        SQL;

        try {
            $columns = DB::select($query, [$database]);
        } catch (\Exception $e) {
            throw new \RuntimeException(
                "Failed to query INFORMATION_SCHEMA: {$e->getMessage()}",
                0,
                $e
            );
        }

        return $this->groupByTable($columns);
    }

    /**
     * Get required columns for a specific table
     *
     * @param string $table Table name
     * @return array<string> List of required column names
     */
    public function getRequiredColumnsForTable(string $table): array
    {
        if (!Schema::hasTable($table)) {
            return [];
        }

        $allRequired = $this->getRequiredColumnsByTable();

        return $allRequired[$table] ?? [];
    }

    /**
     * Group columns by table and filter auto-managed/ignored tables
     *
     * @param array<object> $columns Raw column data from INFORMATION_SCHEMA
     * @return array<string, array<string>>
     */
    private function groupByTable(array $columns): array
    {
        $grouped = [];

        foreach ($columns as $column) {
            $table = $column->TABLE_NAME;
            $columnName = $column->COLUMN_NAME;

            // Skip auto-managed columns
            if ($this->isAutoManaged($column)) {
                continue;
            }

            // Skip migration metadata tables
            if ($this->isMigrationTable($table)) {
                continue;
            }

            // Skip ignored tables
            if ($this->isIgnoredTable($table)) {
                continue;
            }

            if (!isset($grouped[$table])) {
                $grouped[$table] = [];
            }

            $grouped[$table][] = $columnName;
        }

        return $grouped;
    }

    /**
     * Determine if a column is auto-managed by Laravel/MySQL
     *
     * @param object $column Column metadata from INFORMATION_SCHEMA
     * @return bool
     */
    private function isAutoManaged(object $column): bool
    {
        $columnName = $column->COLUMN_NAME;

        // Check explicit auto-managed list
        if (in_array($columnName, self::AUTO_MANAGED_COLUMNS, true)) {
            return true;
        }

        // Check patterns
        foreach (self::AUTO_GENERATED_PATTERNS as $pattern) {
            if (preg_match($pattern, $columnName)) {
                return true;
            }
        }

        // Check if it's an auto-increment primary key
        if ($column->COLUMN_KEY === 'PRI' && str_contains($column->EXTRA, 'auto_increment')) {
            return true;
        }

        return false;
    }

    /**
     * Check if table is a migration metadata table
     *
     * @param string $table Table name
     * @return bool
     */
    private function isMigrationTable(string $table): bool
    {
        return in_array($table, [
            'migrations',
            'failed_jobs',
            'password_reset_tokens',
            'personal_access_tokens',
        ], true);
    }

    /**
     * Check if table is in the ignored list
     *
     * @param string $table Table name
     * @return bool
     */
    private function isIgnoredTable(string $table): bool
    {
        return in_array($table, $this->ignoredTables, true);
    }

    /**
     * Get list of all tables in the database (excluding migrations and ignored)
     *
     * @return array<string>
     */
    public function getAllTables(): array
    {
        $tables = Schema::getAllTables();
        $tableNames = [];

        foreach ($tables as $table) {
            // Handle different DB drivers (MySQL vs PostgreSQL)
            $tableName = $table->{'Tables_in_' . config('database.connections.mysql.database')}
                ?? $table->tablename
                ?? null;

            if ($tableName && !$this->isMigrationTable($tableName) && !$this->isIgnoredTable($tableName)) {
                $tableNames[] = $tableName;
            }
        }

        return $tableNames;
    }

    /**
     * Validate that the database connection is working
     *
     * @return bool
     */
    public function validateConnection(): bool
    {
        try {
            DB::select('SELECT 1');
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}