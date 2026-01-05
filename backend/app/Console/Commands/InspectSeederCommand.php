<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Seeder\SeederContractValidator;
use Illuminate\Console\Command;

/**
 * Artisan command to validate seeder-schema contracts
 *
 * Usage: php artisan seed:inspect
 *
 * Validates that all seeders provide required columns before execution.
 * Exits with non-zero status if violations are found.
 *
 * Integration:
 * - Run manually before seeding
 * - Call from DatabaseSeeder::run() as a guard
 * - Add to CI/CD pipeline
 */
class InspectSeederCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seed:inspect
                            {--path= : Custom path to seeders directory}
                            {--format=text : Output format (text, json)}
                            {--fail-fast : Stop at first violation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Validate that seeders satisfy database schema constraints before execution';

    /**
     * @var SeederContractValidator
     */
    private SeederContractValidator $validator;

    /**
     * Create a new command instance.
     */
    public function __construct(SeederContractValidator $validator)
    {
        parent::__construct();
        $this->validator = $validator;
    }

    /**
     * Execute the console command.
     *
     * @return int Exit code (0 = success, 1 = violations found, 2 = error)
     */
    public function handle(): int
    {
        $this->showHeader();

        // Step 1: Run validation
        try {
            $this->info('🔍 Scanning database schema...');
            $this->info('📄 Analyzing seeder files...');

            $seedersPath = $this->option('path');
            $violations = $this->validator->validate($seedersPath);

            $this->newLine();

        } catch (\RuntimeException $e) {
            $this->error('❌ Validation Error: ' . $e->getMessage());
            return self::FAILURE;
        } catch (\Exception $e) {
            $this->error('❌ Unexpected Error: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
            return 2;
        }

        // Step 2: Output results
        $format = $this->option('format');

        if ($format === 'json') {
            return $this->outputJson($violations);
        }

        return $this->outputText($violations);
    }

    /**
     * Show command header
     */
    private function showHeader(): void
    {
        $this->newLine();
        $this->line('╔═══════════════════════════════════════════════════════════════════════════╗');
        $this->line('║              SEEDER-SCHEMA CONTRACT VALIDATOR                              ║');
        $this->line('╚═══════════════════════════════════════════════════════════════════════════╝');
        $this->newLine();
    }

    /**
     * Output results in text format
     *
     * @param array $violations List of violations
     * @return int Exit code
     */
    private function outputText(array $violations): int
    {
        if (empty($violations)) {
            $this->outputSuccess();
            return self::SUCCESS;
        }

        $this->outputViolations($violations);
        return self::FAILURE;
    }

    /**
     * Output success message
     */
    private function outputSuccess(): void
    {
        $this->info('✅ VALIDATION PASSED');
        $this->newLine();
        $this->line('All seeders satisfy database schema constraints.');
        $this->line('No required columns are missing.');
        $this->newLine();
        $this->info('→ Safe to run: php artisan db:seed');
        $this->newLine();
    }

    /**
     * Output violations in formatted text
     *
     * @param array $violations List of violations
     */
    private function outputViolations(array $violations): void
    {
        $summary = $this->validator->getSummary();

        $this->error('❌ VALIDATION FAILED');
        $this->newLine();

        $this->warn('CRITICAL: Seeders are missing required columns.');
        $this->newLine();

        // Summary
        $this->line('<fg=yellow>SUMMARY:</>');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Violations', $summary['total_violations']],
                ['Affected Tables', $summary['affected_tables']],
                ['Affected Seeders', $summary['affected_seeders']],
            ]
        );

        $this->newLine();
        $this->line('<fg=red>VIOLATIONS:</>');
        $this->newLine();

        // Group violations by table for clarity
        $byTable = $this->validator->violationsByTable();

        foreach ($byTable as $table => $tableViolations) {
            $this->line(str_repeat('━', 80));
            $this->line("<fg=cyan>TABLE: {$table}</> (" . count($tableViolations) . " violation(s))");
            $this->line(str_repeat('━', 80));

            foreach ($tableViolations as $violation) {
                $this->outputViolation($violation);
            }

            $this->newLine();
        }

        $this->outputResolutionSteps();
    }

    /**
     * Output a single violation
     *
     * @param \App\Services\Seeder\ContractViolation $violation
     */
    private function outputViolation($violation): void
    {
        $location = $violation->seederClass . '::' . $violation->method;
        if ($violation->lineNumber) {
            $location .= " <fg=gray>(line {$violation->lineNumber})</>";
        }

        $this->newLine();
        $this->line("  <fg=yellow>SEEDER:</> {$location}");
        $this->line("  <fg=red>MISSING:</> " . implode(', ', $violation->missingColumns));

        if ($violation->codeSnippet) {
            $this->line("  <fg=gray>CODE:</>");
            $lines = explode("\n", trim($violation->codeSnippet));
            foreach ($lines as $line) {
                $this->line("    <fg=gray>{$line}</>");
            }
        }
    }

    /**
     * Output resolution steps
     */
    private function outputResolutionSteps(): void
    {
        $this->line(str_repeat('═', 80));
        $this->newLine();

        $this->line('<fg=yellow>RESOLUTION STEPS:</>');
        $this->line('  1. Review each violation above');
        $this->line('  2. Add missing columns to seeder create/insert arrays');
        $this->line('  3. OR add database defaults (NOT recommended for fintech)');
        $this->line('  4. Re-run: php artisan seed:inspect');
        $this->newLine();

        $this->warn('⚠️  WARNING: Running seeders with these violations will cause database errors.');
        $this->newLine();
    }

    /**
     * Output results in JSON format
     *
     * @param array $violations List of violations
     * @return int Exit code
     */
    private function outputJson(array $violations): int
    {
        $summary = $this->validator->getSummary();

        $output = [
            'status' => empty($violations) ? 'passed' : 'failed',
            'summary' => $summary,
            'violations' => array_map(fn($v) => $v->toArray(), $violations),
        ];

        $this->line(json_encode($output, JSON_PRETTY_PRINT));

        return empty($violations) ? self::SUCCESS : self::FAILURE;
    }
}
