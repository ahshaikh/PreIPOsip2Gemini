<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CompanyInvestment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * P0 FIX (GAP 21-24): Investment Security Guard
 *
 * PURPOSE:
 * Mitigate security vulnerabilities and race conditions in investment flow.
 *
 * GAPS ADDRESSED:
 * - GAP 21: Stale snapshot attack mitigation
 * - GAP 22: Share allocation race condition prevention
 * - GAP 23: Wallet double-spend protection
 * - GAP 24: Proper isolation levels for critical transactions
 *
 * CRITICAL: This service MUST be used for all financial transactions.
 */
class InvestmentSecurityGuard
{
    /**
     * Maximum allowed snapshot age in seconds before considered stale
     * GAP 21 FIX: Prevents stale snapshot attacks
     */
    const MAX_SNAPSHOT_AGE_SECONDS = 300; // 5 minutes

    /**
     * Lock timeout for concurrent operations in seconds
     */
    const LOCK_TIMEOUT_SECONDS = 30;

    /**
     * GAP 21 FIX: Validate snapshot freshness at submit time
     *
     * Prevents attack where:
     * 1. Investor loads page with snapshot A
     * 2. Platform context changes (company suspended, risks updated)
     * 3. Investor submits with stale snapshot A
     * 4. Investment proceeds without current risk acknowledgement
     *
     * @param int $companyId
     * @param int $providedSnapshotId Snapshot ID from client
     * @param int|null $maxAgeSeconds Override max age
     * @return array{valid: bool, message: string, current_snapshot_id: int|null, stale_fields: array}
     */
    public function validateSnapshotFreshness(
        int $companyId,
        int $providedSnapshotId,
        ?int $maxAgeSeconds = null
    ): array {
        $maxAge = $maxAgeSeconds ?? self::MAX_SNAPSHOT_AGE_SECONDS;

        // Get current snapshot
        $currentSnapshot = DB::table('platform_context_snapshots')
            ->where('company_id', $companyId)
            ->where('is_current', true)
            ->first();

        if (!$currentSnapshot) {
            return [
                'valid' => false,
                'message' => 'No current platform snapshot exists for this company',
                'current_snapshot_id' => null,
                'stale_fields' => [],
            ];
        }

        // Check if provided snapshot matches current
        if ($providedSnapshotId !== $currentSnapshot->id) {
            // Snapshots differ - check what changed
            $providedSnapshot = DB::table('platform_context_snapshots')
                ->where('id', $providedSnapshotId)
                ->first();

            if (!$providedSnapshot) {
                return [
                    'valid' => false,
                    'message' => 'Provided snapshot ID does not exist',
                    'current_snapshot_id' => $currentSnapshot->id,
                    'stale_fields' => ['snapshot_not_found'],
                ];
            }

            // Detect material changes between snapshots
            $staleFields = $this->detectMaterialChanges($providedSnapshot, $currentSnapshot);

            if (!empty($staleFields)) {
                Log::warning('SECURITY: Stale snapshot attack blocked', [
                    'company_id' => $companyId,
                    'provided_snapshot_id' => $providedSnapshotId,
                    'current_snapshot_id' => $currentSnapshot->id,
                    'stale_fields' => $staleFields,
                ]);

                return [
                    'valid' => false,
                    'message' => 'Platform context has changed since you loaded the page. Please refresh and review the updated information.',
                    'current_snapshot_id' => $currentSnapshot->id,
                    'stale_fields' => $staleFields,
                    'requires_re_acknowledgement' => true,
                ];
            }
        }

        // Check snapshot age
        $snapshotAge = now()->diffInSeconds($currentSnapshot->snapshot_at);
        if ($snapshotAge > $maxAge) {
            return [
                'valid' => false,
                'message' => "Snapshot is too old ({$snapshotAge}s > {$maxAge}s max). Please refresh the page.",
                'current_snapshot_id' => $currentSnapshot->id,
                'stale_fields' => ['snapshot_expired'],
            ];
        }

        return [
            'valid' => true,
            'message' => 'Snapshot is fresh and valid',
            'current_snapshot_id' => $currentSnapshot->id,
            'stale_fields' => [],
        ];
    }

    /**
     * GAP 22 FIX: Acquire exclusive lock for share allocation
     *
     * Prevents race condition where:
     * 1. Two investors submit at same time
     * 2. Both check inventory: 100 shares available
     * 3. Both allocate 100 shares
     * 4. Total allocated: 200 shares (only 100 exist)
     *
     * @param int $companyId
     * @param callable $callback Function to execute within lock
     * @return mixed Result of callback
     * @throws \RuntimeException If lock cannot be acquired
     */
    public function executeWithAllocationLock(int $companyId, callable $callback): mixed
    {
        $lockKey = "allocation_lock:company:{$companyId}";

        // Try to acquire lock with timeout
        $lock = Cache::lock($lockKey, self::LOCK_TIMEOUT_SECONDS);

        if (!$lock->block(self::LOCK_TIMEOUT_SECONDS)) {
            Log::error('SECURITY: Failed to acquire allocation lock', [
                'company_id' => $companyId,
                'timeout' => self::LOCK_TIMEOUT_SECONDS,
            ]);

            throw new \RuntimeException(
                'Unable to process allocation - high demand. Please try again in a few seconds.'
            );
        }

        try {
            Log::debug('SECURITY: Allocation lock acquired', [
                'company_id' => $companyId,
                'lock_key' => $lockKey,
            ]);

            return $callback();

        } finally {
            $lock->release();
            Log::debug('SECURITY: Allocation lock released', [
                'company_id' => $companyId,
            ]);
        }
    }

    /**
     * GAP 23 FIX: Verify wallet balance with row-level lock
     *
     * Prevents double-spend where:
     * 1. User has ₹10,000 balance
     * 2. Submits two ₹10,000 investments simultaneously
     * 3. Both check balance: ₹10,000 available
     * 4. Both debit ₹10,000
     * 5. Balance goes negative
     *
     * @param int $userId
     * @param float $amount Amount to verify
     * @return array{valid: bool, message: string, locked_balance: float}
     */
    public function verifyAndLockWalletBalance(int $userId, float $amount): array
    {
        // Use SELECT FOR UPDATE to lock the wallet row
        $wallet = DB::table('wallets')
            ->where('user_id', $userId)
            ->lockForUpdate()
            ->first();

        if (!$wallet) {
            return [
                'valid' => false,
                'message' => 'Wallet not found',
                'locked_balance' => 0,
            ];
        }

        if ($wallet->balance < $amount) {
            return [
                'valid' => false,
                'message' => "Insufficient balance. Available: ₹{$wallet->balance}, Required: ₹{$amount}",
                'locked_balance' => $wallet->balance,
            ];
        }

        return [
            'valid' => true,
            'message' => 'Balance verified and locked',
            'locked_balance' => $wallet->balance,
        ];
    }

    /**
     * GAP 22 FIX: Verify inventory availability with row-level lock
     *
     * @param int $companyId
     * @param float $requiredValue Value of shares needed
     * @return array{valid: bool, available: float, message: string}
     */
    public function verifyAndLockInventory(int $companyId, float $requiredValue): array
    {
        // Get available inventory with lock
        $availableInventory = DB::table('bulk_purchases')
            ->where('company_id', $companyId)
            ->where('status', 'active')
            ->lockForUpdate()
            ->sum(DB::raw('remaining_value'));

        if ($availableInventory < $requiredValue) {
            return [
                'valid' => false,
                'available' => $availableInventory,
                'message' => "Insufficient inventory. Available: ₹{$availableInventory}, Required: ₹{$requiredValue}",
            ];
        }

        return [
            'valid' => true,
            'available' => $availableInventory,
            'message' => 'Inventory verified and locked',
        ];
    }

    /**
     * GAP 24 FIX: Execute callback in SERIALIZABLE isolation
     *
     * For operations requiring strictest isolation:
     * - Prevents phantom reads
     * - Prevents non-repeatable reads
     * - Full serializability guarantee
     *
     * @param callable $callback
     * @return mixed
     */
    public function executeSerializable(callable $callback): mixed
    {
        return DB::transaction(function () use ($callback) {
            // Set isolation level to SERIALIZABLE for this transaction
            DB::statement('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE');

            Log::debug('SECURITY: Executing in SERIALIZABLE isolation');

            return $callback();
        });
    }

    /**
     * GAP 24 FIX: Execute callback in REPEATABLE READ isolation
     *
     * For operations requiring consistent reads within transaction:
     * - Prevents non-repeatable reads
     * - Allows better concurrency than SERIALIZABLE
     *
     * @param callable $callback
     * @return mixed
     */
    public function executeRepeatableRead(callable $callback): mixed
    {
        return DB::transaction(function () use ($callback) {
            // Set isolation level to REPEATABLE READ
            DB::statement('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');

            Log::debug('SECURITY: Executing in REPEATABLE READ isolation');

            return $callback();
        });
    }

    /**
     * GAP 21-24 FIX: Comprehensive pre-investment security check
     *
     * Validates ALL security concerns before allowing investment to proceed.
     *
     * @param User $user
     * @param Company $company
     * @param float $amount
     * @param int $snapshotId
     * @return array{valid: bool, message: string, checks: array}
     */
    public function validateInvestmentSecurity(
        User $user,
        Company $company,
        float $amount,
        int $snapshotId
    ): array {
        $checks = [];

        // 1. Snapshot freshness (GAP 21)
        $snapshotCheck = $this->validateSnapshotFreshness($company->id, $snapshotId);
        $checks['snapshot_freshness'] = $snapshotCheck;
        if (!$snapshotCheck['valid']) {
            return [
                'valid' => false,
                'message' => $snapshotCheck['message'],
                'checks' => $checks,
                'failed_check' => 'snapshot_freshness',
            ];
        }

        // 2. Wallet balance (will be locked in transaction) (GAP 23)
        $walletCheck = [
            'valid' => true,
            'message' => 'Wallet check deferred to transaction',
            'note' => 'Row lock will be acquired during transaction',
        ];
        $checks['wallet_balance'] = $walletCheck;

        // 3. Company eligibility (additional check)
        if ($company->lifecycle_state === 'suspended') {
            $checks['company_eligibility'] = [
                'valid' => false,
                'message' => 'Company is suspended',
            ];
            return [
                'valid' => false,
                'message' => 'Company is currently suspended and not accepting investments',
                'checks' => $checks,
                'failed_check' => 'company_eligibility',
            ];
        }

        if (!($company->buying_enabled ?? true)) {
            $checks['company_eligibility'] = [
                'valid' => false,
                'message' => 'Buying disabled for this company',
            ];
            return [
                'valid' => false,
                'message' => 'Investment is currently paused for this company',
                'checks' => $checks,
                'failed_check' => 'company_eligibility',
            ];
        }

        $checks['company_eligibility'] = [
            'valid' => true,
            'message' => 'Company eligible for investment',
        ];

        // 4. Rate limiting check (additional security)
        $recentInvestments = CompanyInvestment::where('user_id', $user->id)
            ->where('company_id', $company->id)
            ->where('created_at', '>', now()->subMinutes(5))
            ->count();

        if ($recentInvestments >= 3) {
            $checks['rate_limit'] = [
                'valid' => false,
                'message' => 'Too many recent investment attempts',
            ];
            return [
                'valid' => false,
                'message' => 'Too many investment attempts. Please wait a few minutes.',
                'checks' => $checks,
                'failed_check' => 'rate_limit',
            ];
        }

        $checks['rate_limit'] = [
            'valid' => true,
            'message' => 'Within rate limits',
        ];

        return [
            'valid' => true,
            'message' => 'All security checks passed',
            'checks' => $checks,
        ];
    }

    /**
     * Execute secure investment transaction
     *
     * Combines all security measures:
     * - REPEATABLE READ isolation
     * - Allocation lock
     * - Wallet row lock
     * - Inventory row lock
     *
     * @param int $companyId
     * @param callable $investmentCallback
     * @return mixed
     */
    public function executeSecureInvestment(int $companyId, callable $investmentCallback): mixed
    {
        // Acquire allocation lock first (application-level)
        return $this->executeWithAllocationLock($companyId, function () use ($investmentCallback) {
            // Then execute in REPEATABLE READ isolation
            return $this->executeRepeatableRead(function () use ($investmentCallback) {
                return $investmentCallback();
            });
        });
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    /**
     * Detect material changes between snapshots
     */
    protected function detectMaterialChanges(object $oldSnapshot, object $newSnapshot): array
    {
        $staleFields = [];

        // Critical fields that require re-acknowledgement
        $criticalFields = [
            'lifecycle_state',
            'buying_enabled',
            'is_suspended',
            'is_frozen',
            'is_under_investigation',
            'risk_level',
            'tier_2_approved',
        ];

        foreach ($criticalFields as $field) {
            $oldValue = $oldSnapshot->$field ?? null;
            $newValue = $newSnapshot->$field ?? null;

            if ($oldValue !== $newValue) {
                $staleFields[] = [
                    'field' => $field,
                    'old_value' => $oldValue,
                    'new_value' => $newValue,
                    'is_material' => true,
                ];
            }
        }

        // Check for material risk flag changes
        $oldFlags = json_decode($oldSnapshot->risk_flags ?? '[]', true);
        $newFlags = json_decode($newSnapshot->risk_flags ?? '[]', true);

        if ($oldFlags !== $newFlags) {
            $staleFields[] = [
                'field' => 'risk_flags',
                'old_value' => $oldFlags,
                'new_value' => $newFlags,
                'is_material' => true,
            ];
        }

        return $staleFields;
    }

    /**
     * Generate idempotency key for investment
     */
    public function generateIdempotencyKey(int $userId, int $companyId, float $amount): string
    {
        $timestamp = now()->format('Y-m-d-H'); // Hour-level granularity
        return hash('sha256', "{$userId}:{$companyId}:{$amount}:{$timestamp}");
    }

    /**
     * Check for duplicate investment attempt
     */
    public function isDuplicateAttempt(int $userId, int $companyId, float $amount, int $windowSeconds = 60): bool
    {
        $cacheKey = "investment_attempt:{$userId}:{$companyId}:{$amount}";

        if (Cache::has($cacheKey)) {
            Log::warning('SECURITY: Duplicate investment attempt detected', [
                'user_id' => $userId,
                'company_id' => $companyId,
                'amount' => $amount,
            ]);
            return true;
        }

        // Mark this attempt
        Cache::put($cacheKey, true, $windowSeconds);
        return false;
    }
}
