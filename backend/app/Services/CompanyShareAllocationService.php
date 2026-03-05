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
        $orchestrator = app(\App\Services\FinancialOrchestrator::class);
        return $orchestrator->executeCompanyAllocation($investment, $company, $user, $adminLedgerEntryId);
    }

    /**
     * V-ORCHESTRATION-2026: Core allocation logic using pre-locked batches.
     */
    public function executeAllocationLogic(
        CompanyInvestment $investment,
        Company $company,
        User $user,
        \Illuminate\Support\Collection $batches,
        ?int $adminLedgerEntryId = null
    ): array {
        $totalAvailable = $batches->sum('value_remaining');
        $amount = (float) $investment->amount;

        if ($totalAvailable < $amount) {
            throw new \Exception("Insufficient inventory for company #{$company->id}. Requested: {$amount}, Available: {$totalAvailable}");
        }

        $remainingToAllocate = $amount;
        $allocationLogs = [];

        foreach ($batches as $batch) {
            if ($remainingToAllocate <= 0.01) break;

            $allocateFromBatch = min($batch->value_remaining, $remainingToAllocate);
            if ($allocateFromBatch < 0.01) continue;

            $inventoryBefore = $batch->value_remaining;
            $batch->decrement('value_remaining', $allocateFromBatch);
            $inventoryAfter = $batch->value_remaining;

            // Create immutable log
            $allocationLog = ShareAllocationLog::create([
                'bulk_purchase_id' => $batch->id,
                'allocatable_type' => CompanyInvestment::class,
                'allocatable_id' => $investment->id,
                'value_allocated' => $allocateFromBatch,
                'inventory_before' => $inventoryBefore,
                'inventory_after' => $inventoryAfter,
                'admin_ledger_entry_id' => $adminLedgerEntryId,
                'company_id' => $company->id,
                'user_id' => $user->id,
                'metadata' => [
                    'batch_id' => $batch->id,
                ],
            ]);

            $allocationLogs[] = $allocationLog;
            $remainingToAllocate -= $allocateFromBatch;
        }

        // Update investment status
        $investment->update([
            'allocation_status' => 'allocated',
            'allocated_value' => $amount,
            'admin_ledger_entry_id' => $adminLedgerEntryId,
        ]);

        return [
            'success' => true,
            'allocation_log_ids' => collect($allocationLogs)->pluck('id')->toArray(),
        ];
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
        $orchestrator = app(\App\Services\FinancialOrchestrator::class);
        $orchestrator->reverseCompanyAllocation($investment, $reason);
        
        return ['success' => true];
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
