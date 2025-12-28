<?php

namespace App\Services\Accounting;

use App\Models\AdminLedgerEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
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
     * Create Double-Entry Transaction (ATOMIC)
     *
     * PROTOCOL:
     * - Both entries created in single DB transaction
     * - Balances calculated automatically
     * - Entries are immutable after creation
     *
     * @return array [debit_entry, credit_entry]
     */
    private function createDoubleEntry(
        string $debitAccount,
        string $creditAccount,
        float $amount,
        string $referenceType,
        int $referenceId,
        string $description
    ): array {
        return DB::transaction(function () use (
            $debitAccount,
            $creditAccount,
            $amount,
            $referenceType,
            $referenceId,
            $description
        ) {
            // Convert to paise (integer math)
            $amountPaise = (int) round($amount * 100);

            // Get current balances (locking to prevent race conditions)
            $debitBalance = $this->getAccountBalance($debitAccount, true); // true = lock
            $creditBalance = $this->getAccountBalance($creditAccount, true);

            // Create debit entry
            $debitEntry = AdminLedgerEntry::create([
                'account' => $debitAccount,
                'type' => 'debit',
                'amount_paise' => $amountPaise,
                'balance_before_paise' => $debitBalance,
                'balance_after_paise' => $debitBalance + $amountPaise,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'description' => $description,
                'entry_pair_id' => null, // Will be set after credit entry created
            ]);

            // Create credit entry
            $creditEntry = AdminLedgerEntry::create([
                'account' => $creditAccount,
                'type' => 'credit',
                'amount_paise' => $amountPaise,
                'balance_before_paise' => $creditBalance,
                'balance_after_paise' => $creditBalance - $amountPaise,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'description' => $description,
                'entry_pair_id' => $debitEntry->id,
            ]);

            // Link debit entry to credit entry
            $debitEntry->update(['entry_pair_id' => $creditEntry->id]);

            Log::info("ADMIN_LEDGER: Double-entry created", [
                'debit' => "{$debitAccount} +₹{$amount}",
                'credit' => "{$creditAccount} -₹{$amount}",
                'reference' => "{$referenceType}#{$referenceId}",
            ]);

            return [$debitEntry, $creditEntry];
        });
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
}
