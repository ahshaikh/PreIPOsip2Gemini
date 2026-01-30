<?php

/**
 * EPIC 4 - GAP 1 & GAP 4: Platform Ledger Service
 *
 * PROTOCOL:
 * This service records platform capital movements with governance-grade guarantees.
 *
 * INVARIANT:
 * - Every inventory creation MUST have a corresponding ledger debit
 * - Inventory existence === proven platform capital movement
 * - Ledger entries are append-only and immutable
 *
 * FAILURE SEMANTICS:
 * - If ledger recording fails, the calling operation MUST rollback
 * - Hard failure over false success
 * - No silent or implicit financial state changes
 *
 * COMPLIANCE:
 * - Designed for regulator/auditor review
 * - Complete forensic trail
 * - No balance mutation hacks
 * - No silent reconciliation
 * - No historical rewrites
 */

namespace App\Services;

use App\Models\PlatformLedgerEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class PlatformLedgerService
{
    /**
     * Record a debit (capital out) for inventory purchase.
     *
     * PROTOCOL:
     * - MUST be called within the BulkPurchase creation transaction
     * - MUST fail loudly if recording fails
     * - Caller MUST rollback if this method throws
     *
     * @param string $sourceType Source type (e.g., 'bulk_purchase')
     * @param int $sourceId Source ID (e.g., bulk_purchase.id)
     * @param int $amountPaise Amount in paise (smallest currency unit)
     * @param string $description Human-readable description
     * @param string $currency Currency code (default: INR)
     * @param array|null $metadata Additional audit metadata
     * @return PlatformLedgerEntry The created ledger entry
     * @throws RuntimeException If ledger recording fails
     */
    public function debit(
        string $sourceType,
        int $sourceId,
        int $amountPaise,
        string $description,
        string $currency = 'INR',
        ?array $metadata = null
    ): PlatformLedgerEntry {
        // VALIDATION: Amount must be positive
        if ($amountPaise <= 0) {
            throw new RuntimeException(
                "Ledger debit amount must be positive. Received: {$amountPaise} paise. " .
                "Source: {$sourceType}#{$sourceId}"
            );
        }

        // Get current balance (last entry for this currency)
        $currentBalancePaise = $this->getCurrentBalance($currency);

        // Calculate new balance
        $newBalancePaise = $currentBalancePaise - $amountPaise;

        // Create the ledger entry
        $entry = PlatformLedgerEntry::create([
            'type' => PlatformLedgerEntry::TYPE_DEBIT,
            'amount_paise' => $amountPaise,
            'balance_before_paise' => $currentBalancePaise,
            'balance_after_paise' => $newBalancePaise,
            'currency' => $currency,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'description' => $description,
            'actor_id' => auth()->id(),
            'metadata' => $metadata ?? [
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'timestamp' => now()->toIso8601String(),
            ],
        ]);

        Log::info("PlatformLedger DEBIT recorded", [
            'entry_id' => $entry->id,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'amount_paise' => $amountPaise,
            'amount_rupees' => $amountPaise / 100,
            'balance_after_paise' => $newBalancePaise,
            'actor_id' => auth()->id(),
        ]);

        return $entry;
    }

    /**
     * Record a credit (capital in / reversal).
     *
     * PROTOCOL:
     * - Used for reversals when BulkPurchase is deleted (if ever allowed)
     * - MUST link to the original debit entry via entry_pair_id
     * - MUST fail loudly if recording fails
     *
     * @param string $sourceType Source type (e.g., 'bulk_purchase_reversal')
     * @param int $sourceId Source ID (e.g., bulk_purchase.id)
     * @param int $amountPaise Amount in paise
     * @param string $description Human-readable description
     * @param int|null $originalEntryId ID of the entry being reversed
     * @param string $currency Currency code (default: INR)
     * @param array|null $metadata Additional audit metadata
     * @return PlatformLedgerEntry The created ledger entry
     * @throws RuntimeException If ledger recording fails
     */
    public function credit(
        string $sourceType,
        int $sourceId,
        int $amountPaise,
        string $description,
        ?int $originalEntryId = null,
        string $currency = 'INR',
        ?array $metadata = null
    ): PlatformLedgerEntry {
        // VALIDATION: Amount must be positive
        if ($amountPaise <= 0) {
            throw new RuntimeException(
                "Ledger credit amount must be positive. Received: {$amountPaise} paise. " .
                "Source: {$sourceType}#{$sourceId}"
            );
        }

        // If reversing an original entry, validate it exists and is a debit
        if ($originalEntryId !== null) {
            $originalEntry = PlatformLedgerEntry::find($originalEntryId);
            if (!$originalEntry) {
                throw new RuntimeException(
                    "Original ledger entry #{$originalEntryId} not found for reversal. " .
                    "Source: {$sourceType}#{$sourceId}"
                );
            }
            if ($originalEntry->type !== PlatformLedgerEntry::TYPE_DEBIT) {
                throw new RuntimeException(
                    "Cannot reverse a credit entry. Original entry #{$originalEntryId} is a {$originalEntry->type}. " .
                    "Source: {$sourceType}#{$sourceId}"
                );
            }
            if ($originalEntry->isReversed()) {
                throw new RuntimeException(
                    "Original entry #{$originalEntryId} has already been reversed. " .
                    "Double-reversal is not allowed. Source: {$sourceType}#{$sourceId}"
                );
            }
        }

        // Get current balance
        $currentBalancePaise = $this->getCurrentBalance($currency);

        // Calculate new balance (credit adds to balance)
        $newBalancePaise = $currentBalancePaise + $amountPaise;

        // Create the ledger entry
        $entry = PlatformLedgerEntry::create([
            'type' => PlatformLedgerEntry::TYPE_CREDIT,
            'amount_paise' => $amountPaise,
            'balance_before_paise' => $currentBalancePaise,
            'balance_after_paise' => $newBalancePaise,
            'currency' => $currency,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'description' => $description,
            'entry_pair_id' => $originalEntryId,
            'actor_id' => auth()->id(),
            'metadata' => $metadata ?? [
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'timestamp' => now()->toIso8601String(),
                'reversal_of' => $originalEntryId,
            ],
        ]);

        Log::info("PlatformLedger CREDIT recorded", [
            'entry_id' => $entry->id,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'amount_paise' => $amountPaise,
            'amount_rupees' => $amountPaise / 100,
            'balance_after_paise' => $newBalancePaise,
            'original_entry_id' => $originalEntryId,
            'actor_id' => auth()->id(),
        ]);

        return $entry;
    }

    /**
     * Get the current balance for a currency.
     *
     * Balance is determined from the most recent entry's balance_after_paise.
     *
     * @param string $currency Currency code
     * @return int Current balance in paise
     */
    public function getCurrentBalance(string $currency = 'INR'): int
    {
        $lastEntry = PlatformLedgerEntry::where('currency', $currency)
            ->orderBy('id', 'desc')
            ->first();

        return $lastEntry ? $lastEntry->balance_after_paise : 0;
    }

    /**
     * Get ledger entries for a specific source.
     *
     * @param string $sourceType Source type
     * @param int $sourceId Source ID
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getEntriesForSource(string $sourceType, int $sourceId)
    {
        return PlatformLedgerEntry::forSource($sourceType, $sourceId)
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Verify ledger entry exists for a BulkPurchase.
     *
     * PROTOCOL:
     * Used to verify the invariant: inventory existence === proven capital movement.
     *
     * @param int $bulkPurchaseId BulkPurchase ID
     * @return bool True if ledger entry exists
     */
    public function hasLedgerEntryForBulkPurchase(int $bulkPurchaseId): bool
    {
        return PlatformLedgerEntry::forSource(
            PlatformLedgerEntry::SOURCE_BULK_PURCHASE,
            $bulkPurchaseId
        )->exists();
    }

    /**
     * Get ledger entry for a BulkPurchase.
     *
     * @param int $bulkPurchaseId BulkPurchase ID
     * @return PlatformLedgerEntry|null
     */
    public function getLedgerEntryForBulkPurchase(int $bulkPurchaseId): ?PlatformLedgerEntry
    {
        return PlatformLedgerEntry::forSource(
            PlatformLedgerEntry::SOURCE_BULK_PURCHASE,
            $bulkPurchaseId
        )->first();
    }

    /**
     * Get complete ledger history with pagination.
     *
     * @param string|null $currency Filter by currency
     * @param string|null $type Filter by type (debit/credit)
     * @param int $perPage Items per page
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getLedgerHistory(?string $currency = null, ?string $type = null, int $perPage = 50)
    {
        $query = PlatformLedgerEntry::query()->orderBy('created_at', 'desc');

        if ($currency) {
            $query->where('currency', $currency);
        }

        if ($type) {
            $query->where('type', $type);
        }

        return $query->paginate($perPage);
    }

    /**
     * Get ledger summary statistics.
     *
     * @param string $currency Currency code
     * @return array Summary statistics
     */
    public function getLedgerSummary(string $currency = 'INR'): array
    {
        $totals = DB::table('platform_ledger_entries')
            ->where('currency', $currency)
            ->selectRaw("
                SUM(CASE WHEN type = 'debit' THEN amount_paise ELSE 0 END) as total_debits_paise,
                SUM(CASE WHEN type = 'credit' THEN amount_paise ELSE 0 END) as total_credits_paise,
                COUNT(CASE WHEN type = 'debit' THEN 1 END) as debit_count,
                COUNT(CASE WHEN type = 'credit' THEN 1 END) as credit_count
            ")
            ->first();

        $currentBalance = $this->getCurrentBalance($currency);

        return [
            'currency' => $currency,
            'current_balance_paise' => $currentBalance,
            'current_balance' => $currentBalance / 100,
            'total_debits_paise' => (int) ($totals->total_debits_paise ?? 0),
            'total_debits' => ($totals->total_debits_paise ?? 0) / 100,
            'total_credits_paise' => (int) ($totals->total_credits_paise ?? 0),
            'total_credits' => ($totals->total_credits_paise ?? 0) / 100,
            'debit_count' => (int) ($totals->debit_count ?? 0),
            'credit_count' => (int) ($totals->credit_count ?? 0),
            'net_movement_paise' => (int) (($totals->total_debits_paise ?? 0) - ($totals->total_credits_paise ?? 0)),
            'net_movement' => (($totals->total_debits_paise ?? 0) - ($totals->total_credits_paise ?? 0)) / 100,
        ];
    }

    /**
     * Verify ledger integrity.
     *
     * Checks that running balances are consistent.
     *
     * @param string $currency Currency code
     * @return array Integrity check result
     */
    public function verifyLedgerIntegrity(string $currency = 'INR'): array
    {
        $entries = PlatformLedgerEntry::where('currency', $currency)
            ->orderBy('id', 'asc')
            ->get();

        $violations = [];
        $expectedBalance = 0;

        foreach ($entries as $index => $entry) {
            // Check balance_before matches expected
            if ($entry->balance_before_paise !== $expectedBalance) {
                $violations[] = [
                    'entry_id' => $entry->id,
                    'position' => $index + 1,
                    'type' => 'balance_before_mismatch',
                    'expected_before' => $expectedBalance,
                    'actual_before' => $entry->balance_before_paise,
                ];
            }

            // Calculate expected balance after
            if ($entry->type === PlatformLedgerEntry::TYPE_DEBIT) {
                $expectedBalanceAfter = $entry->balance_before_paise - $entry->amount_paise;
            } else {
                $expectedBalanceAfter = $entry->balance_before_paise + $entry->amount_paise;
            }

            // Check balance_after matches expected
            if ($entry->balance_after_paise !== $expectedBalanceAfter) {
                $violations[] = [
                    'entry_id' => $entry->id,
                    'position' => $index + 1,
                    'type' => 'balance_after_mismatch',
                    'expected_after' => $expectedBalanceAfter,
                    'actual_after' => $entry->balance_after_paise,
                ];
            }

            // Update expected balance for next iteration
            $expectedBalance = $entry->balance_after_paise;
        }

        $isIntact = empty($violations);

        if (!$isIntact) {
            Log::critical("PLATFORM LEDGER INTEGRITY VIOLATION", [
                'currency' => $currency,
                'violations' => $violations,
            ]);
        }

        return [
            'currency' => $currency,
            'is_intact' => $isIntact,
            'total_entries' => $entries->count(),
            'violations' => $violations,
            'violation_count' => count($violations),
        ];
    }
}
