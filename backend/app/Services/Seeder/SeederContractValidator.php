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

            /**
             * 🔒 CONTRACT RULE:
             * If no seeder writes to this table, no seeding contract exists.
             * Ignore tables that are never referenced by any seeder.
             */
            if (!isset($providedByTable[$table])) {
                continue;
            }

            // Check each seeder operation for this table
            foreach ($providedByTable[$table] as $operation) {
                $seederClass = $operation['seeder_class'] ?? 'Unknown';
                $method = $operation['method'] ?? 'N/A';

                /**
                 * 🔒 CONTRACT RULE:
                 * Ignore operations where seeder identity cannot be resolved.
                 */
                if ($seederClass === 'Unknown' || $method === 'N/A') {
                    continue;
                }

                $providedColumns = $operation['columns'] ?? [];
                $missingColumns = array_diff($requiredColumns, $providedColumns);

                if (!empty($missingColumns)) {
                    $this->violations[] = new ContractViolation(
                        table: $table,
                        seederClass: $seederClass,
                        method: $method,
                        missingColumns: array_values($missingColumns),
                        codeSnippet: $operation['code_snippet'] ?? null,
                        lineNumber: $operation['line_number'] ?? null
                    );
                }
            }
        }
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
