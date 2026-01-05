<?php

declare(strict_types=1);

namespace App\Services\Seeder;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Runtime Auto-Completer for Seeder Operations
 *
 * Automatically fills missing required fields during seeder execution
 * to prevent database constraint violations.
 *
 * CATEGORY A & B Handler: Handles real inserts with missing fields
 * - Detects missing required columns at runtime
 * - Auto-fills with intelligent defaults based on column type and name
 * - Ensures seeders never fail due to missing required fields
 *
 * This is a SAFETY NET that works alongside static validation.
 */
final class SeederRuntimeAutoCompleter
{
    /**
     * @var SchemaInspector
     */
    private SchemaInspector $schemaInspector;

    /**
     * @var array<string, array<string>> Cache of required columns by table
     */
    private array $requiredColumnsCache = [];

    /**
     * @var bool Whether auto-completion is enabled
     */
    private bool $enabled = false;

    public function __construct(?SchemaInspector $schemaInspector = null)
    {
        $this->schemaInspector = $schemaInspector ?? new SchemaInspector();
    }

    /**
     * Enable auto-completion for seeder operations
     */
    public function enable(): void
    {
        $this->enabled = true;
        $this->loadRequiredColumns();
    }

    /**
     * Disable auto-completion
     */
    public function disable(): void
    {
        $this->enabled = false;
        $this->requiredColumnsCache = [];
    }

    /**
     * Check if auto-completion is enabled
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Load required columns for all tables
     */
    private function loadRequiredColumns(): void
    {
        try {
            $this->requiredColumnsCache = $this->schemaInspector->getRequiredColumnsByTable();
        } catch (\Exception $e) {
            // Silently fail if schema cannot be loaded
            $this->requiredColumnsCache = [];
        }
    }

    /**
     * Auto-complete missing required fields in data array
     *
     * @param string $table Table name
     * @param array $data Data being inserted
     * @return array Completed data array
     */
    public function complete(string $table, array $data): array
    {
        if (!$this->enabled) {
            return $data;
        }

        $requiredColumns = $this->requiredColumnsCache[$table] ?? [];

        if (empty($requiredColumns)) {
            return $data;
        }

        $missingColumns = array_diff($requiredColumns, array_keys($data));

        if (empty($missingColumns)) {
            return $data;
        }

        // Auto-fill missing columns
        foreach ($missingColumns as $column) {
            $data[$column] = $this->generateDefaultValue($table, $column);
        }

        return $data;
    }

    /**
     * Generate a sensible default value for a column
     *
     * @param string $table Table name
     * @param string $column Column name
     * @return mixed Default value
     */
    private function generateDefaultValue(string $table, string $column)
    {
        // Get column type from schema
        $columnType = $this->getColumnType($table, $column);

        // Pattern-based defaults
        $defaults = $this->getPatternBasedDefault($column, $columnType);
        if ($defaults !== null) {
            return $defaults;
        }

        // Type-based defaults
        return $this->getTypeBasedDefault($columnType);
    }

    /**
     * Get default value based on column name patterns
     *
     * @param string $column Column name
     * @param string $type Column type
     * @return mixed|null Default value or null
     */
    private function getPatternBasedDefault(string $column, string $type): mixed
    {
        // Email patterns
        if (preg_match('/(email|mail)/', $column)) {
            return 'auto-seeded-' . Str::random(10) . '@generated.local';
        }

        // Mobile/Phone patterns
        if (preg_match('/(mobile|phone)/', $column)) {
            return '9' . str_pad((string) rand(0, 999999999), 9, '0', STR_PAD_LEFT);
        }

        // Username patterns
        if (preg_match('/username/', $column)) {
            return 'user_' . Str::random(8);
        }

        // Code/Slug patterns
        if (preg_match('/(code|slug)/', $column)) {
            return strtoupper(Str::random(8));
        }

        // Referral code patterns
        if (preg_match('/referral/', $column)) {
            return 'REF' . strtoupper(Str::random(6));
        }

        // Status patterns
        if (preg_match('/status/', $column)) {
            return 'active';
        }

        // Name patterns
        if (preg_match('/(name|title)/', $column)) {
            return 'Auto-Generated ' . Str::title(str_replace('_', ' ', $column));
        }

        // File path patterns
        if (preg_match('/(path|file)/', $column)) {
            return '/storage/auto-generated/' . Str::random(10) . '.dat';
        }

        // MIME type patterns
        if (preg_match('/(mime|type)/', $column) && !preg_match('/doc_type/', $column)) {
            return 'application/octet-stream';
        }

        // Document type patterns
        if (preg_match('/doc_type/', $column)) {
            return 'general';
        }

        return null;
    }

    /**
     * Get default value based on column type
     *
     * @param string $type Column type
     * @return mixed Default value
     */
    private function getTypeBasedDefault(string $type): mixed
    {
        // String types
        if (str_contains($type, 'char') || str_contains($type, 'text')) {
            return 'auto-filled';
        }

        // Integer types
        if (str_contains($type, 'int') || str_contains($type, 'bigint')) {
            return 0;
        }

        // Decimal/Float types
        if (str_contains($type, 'decimal') || str_contains($type, 'float') || str_contains($type, 'double')) {
            return 0.0;
        }

        // Boolean types
        if (str_contains($type, 'bool') || $type === 'tinyint') {
            return false;
        }

        // Date/Time types
        if (str_contains($type, 'date') || str_contains($type, 'time')) {
            return now();
        }

        // JSON types
        if (str_contains($type, 'json')) {
            return json_encode([]);
        }

        // Default fallback
        return '';
    }

    /**
     * Get column type from database schema
     *
     * @param string $table Table name
     * @param string $column Column name
     * @return string Column type
     */
    private function getColumnType(string $table, string $column): string
    {
        try {
            $driver = config('database.default');
            $database = config("database.connections.{$driver}.database");

            if ($driver === 'mysql') {
                $result = DB::selectOne(
                    "SELECT DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS
                     WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?",
                    [$database, $table, $column]
                );
            } elseif ($driver === 'pgsql') {
                $result = DB::selectOne(
                    "SELECT data_type FROM information_schema.columns
                     WHERE table_schema = 'public' AND table_name = ? AND column_name = ?",
                    [$table, $column]
                );
            } else {
                return 'varchar';
            }

            return $result->data_type ?? $result->DATA_TYPE ?? 'varchar';
        } catch (\Exception $e) {
            return 'varchar';
        }
    }

    /**
     * Get statistics about auto-completed fields
     *
     * @return array Statistics
     */
    public function getStats(): array
    {
        return [
            'enabled' => $this->enabled,
            'tables_monitored' => count($this->requiredColumnsCache),
            'total_required_columns' => array_sum(array_map('count', $this->requiredColumnsCache)),
        ];
    }
}
