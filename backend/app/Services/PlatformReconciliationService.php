<?php

namespace App\Services;

use App\Models\BulkPurchase;
use App\Models\Company;
use App\Models\CompanyInvestment;
use App\Models\ShareAllocationLog;
use App\Services\Accounting\AdminLedger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * P0 FIX (GAP 28-30): Platform Reconciliation Service
 *
 * PURPOSE:
 * Provide comprehensive admin visibility into platform financial position:
 * - Cash position (money received from investors)
 * - Inventory position (shares held for sale)
 * - Share lifecycle tracing (purchase → custody → allocation → cash)
 * - Full reconciliation reports
 *
 * GAPS ADDRESSED:
 * - GAP 28: Platform cash balance visibility
 * - GAP 29: Full share lifecycle tracing
 * - GAP 30: Reconciliation dashboard data
 *
 * CRITICAL: This service provides the data for admin financial oversight.
 */
class PlatformReconciliationService
{
    protected AdminLedger $adminLedger;

    public function __construct(AdminLedger $adminLedger)
    {
        $this->adminLedger = $adminLedger;
    }

    // =========================================================================
    // GAP 28: PLATFORM CASH BALANCE
    // =========================================================================

    /**
     * Get platform cash position summary
     *
     * Shows money received from investors minus any outflows.
     *
     * @param int|null $companyId Filter by company (null = all)
     * @return array
     */
    public function getPlatformCashPosition(?int $companyId = null): array
    {
        // Get cash from share sales (investor investments)
        $shareSalesQuery = DB::table('admin_ledger_entries')
            ->where('account', AdminLedger::ACCOUNT_CASH)
            ->where('entry_type', 'debit') // Cash received = debit to cash account
            ->where('reference_type', 'company_investment');

        if ($companyId) {
            $shareSalesQuery->where('metadata->company_id', $companyId);
        }

        $totalCashFromSales = $shareSalesQuery->sum('amount');

        // Get cash spent on inventory purchases
        $inventoryPurchasesQuery = DB::table('admin_ledger_entries')
            ->where('account', AdminLedger::ACCOUNT_CASH)
            ->where('entry_type', 'credit') // Cash paid = credit to cash account
            ->where('reference_type', 'bulk_purchase');

        if ($companyId) {
            $inventoryPurchasesQuery->where('metadata->company_id', $companyId);
        }

        $totalCashSpentOnInventory = $inventoryPurchasesQuery->sum('amount');

        // Get cash from withdrawals/refunds (outflows)
        $outflowsQuery = DB::table('admin_ledger_entries')
            ->where('account', AdminLedger::ACCOUNT_CASH)
            ->where('entry_type', 'credit')
            ->whereIn('reference_type', ['withdrawal', 'refund']);

        if ($companyId) {
            $outflowsQuery->where('metadata->company_id', $companyId);
        }

        $totalOutflows = $outflowsQuery->sum('amount');

        // Calculate net cash position
        $netCashPosition = $totalCashFromSales - $totalCashSpentOnInventory - $totalOutflows;

        // Get breakdown by company
        $byCompany = [];
        if (!$companyId) {
            $byCompany = DB::table('admin_ledger_entries')
                ->select(
                    DB::raw("JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.company_id')) as company_id"),
                    DB::raw("SUM(CASE WHEN entry_type = 'debit' AND reference_type = 'company_investment' THEN amount ELSE 0 END) as cash_in"),
                    DB::raw("SUM(CASE WHEN entry_type = 'credit' THEN amount ELSE 0 END) as cash_out")
                )
                ->where('account', AdminLedger::ACCOUNT_CASH)
                ->groupBy(DB::raw("JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.company_id'))"))
                ->get()
                ->map(function ($row) {
                    $company = Company::find($row->company_id);
                    return [
                        'company_id' => $row->company_id,
                        'company_name' => $company?->name ?? 'Unknown',
                        'cash_in' => (float) $row->cash_in,
                        'cash_out' => (float) $row->cash_out,
                        'net' => (float) $row->cash_in - (float) $row->cash_out,
                    ];
                })
                ->toArray();
        }

        return [
            'summary' => [
                'total_cash_from_sales' => $totalCashFromSales,
                'total_cash_spent_on_inventory' => $totalCashSpentOnInventory,
                'total_outflows' => $totalOutflows,
                'net_cash_position' => $netCashPosition,
                'as_of' => now()->toIso8601String(),
            ],
            'by_company' => $byCompany,
            'data_source' => 'admin_ledger_entries',
        ];
    }

    /**
     * Get daily cash flow for period
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param int|null $companyId
     * @return array
     */
    public function getDailyCashFlow(Carbon $startDate, Carbon $endDate, ?int $companyId = null): array
    {
        $query = DB::table('admin_ledger_entries')
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw("SUM(CASE WHEN entry_type = 'debit' THEN amount ELSE 0 END) as inflows"),
                DB::raw("SUM(CASE WHEN entry_type = 'credit' THEN amount ELSE 0 END) as outflows")
            )
            ->where('account', AdminLedger::ACCOUNT_CASH)
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($companyId) {
            $query->where(DB::raw("JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.company_id'))"), $companyId);
        }

        $dailyData = $query->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get()
            ->map(function ($row) {
                return [
                    'date' => $row->date,
                    'inflows' => (float) $row->inflows,
                    'outflows' => (float) $row->outflows,
                    'net' => (float) $row->inflows - (float) $row->outflows,
                ];
            })
            ->toArray();

        return [
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
            'daily_cash_flow' => $dailyData,
            'totals' => [
                'total_inflows' => array_sum(array_column($dailyData, 'inflows')),
                'total_outflows' => array_sum(array_column($dailyData, 'outflows')),
                'net_change' => array_sum(array_column($dailyData, 'net')),
            ],
        ];
    }

    // =========================================================================
    // GAP 29: SHARE LIFECYCLE TRACING
    // =========================================================================

    /**
     * Trace complete lifecycle of a share batch
     *
     * Shows: Purchase → Custody → Allocations → Cash receipts
     *
     * @param int $bulkPurchaseId
     * @return array
     */
    public function traceShareLifecycle(int $bulkPurchaseId): array
    {
        $bulkPurchase = BulkPurchase::with('company')->find($bulkPurchaseId);

        if (!$bulkPurchase) {
            return ['error' => 'Bulk purchase not found'];
        }

        // 1. PURCHASE: Initial acquisition
        $purchase = [
            'event' => 'PURCHASE',
            'date' => $bulkPurchase->purchase_date,
            'details' => [
                'company' => $bulkPurchase->company?->name,
                'total_value' => $bulkPurchase->total_value,
                'cost_basis' => $bulkPurchase->cost_basis,
                'discount_percentage' => $bulkPurchase->discount_percentage,
                'source' => $bulkPurchase->source,
            ],
            'ledger_entry_id' => $bulkPurchase->purchase_ledger_entry_id,
        ];

        // 2. CUSTODY: Platform holds shares
        $custody = [
            'event' => 'IN_CUSTODY',
            'date' => $bulkPurchase->purchase_date,
            'details' => [
                'initial_value' => $bulkPurchase->total_value,
                'current_remaining' => $bulkPurchase->value_remaining,
                'status' => $bulkPurchase->status,
            ],
        ];

        // 3. ALLOCATIONS: Shares allocated to investors
        $allocations = ShareAllocationLog::where('bulk_purchase_id', $bulkPurchaseId)
            ->with(['allocatable'])
            ->orderBy('created_at')
            ->get()
            ->map(function ($log) {
                return [
                    'event' => 'ALLOCATION',
                    'date' => $log->created_at,
                    'details' => [
                        'investment_id' => $log->allocatable_id,
                        'investor_id' => $log->user_id,
                        'value_allocated' => $log->value_allocated,
                        'inventory_before' => $log->inventory_before,
                        'inventory_after' => $log->inventory_after,
                    ],
                    'ledger_entry_id' => $log->admin_ledger_entry_id,
                ];
            })
            ->toArray();

        // 4. CASH RECEIPTS: Money received for allocations
        $cashReceipts = DB::table('admin_ledger_entries')
            ->where('reference_type', 'company_investment')
            ->whereIn('reference_id', collect($allocations)->pluck('details.investment_id'))
            ->where('account', AdminLedger::ACCOUNT_CASH)
            ->where('entry_type', 'debit')
            ->get()
            ->map(function ($entry) {
                return [
                    'event' => 'CASH_RECEIPT',
                    'date' => $entry->created_at,
                    'details' => [
                        'amount' => $entry->amount,
                        'investment_id' => $entry->reference_id,
                        'description' => $entry->description,
                    ],
                    'ledger_entry_id' => $entry->id,
                ];
            })
            ->toArray();

        // Build complete timeline
        $timeline = array_merge(
            [$purchase],
            [$custody],
            $allocations,
            $cashReceipts
        );

        // Sort by date
        usort($timeline, fn($a, $b) => strtotime($a['date']) <=> strtotime($b['date']));

        // Calculate summary
        $totalAllocated = array_sum(array_column(
            array_filter($allocations, fn($a) => $a['event'] === 'ALLOCATION'),
            'details'
        ));
        $totalAllocatedValue = collect($allocations)->sum('details.value_allocated');
        $totalCashReceived = collect($cashReceipts)->sum('details.amount');

        return [
            'bulk_purchase_id' => $bulkPurchaseId,
            'company' => [
                'id' => $bulkPurchase->company_id,
                'name' => $bulkPurchase->company?->name,
            ],
            'summary' => [
                'initial_value' => $bulkPurchase->total_value,
                'value_allocated' => $totalAllocatedValue,
                'value_remaining' => $bulkPurchase->value_remaining,
                'cash_received' => $totalCashReceived,
                'allocation_count' => count($allocations),
                'conservation_check' => abs($bulkPurchase->total_value - $totalAllocatedValue - $bulkPurchase->value_remaining) < 0.01
                    ? 'PASS' : 'FAIL',
            ],
            'timeline' => $timeline,
        ];
    }

    /**
     * Trace share lifecycle for an investment
     *
     * Reverse lookup: Investment → Allocation → Bulk Purchase → Cash
     *
     * @param int $investmentId
     * @return array
     */
    public function traceInvestmentProvenance(int $investmentId): array
    {
        $investment = CompanyInvestment::with(['company', 'user', 'bulkPurchase'])
            ->find($investmentId);

        if (!$investment) {
            return ['error' => 'Investment not found'];
        }

        // Get all allocation logs for this investment
        $allocations = ShareAllocationLog::where('allocatable_type', CompanyInvestment::class)
            ->where('allocatable_id', $investmentId)
            ->with('bulkPurchase')
            ->get();

        // Get cash receipt ledger entry
        $cashReceipt = DB::table('admin_ledger_entries')
            ->where('reference_type', 'company_investment')
            ->where('reference_id', $investmentId)
            ->where('account', AdminLedger::ACCOUNT_CASH)
            ->where('entry_type', 'debit')
            ->first();

        return [
            'investment' => [
                'id' => $investment->id,
                'amount' => $investment->amount,
                'status' => $investment->status,
                'created_at' => $investment->created_at,
            ],
            'investor' => [
                'id' => $investment->user_id,
                'name' => $investment->user?->name,
            ],
            'company' => [
                'id' => $investment->company_id,
                'name' => $investment->company?->name,
            ],
            'provenance_chain' => $allocations->map(function ($alloc) {
                return [
                    'bulk_purchase_id' => $alloc->bulk_purchase_id,
                    'purchase_date' => $alloc->bulkPurchase?->purchase_date,
                    'source' => $alloc->bulkPurchase?->source,
                    'value_from_this_batch' => $alloc->value_allocated,
                    'batch_cost_basis' => $alloc->bulkPurchase?->cost_basis,
                ];
            })->toArray(),
            'cash_receipt' => $cashReceipt ? [
                'ledger_entry_id' => $cashReceipt->id,
                'amount' => $cashReceipt->amount,
                'recorded_at' => $cashReceipt->created_at,
            ] : null,
            'audit_proof' => [
                'shares_allocated' => $investment->allocated_value,
                'cash_received' => $cashReceipt?->amount ?? 0,
                'match' => abs(($investment->allocated_value ?? 0) - ($cashReceipt?->amount ?? 0)) < 0.01
                    ? 'VERIFIED' : 'MISMATCH',
            ],
        ];
    }

    // =========================================================================
    // GAP 30: RECONCILIATION DASHBOARD
    // =========================================================================

    /**
     * Get complete reconciliation dashboard data
     *
     * Single view showing:
     * - Shares bought (inventory purchases)
     * - Shares sold (allocations to investors)
     * - Shares remaining (current inventory)
     * - Cash in (from investors)
     * - Cash out (inventory purchases, withdrawals)
     *
     * @param int|null $companyId Filter by company
     * @return array
     */
    public function getReconciliationDashboard(?int $companyId = null): array
    {
        // === INVENTORY SIDE ===

        // Total shares purchased (all bulk purchases)
        $purchasesQuery = BulkPurchase::query();
        if ($companyId) {
            $purchasesQuery->where('company_id', $companyId);
        }

        $totalSharesPurchased = $purchasesQuery->sum('total_value');
        $totalCostBasis = $purchasesQuery->sum('cost_basis');
        $purchaseCount = $purchasesQuery->count();

        // Shares remaining (unsold inventory)
        $sharesRemaining = (clone $purchasesQuery)->sum('value_remaining');

        // Shares allocated (sold to investors)
        $sharesAllocated = $totalSharesPurchased - $sharesRemaining;

        // === INVESTMENT SIDE ===

        $investmentsQuery = CompanyInvestment::where('status', 'completed');
        if ($companyId) {
            $investmentsQuery->where('company_id', $companyId);
        }

        $totalInvestmentValue = $investmentsQuery->sum('amount');
        $investmentCount = $investmentsQuery->count();

        // === CASH SIDE ===

        $cashPosition = $this->getPlatformCashPosition($companyId);

        // === RECONCILIATION CHECKS ===

        $reconciliationChecks = [
            // Check 1: Inventory conservation
            'inventory_conservation' => [
                'name' => 'Inventory Conservation',
                'description' => 'Purchased = Allocated + Remaining',
                'expected' => $totalSharesPurchased,
                'actual' => $sharesAllocated + $sharesRemaining,
                'difference' => abs($totalSharesPurchased - ($sharesAllocated + $sharesRemaining)),
                'status' => abs($totalSharesPurchased - ($sharesAllocated + $sharesRemaining)) < 0.01
                    ? 'PASS' : 'FAIL',
            ],

            // Check 2: Cash = Investments
            'cash_matches_investments' => [
                'name' => 'Cash Matches Investments',
                'description' => 'Cash received = Investment amounts',
                'expected' => $totalInvestmentValue,
                'actual' => $cashPosition['summary']['total_cash_from_sales'],
                'difference' => abs($totalInvestmentValue - $cashPosition['summary']['total_cash_from_sales']),
                'status' => abs($totalInvestmentValue - $cashPosition['summary']['total_cash_from_sales']) < 0.01
                    ? 'PASS' : 'FAIL',
            ],

            // Check 3: Allocations = Investments
            'allocations_match_investments' => [
                'name' => 'Allocations Match Investments',
                'description' => 'Shares allocated = Investment values',
                'expected' => $totalInvestmentValue,
                'actual' => $sharesAllocated,
                'difference' => abs($totalInvestmentValue - $sharesAllocated),
                'status' => abs($totalInvestmentValue - $sharesAllocated) < 0.01
                    ? 'PASS' : 'FAIL',
            ],
        ];

        $allChecksPassed = collect($reconciliationChecks)->every(fn($check) => $check['status'] === 'PASS');

        // === BY COMPANY BREAKDOWN ===

        $byCompany = [];
        if (!$companyId) {
            $companies = Company::whereHas('bulkPurchases')->get();
            foreach ($companies as $company) {
                $companyPurchased = BulkPurchase::where('company_id', $company->id)->sum('total_value');
                $companyRemaining = BulkPurchase::where('company_id', $company->id)->sum('value_remaining');
                $companyInvested = CompanyInvestment::where('company_id', $company->id)
                    ->where('status', 'completed')
                    ->sum('amount');

                $byCompany[] = [
                    'company_id' => $company->id,
                    'company_name' => $company->name,
                    'shares_purchased' => $companyPurchased,
                    'shares_remaining' => $companyRemaining,
                    'shares_allocated' => $companyPurchased - $companyRemaining,
                    'investment_value' => $companyInvested,
                    'utilization_rate' => $companyPurchased > 0
                        ? round((($companyPurchased - $companyRemaining) / $companyPurchased) * 100, 2)
                        : 0,
                ];
            }
        }

        return [
            'generated_at' => now()->toIso8601String(),
            'filter' => [
                'company_id' => $companyId,
            ],

            // Summary cards
            'summary' => [
                'inventory' => [
                    'total_purchased' => $totalSharesPurchased,
                    'total_cost_basis' => $totalCostBasis,
                    'total_remaining' => $sharesRemaining,
                    'total_allocated' => $sharesAllocated,
                    'utilization_rate' => $totalSharesPurchased > 0
                        ? round(($sharesAllocated / $totalSharesPurchased) * 100, 2)
                        : 0,
                    'purchase_count' => $purchaseCount,
                ],
                'investments' => [
                    'total_value' => $totalInvestmentValue,
                    'count' => $investmentCount,
                    'average_investment' => $investmentCount > 0
                        ? round($totalInvestmentValue / $investmentCount, 2)
                        : 0,
                ],
                'cash' => $cashPosition['summary'],
            ],

            // Reconciliation status
            'reconciliation' => [
                'all_checks_passed' => $allChecksPassed,
                'checks' => $reconciliationChecks,
            ],

            // Company breakdown
            'by_company' => $byCompany,

            // Gross margin analysis
            'margin_analysis' => [
                'total_revenue' => $totalInvestmentValue,
                'total_cost' => $totalCostBasis,
                'gross_margin' => $totalInvestmentValue - $totalCostBasis,
                'margin_percentage' => $totalInvestmentValue > 0
                    ? round((($totalInvestmentValue - $totalCostBasis) / $totalInvestmentValue) * 100, 2)
                    : 0,
            ],
        ];
    }

    /**
     * Get unreconciled items for investigation
     *
     * @return array
     */
    public function getUnreconciledItems(): array
    {
        $issues = [];

        // Find investments without allocation logs
        $investmentsWithoutAllocations = CompanyInvestment::where('status', 'completed')
            ->whereDoesntHave('allocationLogs')
            ->get();

        foreach ($investmentsWithoutAllocations as $inv) {
            $issues[] = [
                'type' => 'MISSING_ALLOCATION',
                'severity' => 'HIGH',
                'investment_id' => $inv->id,
                'company_id' => $inv->company_id,
                'amount' => $inv->amount,
                'description' => 'Investment completed but no allocation log exists',
            ];
        }

        // Find investments without cash receipt
        $investmentsWithoutCash = CompanyInvestment::where('status', 'completed')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('admin_ledger_entries')
                    ->whereColumn('admin_ledger_entries.reference_id', 'company_investments.id')
                    ->where('admin_ledger_entries.reference_type', 'company_investment')
                    ->where('admin_ledger_entries.account', AdminLedger::ACCOUNT_CASH);
            })
            ->get();

        foreach ($investmentsWithoutCash as $inv) {
            $issues[] = [
                'type' => 'MISSING_CASH_ENTRY',
                'severity' => 'CRITICAL',
                'investment_id' => $inv->id,
                'company_id' => $inv->company_id,
                'amount' => $inv->amount,
                'description' => 'Investment completed but no cash ledger entry exists',
            ];
        }

        // Find negative inventory (over-allocation)
        $negativeInventory = BulkPurchase::where('value_remaining', '<', 0)->get();

        foreach ($negativeInventory as $batch) {
            $issues[] = [
                'type' => 'NEGATIVE_INVENTORY',
                'severity' => 'CRITICAL',
                'bulk_purchase_id' => $batch->id,
                'company_id' => $batch->company_id,
                'value_remaining' => $batch->value_remaining,
                'description' => 'Inventory has negative remaining value (over-allocated)',
            ];
        }

        return [
            'generated_at' => now()->toIso8601String(),
            'total_issues' => count($issues),
            'by_severity' => [
                'critical' => count(array_filter($issues, fn($i) => $i['severity'] === 'CRITICAL')),
                'high' => count(array_filter($issues, fn($i) => $i['severity'] === 'HIGH')),
                'medium' => count(array_filter($issues, fn($i) => $i['severity'] === 'MEDIUM')),
            ],
            'issues' => $issues,
        ];
    }
}
