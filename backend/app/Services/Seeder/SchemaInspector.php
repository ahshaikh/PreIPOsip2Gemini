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
        '/_id$/',  // Foreign keys might have defaults or be auto-set
    ];

    /**
     * Get required columns for all tables in the database
     *
     * @return array<string, array<string>> Map of table => [required_columns]
     * @throws \RuntimeException If database connection fails
     */
    public function getRequiredColumnsByTable(): array
    {
        $driver = config('database.default');
        $connection = config("database.connections.{$driver}");
        $database = $connection['database'] ?? null;

        if (!$database) {
            throw new \RuntimeException('Database name not configured');
        }

        try {
            if ($driver === 'mysql') {
                $columns = $this->getRequiredColumnsMySQL($database);
            } elseif ($driver === 'pgsql') {
                $columns = $this->getRequiredColumnsPostgreSQL($database);
            } else {
                // Fallback for other databases
                $columns = $this->getRequiredColumnsGeneric($database);
            }
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
     * Get required columns for MySQL
     *
     * @param string $database Database name
     * @return array Raw column data
     */
    private function getRequiredColumnsMySQL(string $database): array
    {
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

        return DB::select($query, [$database]);
    }

    /**
     * Get required columns for PostgreSQL
     *
     * @param string $database Database name
     * @return array Raw column data
     */
    private function getRequiredColumnsPostgreSQL(string $database): array
    {
        $query = <<<SQL
            SELECT
                c.TABLE_NAME,
                c.COLUMN_NAME,
                c.IS_NULLABLE,
                c.COLUMN_DEFAULT,
                CASE
                    WHEN tc.constraint_type = 'PRIMARY KEY' THEN 'PRI'
                    ELSE ''
                END as COLUMN_KEY,
                CASE
                    WHEN c.column_default LIKE 'nextval%' THEN 'auto_increment'
                    ELSE ''
                END as EXTRA
            FROM
                INFORMATION_SCHEMA.COLUMNS c
            LEFT JOIN
                information_schema.key_column_usage kcu
                ON c.table_name = kcu.table_name
                AND c.column_name = kcu.column_name
                AND c.table_schema = kcu.table_schema
            LEFT JOIN
                information_schema.table_constraints tc
                ON kcu.constraint_name = tc.constraint_name
                AND kcu.table_schema = tc.table_schema
            WHERE
                c.TABLE_SCHEMA = 'public'
                AND c.IS_NULLABLE = 'NO'
                AND c.COLUMN_DEFAULT IS NULL
            ORDER BY
                c.TABLE_NAME, c.ORDINAL_POSITION
        SQL;

        return DB::select($query);
    }

    /**
     * Get required columns for generic databases
     *
     * @param string $database Database name
     * @return array Raw column data
     */
    private function getRequiredColumnsGeneric(string $database): array
    {
        $query = <<<SQL
            SELECT
                TABLE_NAME,
                COLUMN_NAME,
                IS_NULLABLE,
                COLUMN_DEFAULT
            FROM
                INFORMATION_SCHEMA.COLUMNS
            WHERE
                IS_NULLABLE = 'NO'
                AND COLUMN_DEFAULT IS NULL
            ORDER BY
                TABLE_NAME, ORDINAL_POSITION
        SQL;

        $columns = DB::select($query);

        // Add empty COLUMN_KEY and EXTRA for consistency
        return array_map(function ($col) {
            $col->COLUMN_KEY = '';
            $col->EXTRA = '';
            return $col;
        }, $columns);
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
     * Group columns by table and filter auto-managed columns
     *
     * @param array<object> $columns Raw column data from INFORMATION_SCHEMA
     * @return array<string, array<string>>
     */
    private function groupByTable(array $columns): array
    {
        $grouped = [];

        foreach ($columns as $column) {
            // Handle both uppercase (MySQL) and lowercase (PostgreSQL) property names
            $table = $column->TABLE_NAME ?? $column->table_name ?? null;
            $columnName = $column->COLUMN_NAME ?? $column->column_name ?? null;

            if (!$table || !$columnName) {
                continue;
            }

            // Skip auto-managed columns
            if ($this->isAutoManaged($column)) {
                continue;
            }

            // Skip migration metadata tables
            if ($this->isMigrationTable($table)) {
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
     * Determine if a column is auto-managed by Laravel/MySQL/PostgreSQL
     *
     * @param object $column Column metadata from INFORMATION_SCHEMA
     * @return bool
     */
    private function isAutoManaged(object $column): bool
    {
        // Handle both uppercase (MySQL) and lowercase (PostgreSQL) property names
        $columnName = $column->COLUMN_NAME ?? $column->column_name ?? '';
        $columnKey = $column->COLUMN_KEY ?? $column->column_key ?? '';
        $extra = $column->EXTRA ?? $column->extra ?? '';

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
        if ($columnKey === 'PRI' && str_contains($extra, 'auto_increment')) {
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
     * Get list of all tables in the database (excluding migrations)
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

            if ($tableName && !$this->isMigrationTable($tableName)) {
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
