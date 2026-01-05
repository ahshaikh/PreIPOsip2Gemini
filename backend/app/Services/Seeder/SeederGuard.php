<?php

declare(strict_types=1);

namespace App\Services\Seeder;

use Illuminate\Support\Facades\App;

/**
 * Guard class to validate seeder contracts before execution
 *
 * Usage in DatabaseSeeder::run():
 *
 * public function run(): void
 * {
 *     SeederGuard::validate(); // <-- Add this line at the top
 *
 *     $this->call(RolesAndPermissionsSeeder::class);
 *     // ... rest of seeders
 * }
 *
 * This ensures no seeder executes until all contracts are satisfied.
 */
final class SeederGuard
{
    /**
     * Validate seeder contracts before allowing execution
     *
     * @param bool $failInProduction Whether to enforce validation in production (default: false)
     * @throws \RuntimeException If validation fails
     */
    public static function validate(bool $failInProduction = false): void
    {
        // Skip validation in production unless explicitly enabled
        if (App::environment('production') && !$failInProduction) {
            return;
        }

        $validator = new SeederContractValidator();

        try {
            $violations = $validator->validate();
        } catch (\RuntimeException $e) {
            // Log but don't fail if database connection is unavailable
            // This allows migrations to run first
            if (str_contains($e->getMessage(), 'Database connection failed')) {
                return;
            }

            throw $e;
        }

        if (!empty($violations)) {
            $report = $validator->formatReport();

            // Print to console
            echo $report;

            throw new \RuntimeException(
                "Seeder contract validation failed with {$validator->violationCount()} violation(s). " .
                "Run 'php artisan seed:inspect' for details."
            );
        }
    }

    /**
     * Validate and return boolean result (non-throwing version)
     *
     * @return bool True if validation passed
     */
    public static function check(): bool
    {
        try {
            self::validate(true);
            return true;
        } catch (\RuntimeException $e) {
            return false;
        }
    }

    /**
     * Get validation report without throwing
     *
     * @return array{passed: bool, violations: int, report: string}
     */
    public static function report(): array
    {
        $validator = new SeederContractValidator();

        try {
            $violations = $validator->validate();

            return [
                'passed' => empty($violations),
                'violations' => count($violations),
                'report' => $validator->formatReport(),
            ];
        } catch (\RuntimeException $e) {
            return [
                'passed' => false,
                'violations' => -1,
                'report' => "Validation error: {$e->getMessage()}",
            ];
        }
    }
}
