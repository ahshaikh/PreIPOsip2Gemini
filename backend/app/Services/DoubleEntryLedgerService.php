<?php

namespace App\Services;

use App\Models\LedgerAccount;
use App\Models\LedgerEntry;
use App\Models\LedgerLine;
use App\Models\BulkPurchase;
use App\Models\User;
use App\Models\Payment;
use App\Models\UserInvestment;
use App\Models\BonusTransaction;
use App\Models\Withdrawal;
use Illuminate\Support\Facades\DB;

/**
 * DOUBLE-ENTRY LEDGER SERVICE
 *
 * This service implements a true double-entry accounting system that:
 * - Enforces SUM(debits) == SUM(credits) for every entry
 * - Mirrors the platform's real bank account
 * - Tracks all assets, liabilities, equity, income, and expenses
 * - Provides immutable, audit-grade financial records
 *
 * INVARIANT:
 * Every method that creates ledger entries MUST ensure debits = credits.
 * If balance fails, the entire transaction must be rolled back.
 *
 * ACCOUNTING MODEL (Phase 4.1 - Expense-Based Inventory):
 * - Inventory purchase cost is EXPENSED IMMEDIATELY (not held as asset)
 * - Inventory quantity is tracked OPERATIONALLY via BulkPurchase, not financially
 * - On bulk purchase: DEBIT COST_OF_SHARES, CREDIT BANK
 * - On share sale: DEBIT USER_WALLET_LIABILITY, CREDIT SHARE_SALE_INCOME (margin)
 * - Margin = User Price - Proportional Cost Basis (discount margin is income)
 *
 * USAGE:
 * This service should be called within DB::transaction() blocks from controllers.
 * It does NOT wrap its own transactions to allow composition with other operations.
 *
 * @see App\Models\LedgerAccount
 * @see App\Models\LedgerEntry
 * @see App\Models\LedgerLine
 */
class DoubleEntryLedgerService
{
    /**
     * Account cache for performance
     */
    private array $accountCache = [];

    // =========================================================================
    // INVENTORY OPERATIONS
    // =========================================================================

    /**
     * Record inventory purchase (bulk purchase) - EXPENSE-BASED MODEL.
     *
     * PHASE 4.1 ACCOUNTING:
     *   DEBIT  COST_OF_SHARES  (immediate expense recognition)
     *   CREDIT BANK            (decrease asset - cash paid out)
     *
     * RATIONALE:
     * - Inventory is no longer a balance-sheet asset
     * - Cost is recognized as expense immediately upon purchase
     * - Inventory quantity is tracked OPERATIONALLY via bulk_purchases table
     * - This is more conservative (expenses recognized upfront)
     *
     * @param BulkPurchase $bulkPurchase The bulk purchase record
     * @param float $amount Total amount paid (in rupees)
     * @param int|null $adminId Admin who initiated the purchase
     * @return LedgerEntry The created journal entry
     * @throws \RuntimeException if entry is unbalanced
     */
    public function recordInventoryPurchase(
        BulkPurchase $bulkPurchase,
        float $amount,
        ?int $adminId = null
    ): LedgerEntry {
        $this->validatePositiveAmount($amount);

        $entry = $this->createEntry(
            LedgerEntry::REF_BULK_PURCHASE,
            $bulkPurchase->id,
            "Inventory purchase (expensed): {$bulkPurchase->product->name ?? 'Product'} - ₹" . number_format($amount, 2),
            $adminId
        );

        // DEBIT: Recognize cost as immediate expense
        $this->addLine($entry, LedgerAccount::CODE_COST_OF_SHARES, 'DEBIT', $amount);

        // CREDIT: Decrease Bank (cash paid out)
        $this->addLine($entry, LedgerAccount::CODE_BANK, 'CREDIT', $amount);

        $this->validateBalanced($entry);

        return $entry;
    }

    /**
     * Reverse an inventory purchase (bulk purchase reversal) - EXPENSE-BASED MODEL.
     *
     * PHASE 4.1 ACCOUNTING (opposite of purchase):
     *   DEBIT  BANK            (increase asset - cash returned)
     *   CREDIT COST_OF_SHARES  (decrease expense - cost reversed)
     *
     * NOTE: This should only be used for complete purchase reversals before
     * any inventory has been allocated. Partial reversals are not supported.
     *
     * @param BulkPurchase $bulkPurchase The bulk purchase being reversed
     * @param LedgerEntry $originalEntry The original purchase entry
     * @param float $amount Amount being reversed
     * @param int|null $adminId Admin who initiated the reversal
     * @return LedgerEntry The reversal entry
     */
    public function recordInventoryPurchaseReversal(
        BulkPurchase $bulkPurchase,
        LedgerEntry $originalEntry,
        float $amount,
        ?int $adminId = null
    ): LedgerEntry {
        if (!$originalEntry->canBeReversed()) {
            throw new \RuntimeException(
                "Entry #{$originalEntry->id} cannot be reversed (already reversed or is a reversal)"
            );
        }

        $this->validatePositiveAmount($amount);

        $entry = LedgerEntry::create([
            'reference_type' => LedgerEntry::REF_BULK_PURCHASE_REVERSAL,
            'reference_id' => $bulkPurchase->id,
            'description' => "Reversal of inventory purchase #" . $originalEntry->id,
            'entry_date' => now()->toDateString(),
            'created_by' => $adminId,
            'is_reversal' => true,
            'reverses_entry_id' => $originalEntry->id,
        ]);

        // DEBIT: Increase Bank (cash returned)
        $this->addLine($entry, LedgerAccount::CODE_BANK, 'DEBIT', $amount);

        // CREDIT: Decrease expense (cost reversed)
        $this->addLine($entry, LedgerAccount::CODE_COST_OF_SHARES, 'CREDIT', $amount);

        $this->validateBalanced($entry);

        return $entry;
    }

    // =========================================================================
    // USER WALLET OPERATIONS
    // =========================================================================

    /**
     * Record user deposit (funds added to wallet).
     *
     * ACCOUNTING:
     *   DEBIT  BANK                    (increase asset - we received cash)
     *   CREDIT USER_WALLET_LIABILITY   (increase liability - we owe user)
     *
     * @param User $user The user making the deposit
     * @param Payment|int $payment Payment record or ID
     * @param float $amount Amount deposited (in rupees)
     * @return LedgerEntry
     */
    public function recordUserDeposit(
        User $user,
        $payment,
        float $amount
    ): LedgerEntry {
        $this->validatePositiveAmount($amount);

        $paymentId = $payment instanceof Payment ? $payment->id : $payment;

        $entry = $this->createEntry(
            LedgerEntry::REF_USER_DEPOSIT,
            $paymentId,
            "User deposit: {$user->name} - ₹" . number_format($amount, 2)
        );

        // DEBIT: Increase Bank (asset)
        $this->addLine($entry, LedgerAccount::CODE_BANK, 'DEBIT', $amount);

        // CREDIT: Increase User Wallet Liability
        $this->addLine($entry, LedgerAccount::CODE_USER_WALLET_LIABILITY, 'CREDIT', $amount);

        $this->validateBalanced($entry);

        return $entry;
    }

    /**
     * Record user investment / share sale - EXPENSE-BASED MODEL.
     *
     * PHASE 4.1 ACCOUNTING:
     *   DEBIT  USER_WALLET_LIABILITY   (decrease liability - user used funds)
     *   CREDIT SHARE_SALE_INCOME       (recognize full payment as income)
     *
     * MARGIN COMPUTATION:
     * Since inventory cost was EXPENSED at bulk purchase (to COST_OF_SHARES),
     * the platform margin is automatically computed in P&L as:
     *   Margin = SHARE_SALE_INCOME balance - COST_OF_SHARES balance
     *
     * This approach maintains double-entry integrity while correctly recognizing
     * that shares are sold at a markup over cost basis (12-15% discount captured).
     *
     * @param UserInvestment $investment The investment record
     * @param float $amount Amount paid by user (in rupees)
     * @return LedgerEntry
     */
    public function recordUserInvestment(
        UserInvestment $investment,
        float $amount
    ): LedgerEntry {
        $this->validatePositiveAmount($amount);

        $entry = $this->createEntry(
            LedgerEntry::REF_USER_INVESTMENT,
            $investment->id,
            "Share sale: ₹" . number_format($amount, 2) . " for " . ($investment->product->name ?? 'shares')
        );

        // DEBIT: Decrease User Wallet Liability (user used their funds)
        $this->addLine($entry, LedgerAccount::CODE_USER_WALLET_LIABILITY, 'DEBIT', $amount);

        // CREDIT: Recognize share sale income (full amount)
        // Margin = SHARE_SALE_INCOME - COST_OF_SHARES (computed in reports)
        $this->addLine($entry, LedgerAccount::CODE_SHARE_SALE_INCOME, 'CREDIT', $amount);

        $this->validateBalanced($entry);

        return $entry;
    }

    /**
     * @deprecated PHASE 4.1: This method is NO LONGER USED in the expense-based model.
     *
     * In the previous asset-based model, this tracked inventory reduction at allocation.
     * In the expense-based model (Phase 4.1):
     * - Cost is expensed IMMEDIATELY at bulk purchase (DEBIT COST_OF_SHARES)
     * - Inventory is tracked OPERATIONALLY via bulk_purchases table, not in ledger
     * - No separate allocation entry is needed
     *
     * If you need to record a share allocation, use recordUserInvestment() which
     * recognizes the user payment as income. The margin is computed in reports as:
     *   Margin = SHARE_SALE_INCOME - COST_OF_SHARES
     *
     * @throws \RuntimeException Always throws - method is deprecated
     */
    public function recordShareAllocation(
        UserInvestment $investment,
        float $costBasis
    ): LedgerEntry {
        throw new \RuntimeException(
            'recordShareAllocation() is deprecated in Phase 4.1 expense-based model. ' .
            'Use recordUserInvestment() instead. Cost is already expensed at bulk purchase time.'
        );
    }

    /**
     * Record bonus credit to user.
     *
     * ACCOUNTING:
     *   DEBIT  MARKETING_EXPENSE    (increase expense)
     *   CREDIT BONUS_LIABILITY      (increase liability - we owe user bonus)
     *
     * @param BonusTransaction $bonus The bonus transaction
     * @param float $amount Bonus amount
     * @return LedgerEntry
     */
    public function recordBonusCredit(
        BonusTransaction $bonus,
        float $amount
    ): LedgerEntry {
        $this->validatePositiveAmount($amount);

        $entry = $this->createEntry(
            LedgerEntry::REF_BONUS_CREDIT,
            $bonus->id,
            "Bonus credit: {$bonus->type} - ₹" . number_format($amount, 2)
        );

        // DEBIT: Marketing expense
        $this->addLine($entry, LedgerAccount::CODE_MARKETING_EXPENSE, 'DEBIT', $amount);

        // CREDIT: Bonus liability (we owe this to user)
        $this->addLine($entry, LedgerAccount::CODE_BONUS_LIABILITY, 'CREDIT', $amount);

        $this->validateBalanced($entry);

        return $entry;
    }

    /**
     * Record bonus conversion to wallet (when user receives bonus as cash).
     *
     * ACCOUNTING:
     *   DEBIT  BONUS_LIABILITY         (decrease liability - bonus paid out)
     *   CREDIT USER_WALLET_LIABILITY   (increase liability - now in wallet)
     *
     * @param User $user The user receiving bonus
     * @param int $referenceId Reference ID (bonus transaction or similar)
     * @param float $amount Amount transferred
     * @return LedgerEntry
     */
    public function recordBonusToWallet(
        User $user,
        int $referenceId,
        float $amount
    ): LedgerEntry {
        $this->validatePositiveAmount($amount);

        $entry = $this->createEntry(
            LedgerEntry::REF_BONUS_CREDIT,
            $referenceId,
            "Bonus to wallet: {$user->name} - ₹" . number_format($amount, 2)
        );

        // DEBIT: Decrease bonus liability
        $this->addLine($entry, LedgerAccount::CODE_BONUS_LIABILITY, 'DEBIT', $amount);

        // CREDIT: Increase wallet liability
        $this->addLine($entry, LedgerAccount::CODE_USER_WALLET_LIABILITY, 'CREDIT', $amount);

        $this->validateBalanced($entry);

        return $entry;
    }

    /**
     * Record user withdrawal.
     *
     * ACCOUNTING:
     *   DEBIT  USER_WALLET_LIABILITY   (decrease liability - user took funds)
     *   CREDIT BANK                    (decrease asset - cash paid out)
     *
     * @param Withdrawal $withdrawal The withdrawal record
     * @param float $amount Amount withdrawn (in rupees)
     * @return LedgerEntry
     */
    public function recordWithdrawal(
        Withdrawal $withdrawal,
        float $amount
    ): LedgerEntry {
        $this->validatePositiveAmount($amount);

        $entry = $this->createEntry(
            LedgerEntry::REF_WITHDRAWAL,
            $withdrawal->id,
            "Withdrawal: ₹" . number_format($amount, 2)
        );

        // DEBIT: Decrease User Wallet Liability (we owe less)
        $this->addLine($entry, LedgerAccount::CODE_USER_WALLET_LIABILITY, 'DEBIT', $amount);

        // CREDIT: Decrease Bank (cash paid out)
        $this->addLine($entry, LedgerAccount::CODE_BANK, 'CREDIT', $amount);

        $this->validateBalanced($entry);

        return $entry;
    }

    /**
     * Record TDS deduction.
     *
     * ACCOUNTING:
     *   DEBIT  USER_WALLET_LIABILITY   (decrease amount owed to user)
     *   CREDIT TDS_PAYABLE             (increase liability to government)
     *
     * @param int $referenceId Related payment/withdrawal ID
     * @param float $amount TDS amount
     * @return LedgerEntry
     */
    public function recordTdsDeduction(
        int $referenceId,
        float $amount
    ): LedgerEntry {
        $this->validatePositiveAmount($amount);

        $entry = $this->createEntry(
            LedgerEntry::REF_TDS_DEDUCTION,
            $referenceId,
            "TDS deduction: ₹" . number_format($amount, 2)
        );

        // DEBIT: Decrease amount owed to user
        $this->addLine($entry, LedgerAccount::CODE_USER_WALLET_LIABILITY, 'DEBIT', $amount);

        // CREDIT: Increase TDS payable (we owe government)
        $this->addLine($entry, LedgerAccount::CODE_TDS_PAYABLE, 'CREDIT', $amount);

        $this->validateBalanced($entry);

        return $entry;
    }

    /**
     * Record refund to user - EXPENSE-BASED MODEL.
     *
     * PHASE 4.1 ACCOUNTING:
     *   DEBIT  SHARE_SALE_INCOME       (decrease revenue - refunded)
     *   CREDIT USER_WALLET_LIABILITY   (increase what we owe user)
     *
     * NOTE: Refunds reduce income recognition. The original cost (if any)
     * remains expensed - this is conservative accounting. If inventory needs
     * to be "returned" to available stock, that's handled operationally in
     * the bulk_purchases table, not in the ledger.
     *
     * @param int $referenceId Payment or investment ID being refunded
     * @param float $amount Refund amount
     * @return LedgerEntry
     */
    public function recordRefund(
        int $referenceId,
        float $amount
    ): LedgerEntry {
        $this->validatePositiveAmount($amount);

        $entry = $this->createEntry(
            LedgerEntry::REF_REFUND,
            $referenceId,
            "Refund: ₹" . number_format($amount, 2)
        );

        // DEBIT: Decrease share sale income (refund reverses revenue)
        $this->addLine($entry, LedgerAccount::CODE_SHARE_SALE_INCOME, 'DEBIT', $amount);

        // CREDIT: Increase what we owe user
        $this->addLine($entry, LedgerAccount::CODE_USER_WALLET_LIABILITY, 'CREDIT', $amount);

        $this->validateBalanced($entry);

        return $entry;
    }

    // =========================================================================
    // CAPITAL OPERATIONS
    // =========================================================================

    /**
     * Record capital injection from owner.
     *
     * ACCOUNTING:
     *   DEBIT  BANK           (increase asset - received cash)
     *   CREDIT OWNER_CAPITAL  (increase equity - owner's investment)
     *
     * @param int $referenceId Reference document ID
     * @param float $amount Amount injected
     * @param int|null $adminId Admin recording this
     * @return LedgerEntry
     */
    public function recordCapitalInjection(
        int $referenceId,
        float $amount,
        ?int $adminId = null
    ): LedgerEntry {
        $this->validatePositiveAmount($amount);

        $entry = $this->createEntry(
            LedgerEntry::REF_CAPITAL_INJECTION,
            $referenceId,
            "Capital injection: ₹" . number_format($amount, 2),
            $adminId
        );

        // DEBIT: Increase Bank
        $this->addLine($entry, LedgerAccount::CODE_BANK, 'DEBIT', $amount);

        // CREDIT: Increase Owner Capital
        $this->addLine($entry, LedgerAccount::CODE_OWNER_CAPITAL, 'CREDIT', $amount);

        $this->validateBalanced($entry);

        return $entry;
    }

    /**
     * Record payment gateway fees.
     *
     * ACCOUNTING:
     *   DEBIT  PAYMENT_GATEWAY_FEES  (increase expense)
     *   CREDIT BANK                   (decrease asset - fee deducted)
     *
     * @param int $paymentId Related payment ID
     * @param float $feeAmount Fee amount
     * @return LedgerEntry
     */
    public function recordGatewayFee(
        int $paymentId,
        float $feeAmount
    ): LedgerEntry {
        $this->validatePositiveAmount($feeAmount);

        $entry = $this->createEntry(
            LedgerEntry::REF_GATEWAY_FEE,
            $paymentId,
            "Gateway fee: ₹" . number_format($feeAmount, 2)
        );

        // DEBIT: Increase expense
        $this->addLine($entry, LedgerAccount::CODE_PAYMENT_GATEWAY_FEES, 'DEBIT', $feeAmount);

        // CREDIT: Decrease bank (fee deducted)
        $this->addLine($entry, LedgerAccount::CODE_BANK, 'CREDIT', $feeAmount);

        $this->validateBalanced($entry);

        return $entry;
    }

    // =========================================================================
    // REPORTING METHODS
    // =========================================================================

    /**
     * Get balance of a specific account.
     *
     * @param string $accountCode Account code (e.g., 'BANK', 'INVENTORY')
     * @return float Current balance
     */
    public function getAccountBalance(string $accountCode): float
    {
        return $this->getAccount($accountCode)->balance;
    }

    /**
     * Calculate share sale margin (Phase 4.1).
     *
     * MARGIN = SHARE_SALE_INCOME - COST_OF_SHARES
     *
     * This represents the platform's gross profit from the discount model:
     * - Platform acquires shares at 12-15% discount
     * - Users pay full price (or close to it)
     * - Difference is margin
     *
     * @return array Margin breakdown with income, cost, and margin amounts
     */
    public function getShareSaleMargin(): array
    {
        $shareSaleIncome = $this->getAccountBalance(LedgerAccount::CODE_SHARE_SALE_INCOME);
        $costOfShares = $this->getAccountBalance(LedgerAccount::CODE_COST_OF_SHARES);

        // Note: COST_OF_SHARES is an expense account (debit normal)
        // A positive balance means expenses incurred
        // SHARE_SALE_INCOME is income account (credit normal)
        // A positive balance means income earned

        $grossMargin = $shareSaleIncome - $costOfShares;
        $marginPercentage = $costOfShares > 0
            ? round(($grossMargin / $costOfShares) * 100, 2)
            : 0;

        return [
            'share_sale_income' => $shareSaleIncome,
            'cost_of_shares' => $costOfShares,
            'gross_margin' => $grossMargin,
            'margin_percentage' => $marginPercentage,
            'description' => 'Gross margin from share sales (discount model)',
        ];
    }

    /**
     * Get trial balance (all account balances).
     *
     * @return array Array of account balances
     */
    public function getTrialBalance(): array
    {
        $accounts = LedgerAccount::all();
        $result = [];

        foreach ($accounts as $account) {
            $result[] = [
                'code' => $account->code,
                'name' => $account->name,
                'type' => $account->type,
                'balance' => $account->balance,
                'total_debits' => $account->total_debits,
                'total_credits' => $account->total_credits,
            ];
        }

        return $result;
    }

    /**
     * Verify the fundamental accounting equation: Assets = Liabilities + Equity
     *
     * @return array Verification result with balances
     */
    public function verifyAccountingEquation(): array
    {
        $assets = LedgerAccount::ofType(LedgerAccount::TYPE_ASSET)
            ->get()
            ->sum(fn($a) => $a->balance);

        $liabilities = LedgerAccount::ofType(LedgerAccount::TYPE_LIABILITY)
            ->get()
            ->sum(fn($a) => $a->balance);

        $equity = LedgerAccount::ofType(LedgerAccount::TYPE_EQUITY)
            ->get()
            ->sum(fn($a) => $a->balance);

        // Include retained earnings (net income)
        $income = LedgerAccount::ofType(LedgerAccount::TYPE_INCOME)
            ->get()
            ->sum(fn($a) => $a->balance);

        $expenses = LedgerAccount::ofType(LedgerAccount::TYPE_EXPENSE)
            ->get()
            ->sum(fn($a) => $a->balance);

        $netIncome = $income - $expenses;
        $totalEquity = $equity + $netIncome;

        $leftSide = $assets;
        $rightSide = $liabilities + $totalEquity;
        $isBalanced = bccomp((string) $leftSide, (string) $rightSide, 2) === 0;

        return [
            'assets' => $assets,
            'liabilities' => $liabilities,
            'equity' => $equity,
            'income' => $income,
            'expenses' => $expenses,
            'net_income' => $netIncome,
            'total_equity' => $totalEquity,
            'left_side' => $leftSide,
            'right_side' => $rightSide,
            'difference' => abs($leftSide - $rightSide),
            'is_balanced' => $isBalanced,
        ];
    }

    // =========================================================================
    // PRIVATE HELPERS
    // =========================================================================

    /**
     * Create a new ledger entry.
     */
    private function createEntry(
        string $referenceType,
        int $referenceId,
        string $description,
        ?int $createdBy = null
    ): LedgerEntry {
        return LedgerEntry::create([
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'description' => $description,
            'entry_date' => now()->toDateString(),
            'created_by' => $createdBy,
            'is_reversal' => false,
        ]);
    }

    /**
     * Add a line to a ledger entry.
     */
    private function addLine(
        LedgerEntry $entry,
        string $accountCode,
        string $direction,
        float $amount
    ): LedgerLine {
        $account = $this->getAccount($accountCode);

        return LedgerLine::create([
            'ledger_entry_id' => $entry->id,
            'ledger_account_id' => $account->id,
            'direction' => $direction,
            'amount' => $amount,
        ]);
    }

    /**
     * Get account by code with caching.
     */
    private function getAccount(string $code): LedgerAccount
    {
        if (!isset($this->accountCache[$code])) {
            $this->accountCache[$code] = LedgerAccount::byCode($code);
        }

        return $this->accountCache[$code];
    }

    /**
     * Validate that an entry is balanced (debits = credits).
     *
     * @throws \RuntimeException if entry is not balanced
     */
    private function validateBalanced(LedgerEntry $entry): void
    {
        // Refresh to ensure we have all lines
        $entry->refresh();

        if (!$entry->isBalanced()) {
            $debits = $entry->total_debits;
            $credits = $entry->total_credits;

            throw new \RuntimeException(
                "DOUBLE-ENTRY VIOLATION: Entry #{$entry->id} is not balanced. " .
                "Debits: ₹{$debits}, Credits: ₹{$credits}, Difference: ₹" . abs($debits - $credits)
            );
        }
    }

    /**
     * Validate that amount is positive.
     *
     * @throws \RuntimeException if amount is not positive
     */
    private function validatePositiveAmount(float $amount): void
    {
        if ($amount <= 0) {
            throw new \RuntimeException(
                "Ledger amount must be positive. Got: ₹{$amount}"
            );
        }
    }
}
