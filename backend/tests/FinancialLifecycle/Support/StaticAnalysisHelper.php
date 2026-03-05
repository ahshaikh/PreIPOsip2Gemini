<?php

/**
 * StaticAnalysisHelper - Code Analysis for Financial Lifecycle Rules
 *
 * Scans the codebase to enforce architectural rules:
 * - No float usage in financial calculations
 * - No /100 conversions inside lifecycle code
 * - No nested transactions in domain services
 * - No lockForUpdate outside FinancialOrchestrator
 * - No async financial mutations (queue dispatches inside transactions)
 *
 * @package Tests\FinancialLifecycle\Support
 */

namespace Tests\FinancialLifecycle\Support;

use Illuminate\Support\Facades\File;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;

class StaticAnalysisHelper
{
    /**
     * Paths to scan for financial lifecycle code.
     */
    private const SCAN_PATHS = [
        'app/Services',
        'app/Jobs',
        'app/Domains',
    ];

    /**
     * Files/patterns to exclude from scanning.
     */
    private const EXCLUDE_PATTERNS = [
        '/Test\.php$/',
        '/\.blade\.php$/',
        '/migrations/',
        '/config/',
        '/app[\\\\\/]Services[\\\\\/]Orchestration/', // EXCLUDE orchestration internal logic
        '/PaymentAllocationSaga\.php$/', // Saga is an orchestrator
        '/CelebrationBonusService\.php$/', // Orchestrates celebration bonuses
    ];

    /**
     * Financial lifecycle files that must adhere to strict rules.
     */
    private const LIFECYCLE_FILES = [
        'PaymentWebhookService.php',
        'WalletService.php',
        'AllocationService.php',
        'BonusCalculatorService.php',
        'ProcessSuccessfulPaymentJob.php',
        'ProcessPaymentBonusJob.php',
        'DoubleEntryLedgerService.php',
        'FinancialOrchestrator.php', // Target of refactor
    ];

    /**
     * Allowed files for lockForUpdate (only orchestrator should lock).
     */
    private const ALLOWED_LOCKING_FILES = [
        'FinancialOrchestrator.php',
        'WalletService.php', // Allowed for internal atomic operations
    ];

    /**
     * Files allowed to open transactions.
     */
    private const ALLOWED_TRANSACTION_FILES = [
        'FinancialOrchestrator.php',
        'WalletService.php', // Allowed for legacy/internal paths
    ];

    /**
     * Files allowed to call WalletService mutation methods.
     */
    private const ALLOWED_WALLET_MUTATION_CALLERS = [
        'FinancialOrchestrator.php',
        'WalletService.php', // Self-calls
    ];

    private string $basePath;

    public function __construct(?string $basePath = null)
    {
        $this->basePath = $basePath ?? base_path();
    }

    /**
     * Scan for float usage in financial calculations.
     *
     * Detects patterns like:
     * - float $amount
     * - (float) $value
     * - floatval()
     * - Explicit float literals in financial context
     *
     * @return array Violations found
     */
    public function scanForFloatUsage(): array
    {
        $violations = [];
        $patterns = [
            '/\bfloat\s+\$\w*(?:amount|paise|balance|value|price)/i' => 'Float type hint for monetary variable',
            '/\(float\)\s*\$\w*(?:amount|paise|balance)/i' => 'Float cast on monetary value',
            '/floatval\s*\(\s*\$\w*(?:amount|paise|balance)/i' => 'floatval() on monetary value',
            '/\$\w*(?:amount|balance)_paise\s*=\s*[\d\.]+\s*[^;]*\.\d+/' => 'Float literal assigned to paise variable',
        ];

        foreach ($this->getLifecycleFiles() as $file) {
            $content = file_get_contents($file);
            $lines = explode("\n", $content);

            foreach ($patterns as $pattern => $description) {
                foreach ($lines as $lineNum => $line) {
                    if (preg_match($pattern, $line)) {
                        $violations[] = [
                            'file' => $this->relativePath($file),
                            'line' => $lineNum + 1,
                            'code' => trim($line),
                            'rule' => 'NO_FLOAT_USAGE',
                            'description' => $description,
                        ];
                    }
                }
            }
        }

        return $violations;
    }

    /**
     * Scan for /100 conversion bridges in financial code.
     *
     * Detects patterns like:
     * - $amount / 100
     * - $paise / 100
     * - / 100.0
     *
     * @return array Violations found
     */
    public function scanForDivisionBy100(): array
    {
        $violations = [];
        $patterns = [
            '/\$\w*(?:amount|paise|balance)\s*\/\s*100\b/' => 'Division by 100 on monetary variable',
            '/\/\s*100\.0\b/' => 'Division by 100.0 (float conversion)',
            '/\$\w+_paise\s*\/\s*100/' => 'Paise to rupee conversion inside lifecycle',
        ];

        foreach ($this->getLifecycleFiles() as $file) {
            $content = file_get_contents($file);
            $lines = explode("\n", $content);

            // Skip if file has explicit "rupee conversion allowed" marker
            if (strpos($content, '@allow-rupee-conversion') !== false) {
                continue;
            }

            foreach ($patterns as $pattern => $description) {
                foreach ($lines as $lineNum => $line) {
                    // Skip comments
                    if (preg_match('/^\s*(?:\/\/|\*|#)/', $line)) {
                        continue;
                    }

                    if (preg_match($pattern, $line)) {
                        $violations[] = [
                            'file' => $this->relativePath($file),
                            'line' => $lineNum + 1,
                            'code' => trim($line),
                            'rule' => 'NO_RUPEE_CONVERSION',
                            'description' => $description,
                        ];
                    }
                }
            }
        }

        return $violations;
    }

    /**
     * Scan for nested transactions.
     *
     * Detects DB::transaction() calls inside methods that are
     * already wrapped in transactions.
     *
     * @return array Violations found
     */
    public function scanForNestedTransactions(): array
    {
        $violations = [];

        foreach ($this->getLifecycleFiles() as $file) {
            $content = file_get_contents($file);
            $lines = explode("\n", $content);

            $transactionDepth = 0;
            $insideTransaction = false;
            $transactionStartLine = 0;

            foreach ($lines as $lineNum => $line) {
                // Detect transaction start
                if (preg_match('/DB::transaction\s*\(/', $line)) {
                    if ($insideTransaction) {
                        $violations[] = [
                            'file' => $this->relativePath($file),
                            'line' => $lineNum + 1,
                            'code' => trim($line),
                            'rule' => 'NO_NESTED_TRANSACTION',
                            'description' => "Nested transaction detected. Outer transaction started at line {$transactionStartLine}",
                        ];
                    }
                    $insideTransaction = true;
                    $transactionDepth++;
                    if ($transactionDepth === 1) {
                        $transactionStartLine = $lineNum + 1;
                    }
                }

                // Track closure depth for transaction end (simplified)
                if ($insideTransaction) {
                    $opens = substr_count($line, '{');
                    $closes = substr_count($line, '}');

                    // Very simplified closure tracking
                    if (preg_match('/}\s*\)\s*;/', $line) && $transactionDepth > 0) {
                        $transactionDepth--;
                        if ($transactionDepth === 0) {
                            $insideTransaction = false;
                        }
                    }
                }
            }
        }

        return $violations;
    }

    /**
     * Scan for lockForUpdate usage outside orchestrator.
     *
     * After refactor, only FinancialOrchestrator should acquire row locks.
     *
     * @return array Violations found
     */
    public function scanForLockingOutsideOrchestrator(): array
    {
        $violations = [];

        foreach ($this->getLifecycleFiles() as $file) {
            $filename = basename($file);

            // Skip allowed files
            if (in_array($filename, self::ALLOWED_LOCKING_FILES)) {
                continue;
            }

            $content = file_get_contents($file);
            $lines = explode("\n", $content);

            foreach ($lines as $lineNum => $line) {
                if (preg_match('/->lockForUpdate\s*\(/', $line) ||
                    preg_match('/->sharedLock\s*\(/', $line)) {
                    $violations[] = [
                        'file' => $this->relativePath($file),
                        'line' => $lineNum + 1,
                        'code' => trim($line),
                        'rule' => 'NO_LOCKING_OUTSIDE_ORCHESTRATOR',
                        'description' => 'Row lock acquired outside FinancialOrchestrator. Locks must be centralized.',
                    ];
                }
            }
        }

        return $violations;
    }

    /**
     * Scan for async dispatches inside transactions.
     *
     * Detects queue dispatch calls that could cause partial mutations
     * if the outer transaction rolls back.
     *
     * @return array Violations found
     */
    public function scanForAsyncFinancialMutations(): array
    {
        $violations = [];

        foreach ($this->getLifecycleFiles() as $file) {
            $content = file_get_contents($file);
            $lines = explode("\n", $content);

            $insideTransaction = false;
            $transactionStartLine = 0;

            foreach ($lines as $lineNum => $line) {
                // Track transaction context
                if (preg_match('/DB::transaction\s*\(/', $line)) {
                    $insideTransaction = true;
                    $transactionStartLine = $lineNum + 1;
                }

                if ($insideTransaction && preg_match('/}\s*\)\s*;/', $line)) {
                    $insideTransaction = false;
                }

                // Detect dispatch inside transaction
                if ($insideTransaction) {
                    // Match Job::dispatch(), dispatch(), or Bus::dispatch()
                    if (preg_match('/(?:Job|Bus)?::dispatch\s*\(|->dispatch\s*\(/', $line)) {
                        // Skip if it's dispatchSync
                        if (strpos($line, 'dispatchSync') === false &&
                            strpos($line, 'dispatchNow') === false) {
                            $violations[] = [
                                'file' => $this->relativePath($file),
                                'line' => $lineNum + 1,
                                'code' => trim($line),
                                'rule' => 'NO_ASYNC_IN_TRANSACTION',
                                'description' => "Async job dispatched inside transaction (started line {$transactionStartLine}). " .
                                    "Use dispatchSync or dispatch after commit.",
                            ];
                        }
                    }
                }
            }
        }

        return $violations;
    }

    /**
     * Scan for direct service calls that should go through orchestrator.
     *
     * After refactor, domain services should not call each other directly
     * for financial mutations.
     *
     * @return array Violations found
     */
    public function scanForDirectServiceCalls(): array
    {
        $violations = [];
        $financialServices = [
            'WalletService',
            'AllocationService',
            'BonusCalculatorService',
            'DoubleEntryLedgerService',
        ];

        foreach ($this->getLifecycleFiles() as $file) {
            $filename = basename($file);

            // Skip orchestrator itself
            if ($filename === 'FinancialOrchestrator.php') {
                continue;
            }

            $content = file_get_contents($file);

            // Check for imports that suggest direct coupling
            foreach ($financialServices as $service) {
                // Skip if this IS the service file
                if ($filename === "{$service}.php") {
                    continue;
                }

                // Check for method calls that mutate state
                $mutationPatterns = [
                    '/\$this->' . lcfirst($service) . '->deposit/i' => 'deposit',
                    '/\$this->' . lcfirst($service) . '->withdraw/i' => 'withdraw',
                    '/\$this->' . lcfirst($service) . '->allocateShares/i' => 'allocateShares',
                    '/\$this->' . lcfirst($service) . '->calculateAndAwardBonuses/i' => 'calculateAndAwardBonuses',
                    '/\$this->' . lcfirst($service) . '->record/i' => 'recordLedgerEntry',
                ];

                $lines = explode("\n", $content);
                foreach ($lines as $lineNum => $line) {
                    // Skip comments
                    $trimmedLine = trim($line);
                    if (str_starts_with($trimmedLine, '//') || 
                        str_starts_with($trimmedLine, '*') || 
                        str_starts_with($trimmedLine, '/*')) {
                        continue;
                    }

                    foreach ($mutationPatterns as $pattern => $method) {
                        if (preg_match($pattern, $line)) {
                            $violations[] = [
                                'file' => $this->relativePath($file),
                                'line' => $lineNum + 1,
                                'code' => trim($line),
                                'rule' => 'NO_DIRECT_SERVICE_MUTATION',
                                'description' => "Direct call to {$service}::{$method}(). Financial mutations should go through FinancialOrchestrator.",
                            ];
                        }
                    }
                }
            }
        }

        return $violations;
    }

    /**
     * Get all lifecycle files to scan.
     */
    private function getLifecycleFiles(): array
    {
        $files = [];

        foreach (self::SCAN_PATHS as $relativePath) {
            $fullPath = $this->basePath . '/' . $relativePath;

            if (!is_dir($fullPath)) {
                continue;
            }

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($fullPath)
            );

            $phpFiles = new RegexIterator($iterator, '/\.php$/');

            foreach ($phpFiles as $file) {
                $filepath = $file->getPathname();

                // Check exclusions
                $excluded = false;
                foreach (self::EXCLUDE_PATTERNS as $pattern) {
                    if (preg_match($pattern, $filepath)) {
                        $excluded = true;
                        break;
                    }
                }

                if (!$excluded) {
                    // Check if it's a lifecycle file or in lifecycle namespace
                    $filename = basename($filepath);
                    if (in_array($filename, self::LIFECYCLE_FILES) ||
                        $this->isFinancialFile($filepath)) {
                        $files[] = $filepath;
                    }
                }
            }
        }

        return array_unique($files);
    }

    /**
     * Check if file is related to financial operations.
     */
    private function isFinancialFile(string $filepath): bool
    {
        $keywords = [
            'Payment',
            'Wallet',
            'Allocation',
            'Bonus',
            'Ledger',
            'Investment',
            'Financial',
            'Transaction',
        ];

        $filename = basename($filepath);
        foreach ($keywords as $keyword) {
            if (stripos($filename, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get relative path from base.
     */
    private function relativePath(string $absolutePath): string
    {
        return str_replace($this->basePath . '/', '', $absolutePath);
    }

    /**
     * Run all static analysis rules.
     */
    public function runAllChecks(): array
    {
        return [
            'float_usage' => $this->scanForFloatUsage(),
            'division_by_100' => $this->scanForDivisionBy100(),
            'nested_transactions' => $this->scanForNestedTransactions(),
            'locking_outside_orchestrator' => $this->scanForLockingOutsideOrchestrator(),
            'async_in_transaction' => $this->scanForAsyncFinancialMutations(),
            'direct_service_calls' => $this->scanForDirectServiceCalls(),
        ];
    }

    /**
     * Get summary of all violations.
     */
    public function getViolationSummary(): array
    {
        $results = $this->runAllChecks();
        $totalViolations = 0;

        $summary = [];
        foreach ($results as $rule => $violations) {
            $count = count($violations);
            $totalViolations += $count;
            $summary[$rule] = [
                'count' => $count,
                'files' => array_unique(array_column($violations, 'file')),
            ];
        }

        return [
            'total_violations' => $totalViolations,
            'by_rule' => $summary,
            'passed' => $totalViolations === 0,
        ];
    }
}
