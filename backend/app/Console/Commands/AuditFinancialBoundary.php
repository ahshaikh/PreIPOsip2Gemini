<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class AuditFinancialBoundary extends Command
{
    protected $signature = 'audit:financial-boundary';
    protected $description = 'Detect wallet mutation boundary violations';

    public function handle()
    {
        $this->info("Scanning wallet mutation boundary...");

        $base = base_path('app');

        /*
        |--------------------------------------------------------------------------
        | Financial Mutation Patterns
        |--------------------------------------------------------------------------
        | These represent actual wallet mutations.
        */
        $pattern = implode('|', [
            'walletService->deposit\(',
            'walletService->withdraw\(',
            'depositLegacy\(',
            'withdrawLegacy\('
        ]);

        $output = shell_exec("rg -n \"$pattern\" \"$base\"");

        if (!$output) {
            $this->info("✔ No wallet mutations detected.");
            return;
        }

        $lines = explode("\n", trim($output));

        $violations = [];
        $total = 0;

        foreach ($lines as $line) {

            // Skip empty lines
            if (!$line) {
                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Ignore Comments
            |--------------------------------------------------------------------------
            */
            if (str_contains($line, '//') || str_contains($line, '*')) {
                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Allowed Mutation Locations
            |--------------------------------------------------------------------------
            */
            if (
                str_contains($line, 'FinancialOrchestrator.php') ||
                str_contains($line, 'WalletService.php')
            ) {
                continue;
            }

            preg_match('/^(.*\.php):(\d+):/', $line, $matches);

            $file = $matches[1] ?? $line;

            $violations[$file][] = $line;
            $total++;
        }

        if ($total === 0) {
            $this->info("✔ Mutation boundary is consistent.");
            return;
        }

        $this->warn("Wallet mutation boundary violations detected:\n");

        foreach ($violations as $file => $entries) {

            $this->line("📄 " . str_replace(base_path(), '', $file));

            foreach ($entries as $entry) {
                $this->error("  " . $entry);
            }

            $this->line("");
        }

        $this->warn("Total violations: $total");
    }
}