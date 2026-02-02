<?php

namespace App\Services\Accounting;

use App\Models\AdminLedgerEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * @deprecated PHASE 4.1: This service is DEPRECATED. Use DoubleEntryLedgerService instead.
 *
 * ============================================================================
 * PHASE 4.2 KILL SWITCH: ALL WRITE OPERATIONS ARE PERMANENTLY DISABLED
 * ============================================================================
 *
 * This service's write methods have been KILLED.
 * Any attempt to call them will throw a RuntimeException.
 *
 * This is NOT a soft deprecation. It is a HARD KILL to prevent:
 * - Accidental dual-ledger writes from cron jobs
 * - Resurrection by well-meaning developers
 * - Silent financial state corruption
 *
 * The only allowed operations are READ-ONLY queries on historical data.
 *
 * ============================================================================
 *
 * MIGRATION NOTICE:
 * - This admin-specific ledger has been replaced by unified double-entry accounting
 * - New code MUST use App\Services\DoubleEntryLedgerService
 * - Historical data in admin_ledger_entries table is preserved for audit
 * - This service remains for backward compatibility with existing records only
 *
 * REPLACEMENT MAPPING:
 * - recordPaymentReceived() -> DoubleEntryLedgerService::recordUserDeposit()
 * - recordBulkPurchase() -> DoubleEntryLedgerService::recordInventoryPurchase()
 * - recordBonusPaid() -> DoubleEntryLedgerService::recordBonusWithTds()
 * - recordWithdrawalProcessed() -> DoubleEntryLedgerService::recordWithdrawal()
 *
 * ============================================================================
 * LEGACY DOCUMENTATION (for historical context):
 * ============================================================================
 *
 * AdminLedger - Single Authoritative Accounting Boundary
 *
 * PROTOCOL:
 * 1. ALL admin financial operations MUST create ledger entries
 * 2. Double-entry accounting: every debit has corresponding credit
 * 3. Balance is CALCULATED from ledger, not stored separately
 * 4. Ledger entries are IMMUTABLE once created
 * 5. Admin solvency is provable at all times via ledger query
 *
 * ACCOUNTS:
 * - CASH: Liquid money admin has (from payments, etc.)
 * - INVENTORY: Money admin spent on bulk purchases
 * - LIABILITIES: Money admin owes (referral bonuses, campaign discounts, pending withdrawals)
 * - REVENUE: Money admin earned from user payments
 * - EXPENSES: Money admin paid out (bonuses, withdrawals)
 *
 * EQUATION (MUST ALWAYS BALANCE):
 * CASH + INVENTORY = LIABILITIES + REVENUE - EXPENSES
 */
class AdminLedger
{
    // Account types
    const ACCOUNT_CASH = 'cash';
    const ACCOUNT_INVENTORY = 'inventory';
    const ACCOUNT_LIABILITIES = 'liabilities';
    const ACCOUNT_REVENUE = 'revenue';
    const ACCOUNT_EXPENSES = 'expenses';

    /**
     * Record Payment Received from User
     *
     * Double-Entry:
     * - DEBIT: Cash (+₹10,000)
     * - CREDIT: Revenue (+₹10,000)
     *
     * @param float $amount
     * @param string $referenceType 'payment', 'investment', etc.
     * @param int $referenceId
     * @return array [debit_entry, credit_entry]
     */
    public function recordPaymentReceived(
        float $amount,
        string $referenceType,
        int $referenceId,
        ?string $description = null
    ): array {
        return $this->createDoubleEntry(
            debitAccount: self::ACCOUNT_CASH,
            creditAccount: self::ACCOUNT_REVENUE,
            amount: $amount,
            referenceType: $referenceType,
            referenceId: $referenceId,
            description: $description ?? "Payment received from user"
        );
    }

    /**
     * Record Inventory Purchase
     *
     * Double-Entry:
     * - DEBIT: Inventory (+₹7,000)
     * - CREDIT: Cash (-₹7,000)
     *
     * @param float $cost Actual cost paid (not face value)
     * @param int $bulkPurchaseId
     * @return array
     */
    public function recordInventoryPurchase(
        float $cost,
        int $bulkPurchaseId,
        ?string $description = null
    ): array {
        return $this->createDoubleEntry(
            debitAccount: self::ACCOUNT_INVENTORY,
            creditAccount: self::ACCOUNT_CASH,
            amount: $cost,
            referenceType: 'bulk_purchase',
            referenceId: $bulkPurchaseId,
            description: $description ?? "Inventory purchased"
        );
    }

    /**
     * Record Campaign Discount as Liability
     *
     * CRITICAL: Campaign discount is admin EXPENSE (lost revenue)
     *
     * Double-Entry:
     * - DEBIT: Expenses (+₹2,000)
     * - CREDIT: Liabilities (+₹2,000) [owed to user as share value]
     *
     * @param float $discountAmount
     * @param int $campaignUsageId
     * @return array
     */
    public function recordCampaignDiscount(
        float $discountAmount,
        int $campaignUsageId,
        int $investmentId,
        ?string $description = null
    ): array {
        return $this->createDoubleEntry(
            debitAccount: self::ACCOUNT_EXPENSES,
            creditAccount: self::ACCOUNT_LIABILITIES,
            amount: $discountAmount,
            referenceType: 'campaign_usage',
            referenceId: $campaignUsageId,
            description: $description ?? "Campaign discount applied (investment #{$investmentId})"
        );
    }

    /**
     * Record Referral Bonus as Liability (Before Payout)
     *
     * Double-Entry:
     * - DEBIT: Expenses (+₹500)
     * - CREDIT: Liabilities (+₹500) [owed to referrer]
     *
     * @param float $bonusAmount
     * @param int $referralId
     * @return array
     */
    public function recordReferralBonus(
        float $bonusAmount,
        int $referralId,
        ?string $description = null
    ): array {
        return $this->createDoubleEntry(
            debitAccount: self::ACCOUNT_EXPENSES,
            creditAccount: self::ACCOUNT_LIABILITIES,
            amount: $bonusAmount,
            referenceType: 'referral',
            referenceId: $referralId,
            description: $description ?? "Referral bonus commitment"
        );
    }

    /**
     * P0 FIX: Record Share Sale to Subscriber
     *
     * CRITICAL: This is the missing link for platform revenue tracking.
     * When a subscriber invests, the platform sells shares from inventory.
     *
     * Double-Entry:
     * - DEBIT: Cash (+₹10,000) [platform receives payment]
     * - CREDIT: Inventory (-₹10,000) [shares leave inventory]
     *
     * This entry MUST be created for every subscriber investment to:
     * 1. Track platform cash position
     * 2. Link cash received to shares sold
     * 3. Enable cash-share reconciliation
     *
     * @param float $saleAmount Amount received from subscriber
     * @param int $investmentId CompanyInvestment ID
     * @param int $companyId Company ID for audit trail
     * @param int|null $bulkPurchaseId Optional link to specific inventory lot
     * @return array [debit_entry, credit_entry]
     */
    public function recordShareSale(
        float $saleAmount,
        int $investmentId,
        int $companyId,
        ?int $bulkPurchaseId = null,
        ?string $description = null
    ): array {
        $desc = $description ?? "Share sale to subscriber (investment #{$investmentId}, company #{$companyId})";

        if ($bulkPurchaseId) {
            $desc .= " from bulk purchase #{$bulkPurchaseId}";
        }

        return $this->createDoubleEntry(
            debitAccount: self::ACCOUNT_CASH,
            creditAccount: self::ACCOUNT_INVENTORY,
            amount: $saleAmount,
            referenceType: 'company_investment',
            referenceId: $investmentId,
            description: $desc
        );
    }

    /**
     * P0 FIX: Record Inventory Allocation (Alternative to recordShareSale)
     *
     * Use this when you want to track inventory reduction separately
     * from cash receipt (e.g., for free allocations, bonuses, etc.)
     *
     * Double-Entry:
     * - DEBIT: Expenses (+₹X) [cost of shares given]
     * - CREDIT: Inventory (-₹X) [shares leave inventory]
     *
     * @param float $allocationValue Value of shares allocated
     * @param int $investmentId
     * @param string $reason 'subscriber_purchase', 'bonus_allocation', 'promotional'
     * @return array
     */
    public function recordInventoryAllocation(
        float $allocationValue,
        int $investmentId,
        string $reason = 'subscriber_purchase',
        ?string $description = null
    ): array {
        return $this->createDoubleEntry(
            debitAccount: self::ACCOUNT_EXPENSES,
            creditAccount: self::ACCOUNT_INVENTORY,
            amount: $allocationValue,
            referenceType: 'inventory_allocation',
            referenceId: $investmentId,
            description: $description ?? "Inventory allocated: {$reason}"
        );
    }

    /**
     * Record Bonus Payout (After Wallet Credit)
     *
     * Settles liability:
     * - DEBIT: Liabilities (-₹500) [liability settled]
     * - CREDIT: Cash (-₹500) [cash paid out]
     *
     * @param float $bonusAmount
     * @param int $referralId
     * @return array
     */
    public function recordBonusPayout(
        float $bonusAmount,
        int $referralId,
        ?string $description = null
    ): array {
        return $this->createDoubleEntry(
            debitAccount: self::ACCOUNT_LIABILITIES,
            creditAccount: self::ACCOUNT_CASH,
            amount: $bonusAmount,
            referenceType: 'referral',
            referenceId: $referralId,
            description: $description ?? "Referral bonus paid"
        );
    }

    /**
     * Record Withdrawal Approved
     *
     * Double-Entry:
     * - DEBIT: Expenses (+₹5,000)
     * - CREDIT: Cash (-₹5,000)
     *
     * @param float $amount
     * @param int $withdrawalId
     * @return array
     */
    public function recordWithdrawal(
        float $amount,
        int $withdrawalId,
        ?string $description = null
    ): array {
        return $this->createDoubleEntry(
            debitAccount: self::ACCOUNT_EXPENSES,
            creditAccount: self::ACCOUNT_CASH,
            amount: $amount,
            referenceType: 'withdrawal',
            referenceId: $withdrawalId,
            description: $description ?? "Withdrawal approved"
        );
    }

    /**
     * FIX 24: Record Bonus Reversal
     *
     * When admin reverses a bonus that was previously paid:
     * - Money is taken back from user's wallet
     * - Admin gets cash back
     * - Previous expense is effectively reversed
     *
     * Double-Entry:
     * - DEBIT: Cash (+₹500) [admin gets money back]
     * - CREDIT: Expenses (-₹500) [reverses previous expense]
     *
     * @param float $amount Amount being reversed
     * @param int $bonusTransactionId ID of original bonus transaction
     * @param int $reversalTransactionId ID of reversal transaction
     * @return array [debit_entry, credit_entry]
     */
    public function recordBonusReversal(
        float $amount,
        int $bonusTransactionId,
        int $reversalTransactionId,
        ?string $description = null
    ): array {
        return $this->createDoubleEntry(
            debitAccount: self::ACCOUNT_CASH,
            creditAccount: self::ACCOUNT_EXPENSES,
            amount: $amount,
            referenceType: 'bonus_reversal',
            referenceId: $reversalTransactionId,
            description: $description ?? "Bonus reversal (original bonus #{$bonusTransactionId})"
        );
    }

    /**
     * PHASE 4.2 KILL SWITCH: This method is PERMANENTLY DISABLED.
     *
     * All write operations on AdminLedger have been killed.
     * Use DoubleEntryLedgerService for all new accounting operations.
     *
     * @throws \RuntimeException ALWAYS - legacy ledger writes are forbidden
     */
    private function createDoubleEntry(
        string $debitAccount,
        string $creditAccount,
        float $amount,
        string $referenceType,
        int $referenceId,
        string $description
    ): array {
        // =========================================================================
        // PHASE 4.2 KILL SWITCH - DO NOT REMOVE
        // =========================================================================
        // This legacy admin ledger has been replaced by DoubleEntryLedgerService.
        // Writing to this ledger would create DUAL FINANCIAL TRUTH, which is fatal.
        //
        // Use DoubleEntryLedgerService methods instead:
        // - recordPaymentReceived() -> recordUserDeposit()
        // - recordInventoryPurchase() -> recordInventoryPurchase()
        // - recordBonusPayout() -> recordBonusWithTds()
        // - recordWithdrawal() -> recordWithdrawal()
        // =========================================================================
        Log::critical('ADMIN LEDGER KILL SWITCH TRIGGERED', [
            'method' => 'createDoubleEntry',
            'debit_account' => $debitAccount,
            'credit_account' => $creditAccount,
            'amount' => $amount,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'description' => $description,
            'caller' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5),
        ]);

        throw new \RuntimeException(
            "LEGACY ADMIN LEDGER KILLED (Phase 4.2): AdminLedger::createDoubleEntry() is permanently disabled. " .
            "Use DoubleEntryLedgerService methods instead. " .
            "Attempted: {$debitAccount}/{$creditAccount} for ₹{$amount} ({$referenceType}#{$referenceId}). " .
            "This error is intentional and cannot be bypassed."
        );
    }

    /**
     * Get Account Balance (in paise)
     *
     * @param string $account
     * @param bool $lock Set true to lock for update (within transaction)
     * @return int Balance in paise
     */
    private function getAccountBalance(string $account, bool $lock = false): int
    {
        $query = AdminLedgerEntry::where('account', $account)
            ->orderBy('id', 'desc')
            ->limit(1);

        if ($lock) {
            $query->lockForUpdate();
        }

        $latestEntry = $query->first();

        return $latestEntry ? $latestEntry->balance_after_paise : 0;
    }

    /**
     * Calculate Admin Solvency
     *
     * PROTOCOL:
     * - Queries ledger (source of truth)
     * - Returns current balances for all accounts
     * - Verifies accounting equation balances
     *
     * @return array
     */
    public function calculateSolvency(): array
    {
        $cash = $this->getAccountBalance(self::ACCOUNT_CASH) / 100;
        $inventory = $this->getAccountBalance(self::ACCOUNT_INVENTORY) / 100;
        $liabilities = $this->getAccountBalance(self::ACCOUNT_LIABILITIES) / 100;
        $revenue = $this->getAccountBalance(self::ACCOUNT_REVENUE) / 100;
        $expenses = $this->getAccountBalance(self::ACCOUNT_EXPENSES) / 100;

        // Net position: what admin actually has minus what they owe
        $netPosition = $cash - $liabilities;

        // Total equity: revenue - expenses
        $equity = $revenue - $expenses;

        // Verification: Assets = Liabilities + Equity
        $assets = $cash + $inventory;
        $liabilitiesAndEquity = $liabilities + $equity;

        $balances = abs($assets - $liabilitiesAndEquity) < 0.01; // Allow 1 paisa rounding

        return [
            'cash' => $cash,
            'inventory' => $inventory,
            'liabilities' => $liabilities,
            'revenue' => $revenue,
            'expenses' => $expenses,
            'net_position' => $netPosition,
            'equity' => $equity,
            'assets' => $assets,
            'is_solvent' => $netPosition >= 0,
            'accounting_balances' => $balances,
            'discrepancy' => $balances ? 0 : ($assets - $liabilitiesAndEquity),
        ];
    }

    /**
     * Verify Sufficient Cash for Operation
     *
     * PROTOCOL:
     * - Called BEFORE withdrawal approval
     * - Ensures admin has liquid cash to pay out
     * - Prevents insolvency
     *
     * @param float $requiredAmount
     * @return bool
     */
    public function hasSufficientCash(float $requiredAmount): bool
    {
        $cashBalance = $this->getAccountBalance(self::ACCOUNT_CASH) / 100;

        return $cashBalance >= $requiredAmount;
    }

    /**
     * Get Ledger Entries for Audit Trail
     *
     * @param string|null $account Filter by account
     * @param string|null $referenceType Filter by reference type
     * @param int|null $referenceId Filter by reference ID
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getEntries(
        ?string $account = null,
        ?string $referenceType = null,
        ?int $referenceId = null
    ) {
        $query = AdminLedgerEntry::query()->orderBy('id', 'desc');

        if ($account) {
            $query->where('account', $account);
        }

        if ($referenceType) {
            $query->where('reference_type', $referenceType);
        }

        if ($referenceId) {
            $query->where('reference_id', $referenceId);
        }

        return $query->get();
    }

    // =========================================================================
    // P0 FIX: PLATFORM BALANCE & RECONCILIATION METHODS
    // =========================================================================

    /**
     * P0 FIX: Get Platform Cash Balance
     *
     * Returns the current platform cash position calculated from ledger.
     * This is the AUTHORITATIVE source for "how much cash does platform have?"
     *
     * @return array{balance_paise: int, balance_rupees: float, entry_count: int}
     */
    public function getPlatformCashBalance(): array
    {
        $balancePaise = $this->getAccountBalance(self::ACCOUNT_CASH);
        $entryCount = AdminLedgerEntry::where('account', self::ACCOUNT_CASH)->count();

        return [
            'balance_paise' => $balancePaise,
            'balance_rupees' => $balancePaise / 100,
            'entry_count' => $entryCount,
            'account' => self::ACCOUNT_CASH,
        ];
    }

    /**
     * P0 FIX: Get Platform Inventory Balance
     *
     * Returns the current platform inventory position calculated from ledger.
     *
     * @return array{balance_paise: int, balance_rupees: float, entry_count: int}
     */
    public function getPlatformInventoryBalance(): array
    {
        $balancePaise = $this->getAccountBalance(self::ACCOUNT_INVENTORY);
        $entryCount = AdminLedgerEntry::where('account', self::ACCOUNT_INVENTORY)->count();

        return [
            'balance_paise' => $balancePaise,
            'balance_rupees' => $balancePaise / 100,
            'entry_count' => $entryCount,
            'account' => self::ACCOUNT_INVENTORY,
        ];
    }

    /**
     * P0 FIX: Get Total Cash Received from Share Sales
     *
     * Sums all cash received from subscriber investments (share sales).
     * Used for reconciliation against shares allocated.
     *
     * @return array{total_paise: int, total_rupees: float, sale_count: int}
     */
    public function getTotalShareSaleRevenue(): array
    {
        $entries = AdminLedgerEntry::where('account', self::ACCOUNT_CASH)
            ->where('type', 'debit')
            ->where('reference_type', 'company_investment')
            ->get();

        $totalPaise = $entries->sum('amount_paise');
        $saleCount = $entries->count();

        return [
            'total_paise' => $totalPaise,
            'total_rupees' => $totalPaise / 100,
            'sale_count' => $saleCount,
        ];
    }

    /**
     * P0 FIX: Cash-Share Reconciliation
     *
     * CRITICAL: This method proves the platform received money for shares sold.
     *
     * Reconciliation Logic:
     * 1. Sum all cash received from share sales (AdminLedger)
     * 2. Sum all shares allocated to subscribers (UserInvestment/CompanyInvestment)
     * 3. Compare: cash_received SHOULD EQUAL shares_allocated_value
     *
     * @return array{
     *   is_reconciled: bool,
     *   cash_received_paise: int,
     *   shares_allocated_paise: int,
     *   discrepancy_paise: int,
     *   discrepancy_rupees: float,
     *   details: array
     * }
     */
    public function reconcileCashToShares(): array
    {
        // 1. Get cash received from share sales (from ledger)
        $cashFromSales = $this->getTotalShareSaleRevenue();

        // 2. Get total share allocation value (from CompanyInvestment table)
        // Note: This queries the investment table directly
        $allocatedValue = DB::table('company_investments')
            ->whereIn('status', ['active', 'completed'])
            ->sum('amount');

        $allocatedValuePaise = (int) round($allocatedValue * 100);

        // 3. Calculate discrepancy
        $discrepancyPaise = $cashFromSales['total_paise'] - $allocatedValuePaise;

        // Allow 1 rupee tolerance for rounding
        $isReconciled = abs($discrepancyPaise) <= 100;

        return [
            'is_reconciled' => $isReconciled,
            'cash_received_paise' => $cashFromSales['total_paise'],
            'cash_received_rupees' => $cashFromSales['total_rupees'],
            'shares_allocated_paise' => $allocatedValuePaise,
            'shares_allocated_rupees' => $allocatedValuePaise / 100,
            'discrepancy_paise' => $discrepancyPaise,
            'discrepancy_rupees' => $discrepancyPaise / 100,
            'sale_count' => $cashFromSales['sale_count'],
            'investment_count' => DB::table('company_investments')
                ->whereIn('status', ['active', 'completed'])
                ->count(),
            'reconciled_at' => now()->toIso8601String(),
        ];
    }

    /**
     * P0 FIX: Inventory Conservation Check
     *
     * Verifies: inventory_purchased == inventory_remaining + inventory_sold
     *
     * @return array{
     *   is_conserved: bool,
     *   purchased_paise: int,
     *   remaining_paise: int,
     *   sold_paise: int,
     *   discrepancy_paise: int
     * }
     */
    public function verifyInventoryConservation(): array
    {
        // Get total inventory purchased (from ledger - debits to inventory account)
        $purchasedEntries = AdminLedgerEntry::where('account', self::ACCOUNT_INVENTORY)
            ->where('type', 'debit')
            ->where('reference_type', 'bulk_purchase')
            ->sum('amount_paise');

        // Get total inventory sold (from ledger - credits to inventory account for sales)
        $soldEntries = AdminLedgerEntry::where('account', self::ACCOUNT_INVENTORY)
            ->where('type', 'credit')
            ->where('reference_type', 'company_investment')
            ->sum('amount_paise');

        // Get remaining inventory (current inventory balance)
        $remainingBalance = $this->getAccountBalance(self::ACCOUNT_INVENTORY);

        // Conservation check: purchased = sold + remaining
        // Since remaining = purchased - sold (by ledger math), this should always balance
        $expectedRemaining = $purchasedEntries - $soldEntries;
        $discrepancy = $remainingBalance - $expectedRemaining;

        return [
            'is_conserved' => abs($discrepancy) <= 100, // 1 rupee tolerance
            'purchased_paise' => $purchasedEntries,
            'purchased_rupees' => $purchasedEntries / 100,
            'sold_paise' => $soldEntries,
            'sold_rupees' => $soldEntries / 100,
            'remaining_paise' => $remainingBalance,
            'remaining_rupees' => $remainingBalance / 100,
            'expected_remaining_paise' => $expectedRemaining,
            'discrepancy_paise' => $discrepancy,
            'discrepancy_rupees' => $discrepancy / 100,
            'verified_at' => now()->toIso8601String(),
        ];
    }

    /**
     * P0 FIX: Get Share Sale Entry for Investment
     *
     * Retrieves the ledger entry proving cash was received for a specific investment.
     * Used for dispute resolution and audit.
     *
     * @param int $investmentId
     * @return AdminLedgerEntry|null
     */
    public function getShareSaleEntryForInvestment(int $investmentId): ?AdminLedgerEntry
    {
        return AdminLedgerEntry::where('account', self::ACCOUNT_CASH)
            ->where('type', 'debit')
            ->where('reference_type', 'company_investment')
            ->where('reference_id', $investmentId)
            ->first();
    }

    /**
     * P0 FIX: Platform Financial Summary
     *
     * Comprehensive view of platform's financial position for admin dashboard.
     *
     * @return array
     */
    public function getPlatformFinancialSummary(): array
    {
        $solvency = $this->calculateSolvency();
        $cashBalance = $this->getPlatformCashBalance();
        $inventoryBalance = $this->getPlatformInventoryBalance();
        $shareSaleRevenue = $this->getTotalShareSaleRevenue();
        $reconciliation = $this->reconcileCashToShares();
        $conservation = $this->verifyInventoryConservation();

        return [
            'generated_at' => now()->toIso8601String(),
            'balances' => [
                'cash' => $cashBalance,
                'inventory' => $inventoryBalance,
                'liabilities' => $solvency['liabilities'],
                'revenue' => $solvency['revenue'],
                'expenses' => $solvency['expenses'],
            ],
            'solvency' => [
                'is_solvent' => $solvency['is_solvent'],
                'net_position' => $solvency['net_position'],
                'equity' => $solvency['equity'],
                'accounting_balances' => $solvency['accounting_balances'],
            ],
            'share_sales' => [
                'total_revenue' => $shareSaleRevenue['total_rupees'],
                'sale_count' => $shareSaleRevenue['sale_count'],
            ],
            'reconciliation' => [
                'cash_share_reconciled' => $reconciliation['is_reconciled'],
                'discrepancy' => $reconciliation['discrepancy_rupees'],
            ],
            'conservation' => [
                'inventory_conserved' => $conservation['is_conserved'],
                'discrepancy' => $conservation['discrepancy_rupees'],
            ],
            'audit_status' => $reconciliation['is_reconciled'] && $conservation['is_conserved']
                ? 'PASS'
                : 'REQUIRES_INVESTIGATION',
        ];
    }
}
