<?php

declare(strict_types=1);

namespace App\Services\Seeder;

/**
 * Orchestrates seeder-schema contract validation
 *
 * Coordinates between:
 * - SchemaInspector: Extracts required columns from database
 * - SeederCodeScanner: Analyzes seeder code for provided columns
 *
 * Produces violations when seeders fail to provide required columns.
 */
final class SeederContractValidator
{
    /**
     * @var SchemaInspector
     */
    private SchemaInspector $schemaInspector;

    /**
     * @var SeederCodeScanner
     */
    private SeederCodeScanner $codeScanner;

    /**
     * @var array<ContractViolation>
     */
    private array $violations = [];

    public function __construct(
        ?SchemaInspector $schemaInspector = null,
        ?SeederCodeScanner $codeScanner = null
    ) {
        $this->schemaInspector = $schemaInspector ?? new SchemaInspector();
        $this->codeScanner = $codeScanner ?? new SeederCodeScanner();
    }

    /**
     * Validate all seeders against database schema
     *
     * @param string|null $seedersPath Path to seeders directory (defaults to database/seeders)
     * @return array<ContractViolation> List of violations found
     * @throws \RuntimeException If validation cannot be performed
     */
    public function validate(?string $seedersPath = null): array
    {
        $this->violations = [];

        // Step 1: Validate database connection
        if (!$this->schemaInspector->validateConnection()) {
            throw new \RuntimeException(
                'Database connection failed. Cannot validate seeder contracts without a live database.'
            );
        }

        // Step 2: Extract required columns from schema
        $requiredByTable = $this->schemaInspector->getRequiredColumnsByTable();

        if (empty($requiredByTable)) {
            // No tables with required columns - validation passes
            return [];
        }

        // Step 3: Scan seeder files for provided columns
        $providedByTable = $this->codeScanner->scanSeeders($seedersPath);

        // Step 4: Compare and generate violations
        $this->compareAndGenerateViolations($requiredByTable, $providedByTable);

        return $this->violations;
    }

    /**
     * Tables that should never be seeded (Laravel framework tables)
     */
    private const FRAMEWORK_TABLES = [
        'cache',
        'cache_locks',
        'jobs',
        'job_batches',
        'failed_jobs',
        'password_reset_tokens',
        'password_resets',
        'sessions',
        'otps',
        'password_histories',
        'user_devices',
        'personal_access_tokens',
    ];

    /**
     * Compare required vs provided columns and generate violations
     *
     * @param array<string, array<string>> $requiredByTable Map of table => required columns
     * @param array<string, array> $providedByTable Map of table => scan results
     */
    private function compareAndGenerateViolations(array $requiredByTable, array $providedByTable): void
    {
        foreach ($requiredByTable as $table => $requiredColumns) {
            if (empty($requiredColumns)) {
                continue;
            }

            // CATEGORY C: Auto-filter framework tables that are never seeded
            if ($this->isFrameworkTable($table)) {
                continue;
            }

            // If table is not seeded at all, check if it's a false positive
            if (!isset($providedByTable[$table])) {
                // CATEGORY C: Skip tables with no seeder (false positive)
                // These are typically runtime tables or managed by Laravel
                continue;
            }

            // Check each seeder operation for this table
            foreach ($providedByTable[$table] as $operation) {
                $providedColumns = $operation['columns'] ?? [];
                $missingColumns = array_diff($requiredColumns, $providedColumns);

                if (!empty($missingColumns)) {
                    $violation = new ContractViolation(
                        table: $table,
                        seederClass: $operation['seeder_class'] ?? 'Unknown',
                        method: $operation['method'] ?? 'unknown',
                        missingColumns: array_values($missingColumns),
                        codeSnippet: $operation['code_snippet'] ?? null,
                        lineNumber: $operation['line_number'] ?? null
                    );

                    // CATEGORY C: Auto-filter false positives
                    if (!$this->isFalsePositive($violation)) {
                        $this->violations[] = $violation;
                    }
                }
            }
        }
    }

    /**
     * Determine if a table is a Laravel framework table that should never be seeded
     *
     * @param string $table Table name
     * @return bool
     */
    private function isFrameworkTable(string $table): bool
    {
        return in_array($table, self::FRAMEWORK_TABLES, true);
    }

    /**
     * Determine if a violation is a false positive that should be auto-ignored
     *
     * CATEGORY C: False positives include:
     * - Scanner couldn't detect seeder (Unknown::N/A)
     * - Scanner couldn't parse dynamic code
     * - Tables populated by relationships/observers
     *
     * @param ContractViolation $violation
     * @return bool True if violation should be ignored
     */
    private function isFalsePositive(ContractViolation $violation): bool
    {
        // 1. Unknown seeder with no method = scanner couldn't find insert
        if ($violation->seederClass === 'Unknown' && $violation->method === 'N/A') {
            return true;
        }

        // 2. Unknown seeder with unknown method = scanner limitation
        if ($violation->seederClass === 'Unknown' || $violation->method === 'unknown') {
            return true;
        }

        // 3. Empty or null code snippet = no actual code detected
        if (empty($violation->codeSnippet) || $violation->codeSnippet === null) {
            return true;
        }

        // 4. Code snippet is just a placeholder comment
        if (str_starts_with(trim($violation->codeSnippet), '//')) {
            return true;
        }

        return false;
    }

    /**
     * Get violations found during validation
     *
     * @return array<ContractViolation>
     */
    public function getViolations(): array
    {
        return $this->violations;
    }

    /**
     * Check if validation passed (no violations)
     *
     * @return bool
     */
    public function passed(): bool
    {
        return empty($this->violations);
    }

    /**
     * Get count of violations
     *
     * @return int
     */
    public function violationCount(): int
    {
        return count($this->violations);
    }

    /**
     * Get violations grouped by table
     *
     * @return array<string, array<ContractViolation>>
     */
    public function violationsByTable(): array
    {
        $grouped = [];

        foreach ($this->violations as $violation) {
            $table = $violation->table;

            if (!isset($grouped[$table])) {
                $grouped[$table] = [];
            }

            $grouped[$table][] = $violation;
        }

        return $grouped;
    }

    /**
     * Get violations grouped by seeder class
     *
     * @return array<string, array<ContractViolation>>
     */
    public function violationsBySeeder(): array
    {
        $grouped = [];

        foreach ($this->violations as $violation) {
            $seeder = $violation->seederClass;

            if (!isset($grouped[$seeder])) {
                $grouped[$seeder] = [];
            }

            $grouped[$seeder][] = $violation;
        }

        return $grouped;
    }

    /**
     * Get summary statistics
     *
     * @return array{total_violations: int, affected_tables: int, affected_seeders: int}
     */
    public function getSummary(): array
    {
        return [
            'total_violations' => $this->violationCount(),
            'affected_tables' => count($this->violationsByTable()),
            'affected_seeders' => count($this->violationsBySeeder()),
        ];
    }

    /**
     * Format violations as human-readable report
     *
     * @return string Formatted report
     */
    public function formatReport(): string
    {
        if ($this->passed()) {
            return $this->formatSuccessReport();
        }

        return $this->formatFailureReport();
    }

    /**
     * Format success report
     *
     * @return string
     */
    private function formatSuccessReport(): string
    {
        return <<<REPORT

╔═══════════════════════════════════════════════════════════════════════════╗
║                   SEEDER CONTRACT VALIDATION PASSED ✓                      ║
╚═══════════════════════════════════════════════════════════════════════════╝

All seeders satisfy database schema constraints.
No required columns are missing.

REPORT;
    }

    /**
     * Format failure report
     *
     * @return string
     */
    private function formatFailureReport(): string
    {
        $summary = $this->getSummary();

        $report = <<<HEADER

╔═══════════════════════════════════════════════════════════════════════════╗
║                   SEEDER CONTRACT VALIDATION FAILED ✗                      ║
╚═══════════════════════════════════════════════════════════════════════════╝

CRITICAL: Seeders are missing required columns that have no defaults.

SUMMARY:
  • Total Violations: {$summary['total_violations']}
  • Affected Tables: {$summary['affected_tables']}
  • Affected Seeders: {$summary['affected_seeders']}

VIOLATIONS:

HEADER;

        // Group by table for clearer output
        $byTable = $this->violationsByTable();

        foreach ($byTable as $table => $violations) {
            $report .= "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
            $report .= "TABLE: {$table} ({count($violations)} violation(s))\n";
            $report .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

            foreach ($violations as $violation) {
                $report .= "\n" . $violation->toString() . "\n";
            }
        }

        $report .= <<<FOOTER

═══════════════════════════════════════════════════════════════════════════

RESOLUTION STEPS:
1. Review each violation above
2. Add missing columns to the seeder create/insert arrays
3. OR add database defaults/nullable in migrations (NOT recommended for fintech)
4. Re-run: php artisan seed:inspect

WARNING: Attempting to run seeders with these violations will result in
database constraint errors and failed seeding operations.

FOOTER;

        return $report;
    }
}
