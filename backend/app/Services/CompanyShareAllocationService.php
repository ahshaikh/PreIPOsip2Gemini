<?php

namespace App\Services;

use App\Models\BulkPurchase;
use App\Models\Company;
use App\Models\CompanyInvestment;
use App\Models\ShareAllocationLog;
use App\Models\User;
use App\Services\Accounting\AdminLedger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * CompanyShareAllocationService
 *
 * P0 FIX: Complete share allocation flow for company investments with:
 * 1. Per-lot provenance (links CompanyInvestment to BulkPurchase)
 * 2. Immutable allocation logs
 * 3. Custody state tracking
 * 4. AdminLedger integration
 *
 * This service answers:
 * "Where did each share go, and did platform receive money for it?"
 */
class CompanyShareAllocationService
{
    protected AdminLedger $adminLedger;

    public function __construct(AdminLedger $adminLedger)
    {
        $this->adminLedger = $adminLedger;
    }

    /**
     * Allocate shares from inventory to a company investment
     *
     * FIFO allocation: Uses oldest inventory first
     *
     * @param CompanyInvestment $investment
     * @param Company $company
     * @param User $user
     * @param int|null $adminLedgerEntryId Link to cash receipt proof
     * @return array{success: bool, allocation_log_id?: int, error?: string}
     */
    public function allocateForInvestment(
        CompanyInvestment $investment,
        Company $company,
        User $user,
        ?int $adminLedgerEntryId = null
    ): array {
        $amount = (float) $investment->amount;

        if ($amount <= 0) {
            return ['success' => false, 'error' => 'Investment amount must be positive'];
        }

        return DB::transaction(function () use ($investment, $company, $user, $amount, $adminLedgerEntryId) {
            // 1. Find available inventory for this company (FIFO)
            $batches = BulkPurchase::where('company_id', $company->id)
                ->where('value_remaining', '>', 0)
                ->orderBy('purchase_date', 'asc')
                ->lockForUpdate()
                ->get();

            $totalAvailable = $batches->sum('value_remaining');

            if ($totalAvailable < $amount) {
                Log::warning('[ALLOCATION] Insufficient inventory for company investment', [
                    'investment_id' => $investment->id,
                    'company_id' => $company->id,
                    'requested' => $amount,
                    'available' => $totalAvailable,
                ]);

                return [
                    'success' => false,
                    'error' => "Insufficient inventory. Requested: ₹{$amount}, Available: ₹{$totalAvailable}",
                    'available' => $totalAvailable,
                ];
            }

            $remainingToAllocate = $amount;
            $allocationLogs = [];
            $totalAllocated = 0;

            foreach ($batches as $batch) {
                if ($remainingToAllocate <= 0.01) {
                    break;
                }

                $allocateFromBatch = min($batch->value_remaining, $remainingToAllocate);
                if ($allocateFromBatch < 0.01) {
                    continue;
                }

                $inventoryBefore = $batch->value_remaining;

                // 2. Decrement inventory
                $batch->decrement('value_remaining', $allocateFromBatch);
                $batch->refresh();

                $inventoryAfter = $batch->value_remaining;

                // 3. Create immutable allocation log
                $allocationLog = ShareAllocationLog::create([
                    'bulk_purchase_id' => $batch->id,
                    'allocatable_type' => CompanyInvestment::class,
                    'allocatable_id' => $investment->id,
                    'value_allocated' => $allocateFromBatch,
                    'units_allocated' => null, // Company investments don't have unit granularity
                    'inventory_before' => $inventoryBefore,
                    'inventory_after' => $inventoryAfter,
                    'admin_ledger_entry_id' => $adminLedgerEntryId,
                    'company_id' => $company->id,
                    'user_id' => $user->id,
                    'allocated_by' => null, // Auto-allocation
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'metadata' => [
                        'batch_purchase_date' => $batch->purchase_date?->toDateString(),
                        'batch_discount_percentage' => $batch->discount_percentage,
                    ],
                ]);

                $allocationLogs[] = $allocationLog;
                $totalAllocated += $allocateFromBatch;
                $remainingToAllocate -= $allocateFromBatch;

                Log::info('[ALLOCATION] Share allocation from batch', [
                    'allocation_log_id' => $allocationLog->id,
                    'investment_id' => $investment->id,
                    'bulk_purchase_id' => $batch->id,
                    'amount' => $allocateFromBatch,
                    'inventory_before' => $inventoryBefore,
                    'inventory_after' => $inventoryAfter,
                ]);
            }

            // 4. Update investment with allocation info
            // Use the first batch for the primary link (most significant allocation)
            $primaryBatchId = $allocationLogs[0]->bulk_purchase_id ?? null;

            $investment->update([
                'bulk_purchase_id' => $primaryBatchId,
                'admin_ledger_entry_id' => $adminLedgerEntryId,
                'allocation_status' => $totalAllocated >= $amount ? 'allocated' : 'partially_allocated',
                'allocated_value' => $totalAllocated,
            ]);

            Log::info('[ALLOCATION] Company investment allocation complete', [
                'investment_id' => $investment->id,
                'total_allocated' => $totalAllocated,
                'allocation_logs_count' => count($allocationLogs),
                'primary_batch_id' => $primaryBatchId,
            ]);

            return [
                'success' => true,
                'allocation_log_ids' => collect($allocationLogs)->pluck('id')->toArray(),
                'total_allocated' => $totalAllocated,
                'batches_used' => count($allocationLogs),
            ];
        });
    }

    /**
     * Get full allocation chain for an investment
     *
     * Returns provenance proving: BulkPurchase → CompanyInvestment → Cash
     *
     * @param int $investmentId
     * @return array
     */
    public function getProvenanceChain(int $investmentId): array
    {
        $investment = CompanyInvestment::with(['company', 'user'])->find($investmentId);

        if (!$investment) {
            return ['error' => 'Investment not found'];
        }

        $allocationLogs = ShareAllocationLog::where('allocatable_type', CompanyInvestment::class)
            ->where('allocatable_id', $investmentId)
            ->with(['bulkPurchase', 'adminLedgerEntry'])
            ->get();

        // Get AdminLedger entry for cash proof
        $cashProof = null;
        if ($investment->admin_ledger_entry_id) {
            $cashProof = $this->adminLedger->getShareSaleEntryForInvestment($investmentId);
        }

        return [
            'investment' => [
                'id' => $investment->id,
                'amount' => $investment->amount,
                'allocated_value' => $investment->allocated_value,
                'allocation_status' => $investment->allocation_status,
                'invested_at' => $investment->invested_at?->toIso8601String(),
            ],
            'user' => [
                'id' => $investment->user_id,
                'name' => $investment->user?->name,
            ],
            'company' => [
                'id' => $investment->company_id,
                'name' => $investment->company?->name,
            ],
            'allocation_chain' => $allocationLogs->map(function ($log) {
                return $log->getProvenanceChain();
            })->toArray(),
            'cash_proof' => $cashProof ? [
                'ledger_entry_id' => $cashProof->id,
                'account' => $cashProof->account,
                'amount_paise' => $cashProof->amount_paise,
                'amount_rupees' => $cashProof->amount_paise / 100,
                'created_at' => $cashProof->created_at->toIso8601String(),
                'description' => $cashProof->description,
            ] : null,
            'is_fully_traced' => $allocationLogs->isNotEmpty() && $cashProof !== null,
            'generated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Reverse allocation for an investment
     *
     * Creates compensating log entries and restores inventory
     *
     * @param CompanyInvestment $investment
     * @param string $reason
     * @return array{success: bool, restored_value?: float, error?: string}
     */
    public function reverseAllocation(CompanyInvestment $investment, string $reason): array
    {
        return DB::transaction(function () use ($investment, $reason) {
            $allocationLogs = ShareAllocationLog::where('allocatable_type', CompanyInvestment::class)
                ->where('allocatable_id', $investment->id)
                ->where('is_reversed', false)
                ->get();

            if ($allocationLogs->isEmpty()) {
                return ['success' => true, 'restored_value' => 0, 'message' => 'No active allocations to reverse'];
            }

            $totalRestored = 0;

            foreach ($allocationLogs as $log) {
                // Lock and restore inventory
                $batch = BulkPurchase::lockForUpdate()->find($log->bulk_purchase_id);
                if ($batch) {
                    $batch->increment('value_remaining', $log->value_allocated);
                }

                // Create compensating log entry
                $reversalLog = ShareAllocationLog::create([
                    'bulk_purchase_id' => $log->bulk_purchase_id,
                    'allocatable_type' => CompanyInvestment::class,
                    'allocatable_id' => $investment->id,
                    'value_allocated' => -$log->value_allocated, // Negative for reversal
                    'units_allocated' => $log->units_allocated ? -$log->units_allocated : null,
                    'inventory_before' => $batch?->value_remaining - $log->value_allocated,
                    'inventory_after' => $batch?->value_remaining,
                    'admin_ledger_entry_id' => null,
                    'company_id' => $log->company_id,
                    'user_id' => $log->user_id,
                    'allocated_by' => auth()->id(),
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'metadata' => [
                        'reversal_of_log_id' => $log->id,
                        'reason' => $reason,
                    ],
                ]);

                // Mark original as reversed
                $log->update([
                    'is_reversed' => true,
                    'reversed_at' => now(),
                    'reversal_reason' => $reason,
                    'reversal_log_id' => $reversalLog->id,
                ]);

                $totalRestored += $log->value_allocated;
            }

            // Update investment
            $investment->update([
                'allocation_status' => 'unallocated',
                'allocated_value' => 0,
                'bulk_purchase_id' => null,
            ]);

            Log::info('[ALLOCATION] Investment allocation reversed', [
                'investment_id' => $investment->id,
                'total_restored' => $totalRestored,
                'logs_reversed' => $allocationLogs->count(),
                'reason' => $reason,
            ]);

            return [
                'success' => true,
                'restored_value' => $totalRestored,
                'logs_reversed' => $allocationLogs->count(),
            ];
        });
    }

    /**
     * Reconcile all allocations against inventory
     *
     * Proves: total_purchased == total_allocated + total_remaining
     *
     * @param int|null $companyId Filter by company
     * @return array
     */
    public function reconcileAllocations(?int $companyId = null): array
    {
        $query = BulkPurchase::query();
        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        $batches = $query->get();

        $totalPurchased = $batches->sum('total_value_received');
        $totalRemaining = $batches->sum('value_remaining');
        $totalAllocatedByBatch = $totalPurchased - $totalRemaining;

        // Get allocations from logs
        $logsQuery = ShareAllocationLog::where('is_reversed', false);
        if ($companyId) {
            $logsQuery->where('company_id', $companyId);
        }
        $totalAllocatedByLogs = $logsQuery->sum('value_allocated');

        // Check for discrepancy
        $discrepancy = abs($totalAllocatedByBatch - $totalAllocatedByLogs);
        $isReconciled = $discrepancy <= 1.00; // 1 rupee tolerance

        return [
            'is_reconciled' => $isReconciled,
            'company_id' => $companyId,
            'totals' => [
                'purchased' => $totalPurchased,
                'remaining' => $totalRemaining,
                'allocated_by_inventory' => $totalAllocatedByBatch,
                'allocated_by_logs' => $totalAllocatedByLogs,
            ],
            'conservation' => [
                'expected_allocated' => $totalPurchased - $totalRemaining,
                'actual_allocated' => $totalAllocatedByLogs,
                'discrepancy' => $discrepancy,
            ],
            'batch_count' => $batches->count(),
            'reconciled_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Get unallocated investments that need inventory assignment
     *
     * @param int|null $companyId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUnallocatedInvestments(?int $companyId = null)
    {
        $query = CompanyInvestment::where('allocation_status', 'unallocated')
            ->whereIn('status', ['active', 'pending']);

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        return $query->get();
    }
}
