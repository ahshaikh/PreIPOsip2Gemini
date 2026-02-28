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
 * ============================================================================
 * PHASE 4.2 HARDENING: COST RECOGNITION INVARIANT
 * ============================================================================
 *
 * CRITICAL RULE: Cost is recognized at PURCHASE TIME ONLY.
 *
 * - COST_OF_SHARES may ONLY be debited during bulk purchase
 * - Allocation must NEVER post financial entries
 * - Any attempt to debit COST_OF_SHARES outside bulk purchase flow throws exception
 *
 * WHY: Inventory is expensed immediately. Re-recognizing cost at allocation
 * would DOUBLE-COUNT the expense, corrupting P&L and making margin calculation
 * impossible. This invariant is STRUCTURALLY ENFORCED, not just documented.
 *
 * ============================================================================
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

    /**
     * PHASE 4.2: Flow context flag for COST_OF_SHARES guard.
     *
     * When true, COST_OF_SHARES debits are permitted.
     * This is ONLY set during recordInventoryPurchase().
     *
     * Any attempt to debit COST_OF_SHARES when this is false will throw.
     */
    private bool $inBulkPurchaseFlow = false;

    /**
     * PHASE 4 SECTION 7.2: Flow context flag for bonus usage.
     *
     * When true, COST_OF_SHARES credits are permitted.
     * This is ONLY set during recordBonusUsage().
     *
     * Bonus usage requires CREDIT to COST_OF_SHARES to offset the expense
     * (since the cost was covered by marketing expense, not cash payment).
     */
    private bool $inBonusUsageFlow = false;

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
     * PHASE 4.2 HARDENING:
     * This is the ONLY method that may debit COST_OF_SHARES.
     * The $inBulkPurchaseFlow flag enables this operation.
     * Any attempt to debit COST_OF_SHARES elsewhere will throw.
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

        // PHASE 4.2: Enable COST_OF_SHARES debit for this flow only
        $this->inBulkPurchaseFlow = true;

        try {
            $productName = $bulkPurchase->product->name ?? 'Product';
            $entry = $this->createEntry(
                LedgerEntry::REF_BULK_PURCHASE,
                $bulkPurchase->id,
                "Inventory purchase (expensed): {$productName} - ₹" . number_format($amount, 2),
                $adminId
            );

            // DEBIT: Recognize cost as immediate expense
            $this->addLine($entry, LedgerAccount::CODE_COST_OF_SHARES, 'DEBIT', $amount);

            // CREDIT: Decrease Bank (cash paid out)
            $this->addLine($entry, LedgerAccount::CODE_BANK, 'CREDIT', $amount);

            $this->validateBalanced($entry);

            return $entry;
        } finally {
            // PHASE 4.2: Always reset flag, even on exception
            $this->inBulkPurchaseFlow = false;
        }
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
     * PHASE 4.2 HARDENING:
     * This method may credit COST_OF_SHARES (reversing expense).
     * The $inBulkPurchaseFlow flag enables this operation.
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

        // PHASE 4.2: Enable COST_OF_SHARES credit for this flow only
        $this->inBulkPurchaseFlow = true;

        try {
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
        } finally {
            // PHASE 4.2: Always reset flag, even on exception
            $this->inBulkPurchaseFlow = false;
        }
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
     * PHASE 4 SECTION 7.2: Record share sale income from wallet.
     *
     * ACCOUNTING:
     *   DEBIT  USER_WALLET_LIABILITY  (decrease what we owe user - they spent funds)
     *   CREDIT SHARE_SALE_INCOME      (recognize revenue from share sale)
     *
     * This is the cash portion of a share purchase. The bonus portion is handled
     * separately by recordBonusUsage() which does NOT credit SHARE_SALE_INCOME.
     *
     * @param int $transactionId The wallet transaction ID
     * @param float $amount Cash amount used for share purchase (in rupees)
     * @return LedgerEntry
     */
    public function recordShareSaleFromWallet(
        int $transactionId,
        float $amount
    ): LedgerEntry {
        $this->validatePositiveAmount($amount);

        $entry = $this->createEntry(
            LedgerEntry::REF_USER_INVESTMENT,
            $transactionId,
            "Share sale from wallet: ₹" . number_format($amount, 2)
        );

        // DEBIT: Decrease User Wallet Liability (user used their funds)
        $this->addLine($entry, LedgerAccount::CODE_USER_WALLET_LIABILITY, 'DEBIT', $amount);

        // CREDIT: Recognize share sale income
        $this->addLine($entry, LedgerAccount::CODE_SHARE_SALE_INCOME, 'CREDIT', $amount);

        $this->validateBalanced($entry);

        return $entry;
    }

    // =========================================================================
    // PHASE 4.2: recordShareAllocation() HAS BEEN PERMANENTLY DELETED
    // =========================================================================
    //
    // DO NOT RE-IMPLEMENT THIS METHOD.
    //
    // In the expense-based model (Phase 4.1+):
    // - Cost is expensed IMMEDIATELY at bulk purchase (DEBIT COST_OF_SHARES)
    // - Inventory is tracked OPERATIONALLY via bulk_purchases table, not in ledger
    // - Allocation must NEVER post financial entries
    //
    // Any attempt to recognize cost at allocation time would DOUBLE-COUNT
    // the expense, corrupting the P&L. This is why the method was deleted,
    // not just deprecated.
    //
    // For share sales, use recordUserInvestment() which recognizes user
    // payment as income. Margin = SHARE_SALE_INCOME - COST_OF_SHARES.
    // =========================================================================

    // =========================================================================
    // BONUS + TDS OPERATIONS (PHASE 4.2 HARDENED)
    // =========================================================================
    //
    // PHASE 4.2 LEGAL SEPARATION:
    // Bonuses with TDS involve THREE distinct financial truths:
    // 1. Marketing Expense (GROSS) - what the platform spends
    // 2. User Entitlement (NET) - what the user actually receives
    // 3. Government Payable (TDS) - what we owe the tax authority
    //
    // These MUST be tracked separately to ensure:
    // - P&L correctly reflects gross marketing expense
    // - User receives only net amount (after TDS)
    // - TDS liability is explicit and traceable for remittance
    //
    // TDS must NEVER touch income accounts.
    // TDS must NEVER inflate or reduce platform profit.
    // =========================================================================

    /**
     * Record bonus grant with TDS - LEGALLY SEPARATED MODEL.
     *
     * PHASE 4.2 ACCOUNTING (three-way split):
     *   DEBIT  MARKETING_EXPENSE    (GROSS bonus - full platform expense)
     *   CREDIT USER_WALLET_LIABILITY (NET bonus - what user actually gets)
     *   CREDIT TDS_PAYABLE          (TDS amount - government liability)
     *
     * This ensures:
     * - Marketing expense reflects the true cost to the platform
     * - User wallet liability is exactly what they can withdraw
     * - TDS payable is an explicit government liability
     *
     * @param BonusTransaction $bonus The bonus transaction record
     * @param float $grossAmount Gross bonus amount (before TDS)
     * @param float $tdsAmount TDS amount deducted
     * @return LedgerEntry
     * @throws \RuntimeException if amounts don't reconcile
     */
    public function recordBonusWithTds(
        BonusTransaction $bonus,
        float $grossAmount,
        float $tdsAmount
    ): LedgerEntry {
        $this->validatePositiveAmount($grossAmount);

        $netAmount = $grossAmount - $tdsAmount;

        // INVARIANT: Gross = Net + TDS (must reconcile)
        if (abs($grossAmount - ($netAmount + $tdsAmount)) > 0.01) {
            throw new \RuntimeException(
                "BONUS TDS RECONCILIATION FAILED: Gross (₹{$grossAmount}) != Net (₹{$netAmount}) + TDS (₹{$tdsAmount})"
            );
        }

        $entry = $this->createEntry(
            LedgerEntry::REF_BONUS_CREDIT,
            $bonus->id,
            "Bonus with TDS: {$bonus->type} - Gross ₹" . number_format($grossAmount, 2) .
            ", TDS ₹" . number_format($tdsAmount, 2) .
            ", Net ₹" . number_format($netAmount, 2)
        );

        // DEBIT: Marketing expense (GROSS - full platform cost)
        $this->addLine($entry, LedgerAccount::CODE_MARKETING_EXPENSE, 'DEBIT', $grossAmount);

        // CREDIT: Bonus liability (NET - what user is entitled to)
        // PHASE 4 SECTION 7.2: Credit BONUS_LIABILITY instead of USER_WALLET_LIABILITY
        // This allows proper tracking of bonus usage when shares are purchased.
        // The transfer to USER_WALLET_LIABILITY happens in WalletService::deposit.
        if ($netAmount > 0) {
            $this->addLine($entry, LedgerAccount::CODE_BONUS_LIABILITY, 'CREDIT', $netAmount);
        }

        // CREDIT: TDS payable (government liability)
        if ($tdsAmount > 0) {
            $this->addLine($entry, LedgerAccount::CODE_TDS_PAYABLE, 'CREDIT', $tdsAmount);
        }

        $this->validateBalanced($entry);

        return $entry;
    }

    /**
     * Record TDS remittance to government.
     *
     * PHASE 4.2 ACCOUNTING:
     *   DEBIT  TDS_PAYABLE  (decrease government liability - we paid them)
     *   CREDIT BANK         (decrease asset - cash paid out)
     *
     * This method is called when TDS is actually remitted to the government.
     * It clears the TDS_PAYABLE liability.
     *
     * @param int $referenceId Reference document ID (e.g., TDS challan)
     * @param float $amount TDS amount being remitted
     * @param int|null $adminId Admin processing the remittance
     * @return LedgerEntry
     */
    public function recordTdsRemittance(
        int $referenceId,
        float $amount,
        ?int $adminId = null
    ): LedgerEntry {
        $this->validatePositiveAmount($amount);

        $entry = $this->createEntry(
            LedgerEntry::REF_TDS_REMITTANCE,
            $referenceId,
            "TDS remittance to government: ₹" . number_format($amount, 2),
            $adminId
        );

        // DEBIT: Decrease TDS payable (we paid the government)
        $this->addLine($entry, LedgerAccount::CODE_TDS_PAYABLE, 'DEBIT', $amount);

        // CREDIT: Decrease Bank (cash paid out)
        $this->addLine($entry, LedgerAccount::CODE_BANK, 'CREDIT', $amount);

        $this->validateBalanced($entry);

        return $entry;
    }

    /**
     * PHASE 4 SECTION 7.2: Record bonus usage (redemption for shares).
     *
     * ACCOUNTING:
     *   DEBIT  BONUS_LIABILITY   (decrease liability - user redeemed their bonus)
     *   CREDIT COST_OF_SHARES    (offset expense - cost covered by marketing expense)
     *
     * RATIONALE:
     * When a bonus is GRANTED, we record:
     *   DEBIT MARKETING_EXPENSE, CREDIT BONUS_LIABILITY (+ TDS_PAYABLE)
     *
     * When the bonus is USED to acquire shares:
     * - The liability to the user is fulfilled (they got their shares)
     * - The cost of those shares was already covered by MARKETING_EXPENSE
     * - We CREDIT COST_OF_SHARES to offset the expense that was recognized at bulk purchase
     *
     * This ensures:
     * - Shares sold for CASH: Cost stays in COST_OF_SHARES (offset by SHARE_SALE_INCOME)
     * - Shares given as BONUS: Cost is credited out of COST_OF_SHARES (offset by MARKETING_EXPENSE)
     *
     * APPLIES TO ALL 7 BONUS TYPES:
     * - progressive, milestone, referral, celebration, birthday, anniversary, lucky_draw
     *
     * @param int $userInvestmentId The UserInvestment record ID
     * @param float $amount Cost of shares allocated via bonus (in rupees)
     * @param string $bonusType Type of bonus being used
     * @param int|null $bonusTransactionId Optional link to original bonus transaction
     * @return LedgerEntry
     */
    public function recordBonusUsage(
        int $userInvestmentId,
        float $amount,
        string $bonusType,
        ?int $bonusTransactionId = null
    ): LedgerEntry {
        $this->validatePositiveAmount($amount);

        // PHASE 4 SECTION 7.2: Enable COST_OF_SHARES credit for bonus usage
        $this->inBonusUsageFlow = true;

        try {
            $description = "Bonus usage: {$bonusType} - ₹" . number_format($amount, 2);
            if ($bonusTransactionId) {
                $description .= " (bonus #{$bonusTransactionId})";
            }

            $entry = $this->createEntry(
                LedgerEntry::REF_BONUS_USAGE,
                $userInvestmentId,
                $description
            );

            // DEBIT: Decrease User Wallet Liability (user spent their bonus which was in wallet)
            // PHASE 4 FIX: Spendable bonuses are credited to USER_WALLET_LIABILITY via recordBonusToWallet()
            // during WalletService::deposit(). Redeeming them must reduce the wallet liability.
            $this->addLine($entry, LedgerAccount::CODE_USER_WALLET_LIABILITY, 'DEBIT', $amount);

            // CREDIT: Offset cost of shares (cost was covered by marketing expense at grant time)
            $this->addLine($entry, LedgerAccount::CODE_COST_OF_SHARES, 'CREDIT', $amount);

            $this->validateBalanced($entry);

            return $entry;
        } finally {
            // PHASE 4 SECTION 7.2: Always reset flag, even on exception
            $this->inBonusUsageFlow = false;
        }
    }

    /**
     * PHASE 4 SECTION 7.2: Transfer bonus from BONUS_LIABILITY to USER_WALLET_LIABILITY.
     *
     * STEP 7.2 ACCOUNTING:
     *   DEBIT  BONUS_LIABILITY        (decrease bonus liability - bonus is being credited to wallet)
     *   CREDIT USER_WALLET_LIABILITY  (increase wallet liability - we now owe user via wallet)
     *
     * This entry is made when bonus is credited to the user's wallet.
     * At this point:
     * - recordBonusWithTds() has already credited BONUS_LIABILITY
     * - Now we transfer from BONUS_LIABILITY to USER_WALLET_LIABILITY
     * - User's wallet balance goes up
     *
     * @param int $transactionId The wallet transaction ID
     * @param float $amount Net bonus amount (after TDS) in rupees
     * @param string $bonusType Type of bonus for description
     * @return LedgerEntry
     */
    public function recordBonusToWallet(
        int $transactionId,
        float $amount,
        string $bonusType
    ): LedgerEntry {
        $this->validatePositiveAmount($amount);

        $entry = $this->createEntry(
            LedgerEntry::REF_BONUS_CREDIT,
            $transactionId,
            "Bonus to wallet: {$bonusType} - ₹" . number_format($amount, 2)
        );

        // DEBIT: Decrease bonus liability (bonus transferred to wallet)
        $this->addLine($entry, LedgerAccount::CODE_BONUS_LIABILITY, 'DEBIT', $amount);

        // CREDIT: Increase wallet liability (user can now spend from wallet)
        $this->addLine($entry, LedgerAccount::CODE_USER_WALLET_LIABILITY, 'CREDIT', $amount);

        $this->validateBalanced($entry);

        return $entry;
    }

    /**
     * @deprecated PHASE 4.2: Use recordBonusWithTds() instead for proper TDS separation.
     *
     * Record bonus credit to user (legacy method without TDS separation).
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
            "Bonus credit (legacy): {$bonus->type} - ₹" . number_format($amount, 2)
        );

        // DEBIT: Marketing expense
        $this->addLine($entry, LedgerAccount::CODE_MARKETING_EXPENSE, 'DEBIT', $amount);

        // CREDIT: Bonus liability (we owe this to user)
        $this->addLine($entry, LedgerAccount::CODE_BONUS_LIABILITY, 'CREDIT', $amount);

        $this->validateBalanced($entry);

        return $entry;
    }

    /**
     * @deprecated PHASE 4 SECTION 7.2: Use recordBonusToWallet(int, float, string) instead.
     *
     * Record bonus conversion to wallet (when user receives bonus as cash).
     * Legacy method kept for backward compatibility.
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
    public function recordBonusToWalletLegacy(
        User $user,
        int $referenceId,
        float $amount
    ): LedgerEntry {
        $this->validatePositiveAmount($amount);

        $entry = $this->createEntry(
            LedgerEntry::REF_BONUS_CREDIT,
            $referenceId,
            "Bonus to wallet (legacy): {$user->name} - ₹" . number_format($amount, 2)
        );

        // DEBIT: Decrease bonus liability
        $this->addLine($entry, LedgerAccount::CODE_BONUS_LIABILITY, 'DEBIT', $amount);

        // CREDIT: Increase wallet liability
        $this->addLine($entry, LedgerAccount::CODE_USER_WALLET_LIABILITY, 'CREDIT', $amount);

        $this->validateBalanced($entry);

        return $entry;
    }

    /**
     * @deprecated PHASE 4.2: Use recordBonusWithTds() instead for proper TDS separation.
     *
     * Record bonus credited directly to user wallet (legacy method).
     *
     * PHASE 4.1: Simplified bonus crediting for direct-to-wallet model.
     * Used when bonuses are immediately available in user's wallet.
     *
     * WARNING: This method does NOT properly separate TDS.
     * Use recordBonusWithTds() for correct legal accounting.
     *
     * ACCOUNTING:
     *   DEBIT  MARKETING_EXPENSE       (increase expense - NET, not gross!)
     *   CREDIT USER_WALLET_LIABILITY   (increase liability - we owe user)
     *
     * @param BonusTransaction $bonus The bonus transaction record
     * @param float $netAmount Net amount after TDS (credited to wallet)
     * @return LedgerEntry
     */
    public function recordBonusCreditToWallet(
        BonusTransaction $bonus,
        float $netAmount
    ): LedgerEntry {
        $this->validatePositiveAmount($netAmount);

        $entry = $this->createEntry(
            LedgerEntry::REF_BONUS_CREDIT,
            $bonus->id,
            "Bonus credit (legacy): {$bonus->type} - ₹" . number_format($netAmount, 2) . " to user #{$bonus->user_id}"
        );

        // DEBIT: Increase marketing expense (bonus is a cost to platform)
        $this->addLine($entry, LedgerAccount::CODE_MARKETING_EXPENSE, 'DEBIT', $netAmount);

        // CREDIT: Increase wallet liability (we now owe user)
        $this->addLine($entry, LedgerAccount::CODE_USER_WALLET_LIABILITY, 'CREDIT', $netAmount);

        $this->validateBalanced($entry);

        return $entry;
    }

    /**
     * Record user withdrawal with optional TDS.
     *
     * ACCOUNTING:
     *   DEBIT  USER_WALLET_LIABILITY   (gross amount - decrease liability to user)
     *   CREDIT BANK                    (net amount - decrease asset - cash paid out)
     *   CREDIT TDS_PAYABLE             (TDS amount - increase government liability)
     *
     * RATIONALE:
     * Bank credit must match the actual funds transferred to the user.
     * The TDS portion is withheld by the platform and owed to the government.
     *
     * @param Withdrawal $withdrawal The withdrawal record
     * @param float $amount Gross amount in rupees
     * @param float $tdsAmount TDS amount in rupees (default: 0)
     * @return LedgerEntry
     */
    public function recordWithdrawal(
        $reference,
        float $amount,
        float $tdsAmount = 0
    ): LedgerEntry {
        $this->validatePositiveAmount($amount);
        $referenceId = $reference instanceof Withdrawal ? $reference->id : $reference;
        
        $netAmount = $amount - $tdsAmount;
        
        // INVARIANT: Gross = Net + TDS (must reconcile)
        if (abs($amount - ($netAmount + $tdsAmount)) > 0.01) {
            throw new \RuntimeException(
                "WITHDRAWAL TDS RECONCILIATION FAILED: Gross (₹{$amount}) != Net (₹{$netAmount}) + TDS (₹{$tdsAmount})"
            );
        }

        $entry = $this->createEntry(
            LedgerEntry::REF_WITHDRAWAL,
            $referenceId,
            "Withdrawal: ₹" . number_format($amount, 2) . ($tdsAmount > 0 ? " (TDS: ₹" . number_format($tdsAmount, 2) . ")" : "")
        );

        // DEBIT: Decrease User Wallet Liability (gross amount - we owe user less)
        $this->addLine($entry, LedgerAccount::CODE_USER_WALLET_LIABILITY, 'DEBIT', $amount);

        // CREDIT: Decrease Bank (net amount actually paid out)
        if ($netAmount > 0) {
            $this->addLine($entry, LedgerAccount::CODE_BANK, 'CREDIT', $netAmount);
        }
        
        // CREDIT: Increase TDS Payable (government liability)
        if ($tdsAmount > 0) {
            $this->addLine($entry, LedgerAccount::CODE_TDS_PAYABLE, 'CREDIT', $tdsAmount);
        }

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

    /**
     * V-DISPUTE-REMEDIATION-2026: Record chargeback reversal.
     *
     * Chargebacks are BANK-INITIATED reversals, distinct from merchant refunds.
     * When a chargeback is confirmed (bank ruled in customer's favor):
     *
     * ACCOUNTING:
     *   DEBIT  USER_WALLET_LIABILITY   (decrease liability - we no longer owe user)
     *   CREDIT BANK                    (decrease asset - funds returned to gateway/bank)
     *
     * NOTE: This is the INVERSE of a user deposit. The bank has clawed back
     * the funds, so we reduce both:
     * - What we owe the user (liability decreases)
     * - Our bank balance (asset decreases)
     *
     * This differs from a refund which:
     * - Reduces revenue (DEBIT SHARE_SALE_INCOME)
     * - Increases liability (CREDIT USER_WALLET_LIABILITY)
     *
     * For chargebacks, the user's wallet is debited directly (via WalletService),
     * so we decrease both sides of the equation.
     *
     * @param Payment|int $reference The payment record or ID being charged back
     * @param float $amount Chargeback amount (in rupees)
     * @return LedgerEntry
     */
    public function recordChargeback(
        $reference,
        float $amount
    ): LedgerEntry {
        $this->validatePositiveAmount($amount);
        $referenceId = $reference instanceof Payment ? $reference->id : $reference;

        // Ledger entries use SEMANTIC reference types (event type), not polymorphic model classes
        // Semantic > polymorphic for financial audit trail
        $entry = $this->createEntry(
            LedgerEntry::REF_CHARGEBACK,
            $referenceId,
            "Chargeback reversal for Payment #{$referenceId}: ₹" . number_format($amount, 2)
        );

        // DEBIT: Decrease User Wallet Liability (we no longer owe user this amount)
        $this->addLine($entry, LedgerAccount::CODE_USER_WALLET_LIABILITY, 'DEBIT', $amount);

        // CREDIT: Decrease Bank (funds returned to gateway/bank by chargeback)
        $this->addLine($entry, LedgerAccount::CODE_BANK, 'CREDIT', $amount);

        $this->validateBalanced($entry);

        return $entry;
    }

    /**
     * V-CHARGEBACK-HARDENING-2026: Record chargeback receivable (shortfall).
     *
     * When a chargeback exceeds the user's wallet balance, the shortfall
     * becomes an accounts receivable. This entry records that the user
     * owes the platform the shortfall amount.
     *
     * ACCOUNTING:
     *   DEBIT  ACCOUNTS_RECEIVABLE      (increase asset - user owes us)
     *   CREDIT USER_WALLET_LIABILITY    (decrease what we "owe" - actually creating negative)
     *
     * BUSINESS CONTEXT:
     * - Bank has already clawed back the full chargeback amount
     * - We debited wallet to zero (cannot go negative)
     * - The shortfall is now a receivable from the user
     * - This may be collected via future deposits or legal action
     *
     * @param Payment $payment The payment being charged back
     * @param float $shortfallAmount Shortfall amount in rupees (what user still owes)
     * @param int $userId User who owes the shortfall
     * @return LedgerEntry
     */
    public function recordChargebackReceivable(
        Payment $payment,
        float $shortfallAmount,
        int $userId
    ): LedgerEntry {
        $this->validatePositiveAmount($shortfallAmount);

        $entry = $this->createEntry(
            LedgerEntry::REF_CHARGEBACK_RECEIVABLE,
            $payment->id,
            "Chargeback shortfall receivable: User #{$userId}, Payment #{$payment->id} - ₹" . number_format($shortfallAmount, 2)
        );

        // DEBIT: Increase Accounts Receivable (user owes us)
        $this->addLine($entry, LedgerAccount::CODE_ACCOUNTS_RECEIVABLE, 'DEBIT', $shortfallAmount);

        // CREDIT: Decrease User Wallet Liability (reconciliation entry)
        // This balances the liability side that was "over-debited" when we
        // debited the full chargeback amount from liability in recordChargeback()
        $this->addLine($entry, LedgerAccount::CODE_USER_WALLET_LIABILITY, 'CREDIT', $shortfallAmount);

        $this->validateBalanced($entry);

        return $entry;
    }

    // =========================================================================
    // PLATFORM OPERATIONS
    // =========================================================================

    /**
     * Record platform operating expense (rent, salaries, vendors, etc.)
     *
     * ACCOUNTING:
     *   DEBIT  OPERATING_EXPENSES  (increase expense)
     *   CREDIT BANK                (decrease asset - cash paid out)
     *
     * @param int $referenceId Expense record/invoice ID
     * @param float $amount Expense amount (in rupees)
     * @param string $description Description of expense
     * @param int|null $adminId Admin recording this
     * @return LedgerEntry
     */
    public function recordOperatingExpense(
        int $referenceId,
        float $amount,
        string $description,
        ?int $adminId = null
    ): LedgerEntry {
        $this->validatePositiveAmount($amount);

        $entry = $this->createEntry(
            LedgerEntry::REF_OPERATING_EXPENSE,
            $referenceId,
            "Operating expense: {$description} - ₹" . number_format($amount, 2),
            $adminId
        );

        // DEBIT: Increase operating expense
        $this->addLine($entry, LedgerAccount::CODE_OPERATING_EXPENSES, 'DEBIT', $amount);

        // CREDIT: Decrease bank (cash paid out)
        $this->addLine($entry, LedgerAccount::CODE_BANK, 'CREDIT', $amount);

        $this->validateBalanced($entry);

        return $entry;
    }

    /**
     * Record profit share distribution with TDS.
     *
     * ACCOUNTING (3-way split like bonus):
     *   DEBIT  MARKETING_EXPENSE  (gross distribution - platform expense)
     *   CREDIT BONUS_LIABILITY    (net amount - user entitlement before wallet transfer)
     *   CREDIT TDS_PAYABLE        (TDS amount owed to government)
     *
     * PHASE 4 FIX: Changed from USER_WALLET_LIABILITY to BONUS_LIABILITY.
     * This follows the same two-step pattern as regular bonuses:
     *   Step 1: Record profit share accrual (this method) - credits BONUS_LIABILITY
     *   Step 2: Transfer to wallet (via WalletService::deposit with bonus_credit)
     *           - triggers recordBonusToWallet() which debits BONUS_LIABILITY
     *             and credits USER_WALLET_LIABILITY
     *
     * RATIONALE:
     * - Profit share is functionally a bonus (incentive payment to users)
     * - Using BONUS_LIABILITY allows consistent tracking with other bonus types
     * - Prevents double-crediting USER_WALLET_LIABILITY
     *
     * NOTE: Profit share is treated as marketing expense (incentive to users).
     * The TDS must be properly recorded as government liability.
     *
     * @param int $distributionId Profit share distribution ID
     * @param float $grossAmount Gross distribution amount
     * @param float $tdsAmount TDS deducted
     * @param int $userId User receiving distribution
     * @return LedgerEntry
     */
    public function recordProfitShareWithTds(
        int $distributionId,
        float $grossAmount,
        float $tdsAmount,
        int $userId
    ): LedgerEntry {
        $this->validatePositiveAmount($grossAmount);

        $netAmount = $grossAmount - $tdsAmount;

        // INVARIANT: Gross = Net + TDS (must reconcile)
        if (abs($grossAmount - ($netAmount + $tdsAmount)) > 0.01) {
            throw new \RuntimeException(
                "PROFIT SHARE TDS RECONCILIATION FAILED: Gross (₹{$grossAmount}) != Net (₹{$netAmount}) + TDS (₹{$tdsAmount})"
            );
        }

        $entry = $this->createEntry(
            LedgerEntry::REF_PROFIT_SHARE,
            $distributionId,
            "Profit share: User #{$userId} - Gross ₹" . number_format($grossAmount, 2) .
            ", TDS ₹" . number_format($tdsAmount, 2) .
            ", Net ₹" . number_format($netAmount, 2)
        );

        // DEBIT: Marketing expense (gross - full platform cost)
        $this->addLine($entry, LedgerAccount::CODE_MARKETING_EXPENSE, 'DEBIT', $grossAmount);

        // CREDIT: Bonus liability (net - what user is entitled to)
        // PHASE 4 FIX: Credit BONUS_LIABILITY, not USER_WALLET_LIABILITY
        // Transfer to USER_WALLET_LIABILITY happens via WalletService::deposit
        if ($netAmount > 0) {
            $this->addLine($entry, LedgerAccount::CODE_BONUS_LIABILITY, 'CREDIT', $netAmount);
        }

        // CREDIT: TDS payable (government liability)
        if ($tdsAmount > 0) {
            $this->addLine($entry, LedgerAccount::CODE_TDS_PAYABLE, 'CREDIT', $tdsAmount);
        }

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
        // Note: ofType() already returns a Collection, no need for ->get()
        $assets = LedgerAccount::ofType(LedgerAccount::TYPE_ASSET)
            ->sum(fn($a) => $a->balance);

        $liabilities = LedgerAccount::ofType(LedgerAccount::TYPE_LIABILITY)
            ->sum(fn($a) => $a->balance);

        $equity = LedgerAccount::ofType(LedgerAccount::TYPE_EQUITY)
            ->sum(fn($a) => $a->balance);

        // Include retained earnings (net income)
        $income = LedgerAccount::ofType(LedgerAccount::TYPE_INCOME)
            ->sum(fn($a) => $a->balance);

        $expenses = LedgerAccount::ofType(LedgerAccount::TYPE_EXPENSE)
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
     *
     * PHASE 4.2 HARDENING:
     * Contains runtime guard for COST_OF_SHARES to prevent allocation-time costing.
     * COST_OF_SHARES may ONLY be used within the bulk purchase flow.
     *
     * @throws \RuntimeException if COST_OF_SHARES is used outside bulk purchase flow
     */
    private function addLine(
        LedgerEntry $entry,
        string $accountCode,
        string $direction,
        float $amount
    ): LedgerLine {
        // =========================================================================
        // PHASE 4.2 HARDENING: COST_OF_SHARES GUARD
        // =========================================================================
        //
        // INVARIANT: Cost is recognized at PURCHASE TIME ONLY.
        //
        // COST_OF_SHARES may only be used during:
        // - recordInventoryPurchase() (DEBIT - recognize expense)
        // - recordInventoryPurchaseReversal() (CREDIT - reverse expense)
        // - recordBonusUsage() (CREDIT - offset expense for bonus-funded shares)
        //
        // PHASE 4 SECTION 7.2: Bonus usage is a special case where we CREDIT
        // COST_OF_SHARES to offset the expense because the cost was already
        // covered by MARKETING_EXPENSE when the bonus was granted.
        //
        // Any other usage would cause expense double-counting.
        // This guard makes the violation IMPOSSIBLE, not just prohibited.
        // =========================================================================
        if ($accountCode === LedgerAccount::CODE_COST_OF_SHARES) {
            $isAllowedDebit = $direction === 'DEBIT' && $this->inBulkPurchaseFlow;
            $isAllowedCredit = $direction === 'CREDIT' && ($this->inBulkPurchaseFlow || $this->inBonusUsageFlow);

            if (!$isAllowedDebit && !$isAllowedCredit) {
                throw new \RuntimeException(
                    "ACCOUNTING VIOLATION: COST_OF_SHARES may only be used in bulk purchase or bonus usage flow. " .
                    "Cost is recognized at PURCHASE TIME ONLY. " .
                    "Bonus usage CREDITS to offset expense (cost covered by marketing expense). " .
                    "Attempted direction: {$direction}, amount: ₹{$amount}, entry: #{$entry->id}"
                );
            }
        }

        $account = $this->getAccount($accountCode);

        // Convert rupees to paise for atomic integer storage
        $amountPaise = (int) round($amount * 100);

        return LedgerLine::create([
            'ledger_entry_id' => $entry->id,
            'ledger_account_id' => $account->id,
            'direction' => $direction,
            'amount_paise' => $amountPaise,
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
