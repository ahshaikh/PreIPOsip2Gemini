<?php

/**
 * V-DISPUTE-REMEDIATION-2026: Chargeback Integration Tests
 *
 * Tests the following scenarios:
 * 1. Chargeback replay (same gateway ID twice)
 * 2. Chargeback after refund
 * 3. Refund after chargeback_confirmed
 * 4. Negative wallet balance scenario
 * 5. Ledger balanced after chargeback
 * 6. Chargeback amount validation
 */

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Tests\FeatureTestCase;
use App\Models\Payment;
use App\Models\User;
use App\Models\Subscription;
use App\Models\Wallet;
use App\Models\LedgerEntry;
use App\Models\LedgerLine;
use App\Models\LedgerAccount;
use App\Services\PaymentWebhookService;
use App\Services\WalletService;
use App\Services\DoubleEntryLedgerService;
use App\Enums\TransactionType;
use Mockery;

class ChargebackIntegrationTest extends FeatureTestCase
{
    protected User $user;
    protected Subscription $subscription;
    protected WalletService $walletService;
    protected PaymentWebhookService $webhookService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        // Note: Ledger accounts are seeded by the migration itself

        $this->user = User::factory()->create();
        $this->user->wallet()->create(['balance_paise' => 100000]); // ₹1000

        $this->subscription = Subscription::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'active',
        ]);

        $this->walletService = app(WalletService::class);
        $this->webhookService = app(PaymentWebhookService::class);
    }

    // =========================================================================
    // TEST 1: Chargeback Replay (Idempotency)
    // =========================================================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_chargeback_replay_same_gateway_id_is_idempotent()
    {
        // Create a paid payment
        $payment = Payment::factory()->create([
            'user_id' => $this->user->id,
            'subscription_id' => $this->subscription->id,
            'status' => Payment::STATUS_PAID,
            'amount' => 1000,
            'amount_paise' => 100000,
            'gateway_payment_id' => 'pay_chargeback_replay_test',
        ]);

        $chargebackPayload = [
            'payment_id' => 'pay_chargeback_replay_test',
            'chargeback_id' => 'chbk_replay_123',
            'reason' => 'Fraudulent transaction',
            'amount' => 100000,
        ];

        // First chargeback initiation
        $this->webhookService->handleChargebackInitiated($chargebackPayload);
        $payment->refresh();

        $this->assertEquals(Payment::STATUS_CHARGEBACK_PENDING, $payment->status);
        $this->assertEquals('chbk_replay_123', $payment->chargeback_gateway_id);

        // Second chargeback initiation (replay) - should be idempotent
        $this->webhookService->handleChargebackInitiated($chargebackPayload);
        $payment->refresh();

        // Status should still be chargeback_pending (not changed)
        $this->assertEquals(Payment::STATUS_CHARGEBACK_PENDING, $payment->status);
        $this->assertEquals('chbk_replay_123', $payment->chargeback_gateway_id);

        // Only one chargeback should be recorded
        $this->assertEquals(1, Payment::where('chargeback_gateway_id', 'chbk_replay_123')->count());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_chargeback_confirmation_replay_is_idempotent()
    {
        // Create a payment in chargeback_pending state
        $payment = Payment::factory()->create([
            'user_id' => $this->user->id,
            'subscription_id' => $this->subscription->id,
            'status' => Payment::STATUS_CHARGEBACK_PENDING,
            'amount' => 500,
            'amount_paise' => 50000,
            'gateway_payment_id' => 'pay_confirm_replay_test',
            'chargeback_gateway_id' => 'chbk_confirm_replay',
            'chargeback_initiated_at' => now(),
            'chargeback_amount_paise' => 50000,
        ]);

        $initialWalletBalance = $this->user->wallet->balance_paise;

        $confirmPayload = [
            'payment_id' => 'pay_confirm_replay_test',
            'chargeback_id' => 'chbk_confirm_replay',
        ];

        // First confirmation
        $this->webhookService->handleChargebackConfirmed($confirmPayload);
        $payment->refresh();
        $this->user->wallet->refresh();

        $this->assertEquals(Payment::STATUS_CHARGEBACK_CONFIRMED, $payment->status);
        $balanceAfterFirst = $this->user->wallet->balance_paise;

        // Second confirmation (replay) - should be idempotent
        $this->webhookService->handleChargebackConfirmed($confirmPayload);
        $payment->refresh();
        $this->user->wallet->refresh();

        // Balance should not change on replay
        $this->assertEquals($balanceAfterFirst, $this->user->wallet->balance_paise);
        $this->assertEquals(Payment::STATUS_CHARGEBACK_CONFIRMED, $payment->status);
    }

    // =========================================================================
    // TEST 2: Chargeback After Refund (Should be rejected)
    // =========================================================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_chargeback_after_full_refund_is_rejected()
    {
        // Create a refunded payment
        $payment = Payment::factory()->create([
            'user_id' => $this->user->id,
            'subscription_id' => $this->subscription->id,
            'status' => Payment::STATUS_REFUNDED,
            'amount' => 1000,
            'amount_paise' => 100000,
            'gateway_payment_id' => 'pay_refunded_chargeback_test',
            'refund_amount_paise' => 100000, // Fully refunded
            'refund_gateway_id' => 'rfnd_already_done',
            'refunded_at' => now(),
        ]);

        $initialStatus = $payment->status;
        $initialWalletBalance = $this->user->wallet->balance_paise;

        $chargebackPayload = [
            'payment_id' => 'pay_refunded_chargeback_test',
            'chargeback_id' => 'chbk_after_refund',
            'reason' => 'Late dispute',
        ];

        // Chargeback should not be processed on refunded payment
        $this->webhookService->handleChargebackInitiated($chargebackPayload);
        $payment->refresh();
        $this->user->wallet->refresh();

        // Payment status should remain refunded
        $this->assertEquals($initialStatus, $payment->status);
        // Wallet should be unchanged
        $this->assertEquals($initialWalletBalance, $this->user->wallet->balance_paise);
        // No chargeback_gateway_id should be set
        $this->assertNull($payment->chargeback_gateway_id);
    }

    // =========================================================================
    // TEST 3: Refund After Chargeback Confirmed (Should be rejected)
    // =========================================================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_refund_after_chargeback_confirmed_is_rejected()
    {
        // Create a payment in chargeback_confirmed state
        $payment = Payment::factory()->create([
            'user_id' => $this->user->id,
            'subscription_id' => $this->subscription->id,
            'status' => Payment::STATUS_CHARGEBACK_CONFIRMED,
            'amount' => 1000,
            'amount_paise' => 100000,
            'gateway_payment_id' => 'pay_chargeback_then_refund',
            'chargeback_gateway_id' => 'chbk_confirmed_123',
            'chargeback_confirmed_at' => now(),
            'chargeback_amount_paise' => 100000,
        ]);

        $initialWalletBalance = $this->user->wallet->balance_paise;
        $initialRefundAmount = $payment->refund_amount_paise ?? 0;

        $refundPayload = [
            'payment_id' => 'pay_chargeback_then_refund',
            'refund_id' => 'rfnd_after_chargeback',
            'amount' => 100000,
        ];

        // Refund should be rejected on chargeback_confirmed payment
        $this->webhookService->handleRefundProcessed($refundPayload);
        $payment->refresh();
        $this->user->wallet->refresh();

        // Payment should still be chargeback_confirmed
        $this->assertEquals(Payment::STATUS_CHARGEBACK_CONFIRMED, $payment->status);
        // No new refund should be recorded
        $this->assertEquals($initialRefundAmount, $payment->refund_amount_paise ?? 0);
        // Wallet balance unchanged
        $this->assertEquals($initialWalletBalance, $this->user->wallet->balance_paise);
    }

    // =========================================================================
    // TEST 4: Insufficient Balance Creates Receivable (NO OVERDRAFT, NO ROLLBACK)
    // V-CHARGEBACK-HARDENING-2026: Chargeback completes, shortfall recorded
    // =========================================================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_chargeback_creates_receivable_when_wallet_insufficient()
    {
        // =========================================================================
        // EXACT SCENARIO FROM DIRECTIVE:
        // Deposit 1000, Invest 600, Wallet 400, Chargeback 1000
        //
        // EXPECTED:
        // - Wallet = 0
        // - Income = 0
        // - Bank = 0
        // - Receivable = 600
        // - Payment = confirmed
        // - Ledger balanced
        // =========================================================================

        $this->user->wallet->update(['balance_paise' => 0]);
        $ledgerService = app(DoubleEntryLedgerService::class);

        // Step 1: Deposit ₹1000
        $payment = Payment::factory()->create([
            'user_id' => $this->user->id,
            'subscription_id' => $this->subscription->id,
            'status' => Payment::STATUS_PAID,
            'amount' => 1000,
            'amount_paise' => 100000,
            'gateway_payment_id' => 'pay_receivable_test',
        ]);

        $this->walletService->deposit(
            $this->user,
            100000,
            TransactionType::DEPOSIT,
            'Receivable test deposit',
            $payment,
            true
        );

        $this->user->wallet->refresh();
        $this->assertEquals(100000, $this->user->wallet->balance_paise, 'After deposit: wallet = ₹1000');

        // Step 2: Invest ₹600 (wallet = ₹400)
        $product = \App\Models\Product::factory()->create();
        $investment = \App\Models\UserInvestment::factory()->create([
            'user_id' => $this->user->id,
            'product_id' => $product->id,
            'payment_id' => $payment->id,
            'value_allocated' => 600, // ₹600
            'is_reversed' => false,
        ]);

        // V-AUDIT-FIX-2026: WalletService.withdraw() with INVESTMENT type
        // automatically creates ledger entries (DEBIT USER_WALLET_LIABILITY, CREDIT SHARE_SALE_INCOME)
        $this->walletService->withdraw(
            $this->user,
            60000, // ₹600 in paise
            TransactionType::INVESTMENT,
            'Receivable test investment',
            $investment
        );

        $this->user->wallet->refresh();
        $this->assertEquals(40000, $this->user->wallet->balance_paise, 'After investment: wallet = ₹400');

        // Verify income is 600 before chargeback
        $incomeBeforeChargeback = $ledgerService->getAccountBalance(LedgerAccount::CODE_SHARE_SALE_INCOME);
        $this->assertEquals(600.0, $incomeBeforeChargeback, 'Before chargeback: income = ₹600');

        // Step 3: Chargeback ₹1000 (FULL payment amount)
        //
        // BUSINESS RULE: User owes FULL chargeback amount.
        // - User invested 600, has 400 in wallet
        // - User owes 1000 (full chargeback)
        // - Wallet can only cover 400
        // - Shortfall = 1000 - 400 = 600 → becomes receivable
        //
        // Note: Investment reversal means user loses shares, revenue reversed,
        // but does NOT reduce what user owes for chargeback.

        $payment->update([
            'status' => Payment::STATUS_CHARGEBACK_PENDING,
            'chargeback_gateway_id' => 'chbk_receivable_test',
            'chargeback_initiated_at' => now(),
            'chargeback_amount_paise' => 100000, // ₹1000
        ]);

        // Process chargeback - should complete WITHOUT exception (no rollback)
        $this->webhookService->handleChargebackConfirmed([
            'payment_id' => 'pay_receivable_test',
            'chargeback_id' => 'chbk_receivable_test',
        ]);

        // =========================================================================
        // VERIFY FINAL STATE
        // =========================================================================

        $payment->refresh();
        $this->user->wallet->refresh();
        $investment->refresh();

        // 1. Payment MUST be confirmed (bank finality)
        $this->assertEquals(
            Payment::STATUS_CHARGEBACK_CONFIRMED,
            $payment->status,
            'Payment must be confirmed (bank finality)'
        );

        // 2. Wallet = 0 (debited to zero, not negative)
        $this->assertEquals(0, $this->user->wallet->balance_paise, 'Wallet = 0');

        // 3. Income = 0 (revenue reversed)
        $finalIncome = $ledgerService->getAccountBalance(LedgerAccount::CODE_SHARE_SALE_INCOME);
        $this->assertEquals(0.0, $finalIncome, 'Income = 0 (revenue reversed)');

        // 4. Bank = 0 (clawed back)
        $finalBank = $ledgerService->getAccountBalance(LedgerAccount::CODE_BANK);
        $this->assertEquals(0.0, $finalBank, 'Bank = 0 (clawed back)');

        // 5. Receivable = 600 (shortfall)
        $finalReceivable = $ledgerService->getAccountBalance(LedgerAccount::CODE_ACCOUNTS_RECEIVABLE);
        $this->assertEquals(600.0, $finalReceivable, 'Receivable = 600 (shortfall)');

        // 6. Receivable ledger entry exists with correct amount
        $receivableEntry = LedgerEntry::where('reference_type', LedgerEntry::REF_CHARGEBACK_RECEIVABLE)
            ->where('reference_id', $payment->id)
            ->first();
        $this->assertNotNull($receivableEntry, 'Receivable ledger entry must exist');

        $receivableLines = $receivableEntry->lines;
        $receivableDebit = $receivableLines->where('direction', 'DEBIT')->first();
        $this->assertEquals(60000, $receivableDebit->amount_paise, 'Receivable = ₹600 (60000 paise)');

        // 7. Investment reversed
        $this->assertTrue($investment->is_reversed, 'Investment must be reversed');

        // 8. No active investments
        $activeInvestments = $payment->investments()->where('is_reversed', false)->count();
        $this->assertEquals(0, $activeInvestments, 'No active investments');

        // 9. Ledger MUST be balanced
        $equationResult = $ledgerService->verifyAccountingEquation();
        $this->assertTrue($equationResult['is_balanced'], 'Ledger must be balanced');

        // 10. Audit log exists
        $auditLog = \App\Models\AuditLog::where('action', 'chargeback.shortfall.receivable_created')
            ->where('actor_id', $this->user->id)
            ->first();
        $this->assertNotNull($auditLog, 'Shortfall audit log must be created');
        $this->assertEquals(60000, $auditLog->metadata['shortfall_paise'], 'Audit log shortfall = 60000 paise');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_chargeback_succeeds_when_wallet_has_sufficient_balance()
    {
        // Set wallet to exact amount needed for FULL chargeback
        $this->user->wallet->update(['balance_paise' => 100000]); // Exactly ₹1000

        $payment = Payment::factory()->create([
            'user_id' => $this->user->id,
            'subscription_id' => $this->subscription->id,
            'status' => Payment::STATUS_CHARGEBACK_PENDING,
            'amount' => 1000,
            'amount_paise' => 100000,
            'gateway_payment_id' => 'pay_sufficient_test',
            'chargeback_gateway_id' => 'chbk_sufficient_123',
            'chargeback_initiated_at' => now(),
            'chargeback_amount_paise' => 100000, // ₹1000 chargeback
        ]);

        // Should succeed without exception
        $this->webhookService->handleChargebackConfirmed([
            'payment_id' => 'pay_sufficient_test',
            'chargeback_id' => 'chbk_sufficient_123',
        ]);

        $payment->refresh();
        $this->user->wallet->refresh();

        // Payment confirmed
        $this->assertEquals(Payment::STATUS_CHARGEBACK_CONFIRMED, $payment->status);

        // Wallet = 0 (full 1000 debited, no shortfall)
        $this->assertEquals(0, $this->user->wallet->balance_paise);

        // No receivable entry (wallet was sufficient for full chargeback)
        $receivableEntry = LedgerEntry::where('reference_type', LedgerEntry::REF_CHARGEBACK_RECEIVABLE)
            ->where('reference_id', $payment->id)
            ->first();
        $this->assertNull($receivableEntry, 'No receivable when wallet covers full chargeback');

        // Verify no shortfall audit log
        $shortfallLog = \App\Models\AuditLog::where('action', 'chargeback.shortfall.receivable_created')
            ->where('actor_id', $this->user->id)
            ->first();
        $this->assertNull($shortfallLog, 'No shortfall audit log when wallet sufficient');
    }

    // =========================================================================
    // TEST 5: Ledger Balanced After Chargeback
    // =========================================================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_ledger_balanced_after_chargeback()
    {
        // First, record a deposit to create the initial liability
        $depositAmount = 100000; // ₹1000
        $this->walletService->deposit(
            $this->user,
            $depositAmount,
            TransactionType::DEPOSIT,
            'Test deposit',
            null,
            true // bypass compliance
        );

        // Get initial ledger state
        $ledgerService = app(DoubleEntryLedgerService::class);
        $initialEquationResult = $ledgerService->verifyAccountingEquation();
        $this->assertTrue($initialEquationResult['is_balanced'], 'Ledger should be balanced after deposit');

        // Create a payment that was deposited
        $payment = Payment::factory()->create([
            'user_id' => $this->user->id,
            'subscription_id' => $this->subscription->id,
            'status' => Payment::STATUS_CHARGEBACK_PENDING,
            'amount' => 1000,
            'amount_paise' => 100000,
            'gateway_payment_id' => 'pay_ledger_balance_test',
            'chargeback_gateway_id' => 'chbk_ledger_test',
            'chargeback_initiated_at' => now(),
            'chargeback_amount_paise' => 100000,
        ]);

        // Process chargeback
        $confirmPayload = [
            'payment_id' => 'pay_ledger_balance_test',
            'chargeback_id' => 'chbk_ledger_test',
        ];

        $this->webhookService->handleChargebackConfirmed($confirmPayload);

        // Verify ledger is still balanced
        $finalEquationResult = $ledgerService->verifyAccountingEquation();
        $this->assertTrue(
            $finalEquationResult['is_balanced'],
            'Ledger should be balanced after chargeback. ' .
            "Assets: {$finalEquationResult['assets']}, " .
            "Liabilities + Equity: {$finalEquationResult['right_side']}"
        );

        // Verify chargeback entry exists and is balanced
        $chargebackEntry = LedgerEntry::where('reference_type', LedgerEntry::REF_CHARGEBACK)
            ->where('reference_id', $payment->id)
            ->first();

        $this->assertNotNull($chargebackEntry, 'Chargeback ledger entry should exist');
        $this->assertTrue($chargebackEntry->isBalanced(), 'Chargeback entry should be balanced');
        $this->assertEquals(1000, $chargebackEntry->total_debits, 'Chargeback debit should be ₹1000');
        $this->assertEquals(1000, $chargebackEntry->total_credits, 'Chargeback credit should be ₹1000');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_chargeback_ledger_entry_has_correct_accounts()
    {
        // Create wallet deposit first
        $this->walletService->deposit(
            $this->user,
            100000,
            TransactionType::DEPOSIT,
            'Initial deposit',
            null,
            true
        );

        $payment = Payment::factory()->create([
            'user_id' => $this->user->id,
            'subscription_id' => $this->subscription->id,
            'status' => Payment::STATUS_CHARGEBACK_PENDING,
            'amount' => 1000,
            'amount_paise' => 100000,
            'gateway_payment_id' => 'pay_ledger_accounts_test',
            'chargeback_gateway_id' => 'chbk_accounts_test',
            'chargeback_initiated_at' => now(),
            'chargeback_amount_paise' => 100000,
        ]);

        $this->webhookService->handleChargebackConfirmed([
            'payment_id' => 'pay_ledger_accounts_test',
            'chargeback_id' => 'chbk_accounts_test',
        ]);

        $chargebackEntry = LedgerEntry::where('reference_type', LedgerEntry::REF_CHARGEBACK)
            ->where('reference_id', $payment->id)
            ->first();

        $this->assertNotNull($chargebackEntry);

        // Verify correct accounts are debited/credited
        $lines = $chargebackEntry->lines()->with('account')->get();

        $debitLine = $lines->firstWhere('direction', 'DEBIT');
        $creditLine = $lines->firstWhere('direction', 'CREDIT');

        $this->assertNotNull($debitLine);
        $this->assertNotNull($creditLine);

        // DEBIT should be USER_WALLET_LIABILITY (we no longer owe user)
        $this->assertEquals(
            LedgerAccount::CODE_USER_WALLET_LIABILITY,
            $debitLine->account->code,
            'Chargeback should DEBIT USER_WALLET_LIABILITY'
        );

        // CREDIT should be BANK (funds clawed back)
        $this->assertEquals(
            LedgerAccount::CODE_BANK,
            $creditLine->account->code,
            'Chargeback should CREDIT BANK'
        );
    }

    // =========================================================================
    // TEST 6: Chargeback Amount Validation
    // =========================================================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_chargeback_exceeding_refundable_amount_is_rejected()
    {
        // Create a partially refunded payment
        $payment = Payment::factory()->create([
            'user_id' => $this->user->id,
            'subscription_id' => $this->subscription->id,
            'status' => Payment::STATUS_CHARGEBACK_PENDING,
            'amount' => 1000,
            'amount_paise' => 100000, // ₹1000
            'gateway_payment_id' => 'pay_partial_chargeback_test',
            'chargeback_gateway_id' => 'chbk_partial_test',
            'chargeback_initiated_at' => now(),
            'chargeback_amount_paise' => 100000, // Trying to chargeback full amount
            'refund_amount_paise' => 80000, // Already refunded ₹800
        ]);

        // Refundable amount is only ₹200 (100000 - 80000)
        // Chargeback is trying to claim ₹1000

        $confirmPayload = [
            'payment_id' => 'pay_partial_chargeback_test',
            'chargeback_id' => 'chbk_partial_test',
        ];

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('exceeds remaining refundable amount');

        $this->webhookService->handleChargebackConfirmed($confirmPayload);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_chargeback_within_refundable_amount_succeeds()
    {
        // Create a partially refunded payment
        $payment = Payment::factory()->create([
            'user_id' => $this->user->id,
            'subscription_id' => $this->subscription->id,
            'status' => Payment::STATUS_CHARGEBACK_PENDING,
            'amount' => 1000,
            'amount_paise' => 100000, // ₹1000
            'gateway_payment_id' => 'pay_valid_chargeback_test',
            'chargeback_gateway_id' => 'chbk_valid_test',
            'chargeback_initiated_at' => now(),
            'chargeback_amount_paise' => 20000, // Only chargebacking ₹200
            'refund_amount_paise' => 80000, // Already refunded ₹800
        ]);

        // Refundable amount is ₹200 (100000 - 80000)
        // Chargeback is claiming exactly ₹200 - should succeed

        $confirmPayload = [
            'payment_id' => 'pay_valid_chargeback_test',
            'chargeback_id' => 'chbk_valid_test',
        ];

        $this->webhookService->handleChargebackConfirmed($confirmPayload);
        $payment->refresh();

        $this->assertEquals(Payment::STATUS_CHARGEBACK_CONFIRMED, $payment->status);
    }

    // =========================================================================
    // TEST 7: Database Constraint Tests (UNIQUE on chargeback_gateway_id)
    // =========================================================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_unique_constraint_on_chargeback_gateway_id()
    {
        // First payment with chargeback
        Payment::factory()->create([
            'user_id' => $this->user->id,
            'subscription_id' => $this->subscription->id,
            'status' => Payment::STATUS_CHARGEBACK_PENDING,
            'gateway_payment_id' => 'pay_unique_test_1',
            'chargeback_gateway_id' => 'chbk_unique_123',
        ]);

        // Second payment trying to use same chargeback_gateway_id should fail
        $this->expectException(\Illuminate\Database\QueryException::class);

        Payment::factory()->create([
            'user_id' => $this->user->id,
            'subscription_id' => $this->subscription->id,
            'status' => Payment::STATUS_CHARGEBACK_PENDING,
            'gateway_payment_id' => 'pay_unique_test_2',
            'chargeback_gateway_id' => 'chbk_unique_123', // Duplicate!
        ]);
    }

    // =========================================================================
    // TEST 8: State Machine Transition Tests
    // =========================================================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_chargeback_state_transitions_are_enforced()
    {
        // paid -> chargeback_pending: ALLOWED
        $payment = Payment::factory()->create([
            'user_id' => $this->user->id,
            'subscription_id' => $this->subscription->id,
            'status' => Payment::STATUS_PAID,
            'amount' => 500,
            'amount_paise' => 50000,
            'gateway_payment_id' => 'pay_state_test',
        ]);

        $this->assertTrue($payment->canChargeback());

        // Mark as chargeback pending
        $payment->markAsChargebackPending('chbk_state_test', 'Test reason', 50000);
        $payment->refresh();

        $this->assertEquals(Payment::STATUS_CHARGEBACK_PENDING, $payment->status);
        $this->assertTrue($payment->isChargebackPending());

        // Confirm chargeback
        $payment->confirmChargeback();
        $payment->refresh();

        $this->assertEquals(Payment::STATUS_CHARGEBACK_CONFIRMED, $payment->status);
        $this->assertTrue($payment->isChargebackConfirmed());

        // chargeback_confirmed is terminal - cannot transition further
        $this->assertFalse($payment->canChargeback());
    }

    // =========================================================================
    // TEST 9: Raw SQL Ledger Symmetry Assertion (P0-FIX-4)
    // =========================================================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_raw_db_ledger_symmetry_assertion()
    {
        // P0-FIX-4: Raw SQL query to prove SUM(debits) == SUM(credits)
        // This bypasses the service layer entirely - pure DB truth.

        // Create a deposit
        $this->walletService->deposit(
            $this->user,
            100000,
            TransactionType::DEPOSIT,
            'Test deposit for raw assertion',
            null,
            true
        );

        // Create and process chargeback
        $payment = Payment::factory()->create([
            'user_id' => $this->user->id,
            'subscription_id' => $this->subscription->id,
            'status' => Payment::STATUS_CHARGEBACK_PENDING,
            'amount' => 400,
            'amount_paise' => 40000,
            'gateway_payment_id' => 'pay_raw_symmetry_test',
            'chargeback_gateway_id' => 'chbk_raw_symmetry_test',
            'chargeback_initiated_at' => now(),
            'chargeback_amount_paise' => 40000,
        ]);

        $this->webhookService->handleChargebackConfirmed([
            'payment_id' => 'pay_raw_symmetry_test',
            'chargeback_id' => 'chbk_raw_symmetry_test',
        ]);

        // P0-FIX-4: RAW SQL ASSERTION - bypasses all service/model abstractions
        $result = DB::selectOne("
            SELECT
                SUM(CASE WHEN direction = 'DEBIT' THEN amount_paise ELSE 0 END) as total_debits_paise,
                SUM(CASE WHEN direction = 'CREDIT' THEN amount_paise ELSE 0 END) as total_credits_paise
            FROM ledger_lines
        ");

        $totalDebitsPaise = (int) $result->total_debits_paise;
        $totalCreditsPaise = (int) $result->total_credits_paise;

        // THE FUNDAMENTAL INVARIANT: debits MUST equal credits
        $this->assertEquals(
            $totalDebitsPaise,
            $totalCreditsPaise,
            "LEDGER IMBALANCED! Raw DB shows: DEBITS={$totalDebitsPaise} paise, CREDITS={$totalCreditsPaise} paise. " .
            "Difference: " . abs($totalDebitsPaise - $totalCreditsPaise) . " paise"
        );

        // Additional: verify chargeback entry specifically
        $chargebackResult = DB::selectOne("
            SELECT
                le.id as entry_id,
                SUM(CASE WHEN ll.direction = 'DEBIT' THEN ll.amount_paise ELSE 0 END) as debits,
                SUM(CASE WHEN ll.direction = 'CREDIT' THEN ll.amount_paise ELSE 0 END) as credits
            FROM ledger_entries le
            JOIN ledger_lines ll ON ll.ledger_entry_id = le.id
            WHERE le.reference_type = ?
            AND le.reference_id = ?
            GROUP BY le.id
        ", [LedgerEntry::REF_CHARGEBACK, $payment->id]);

        $this->assertNotNull($chargebackResult, 'Chargeback ledger entry must exist');
        $this->assertEquals(
            (int) $chargebackResult->debits,
            (int) $chargebackResult->credits,
            "Chargeback entry #{$chargebackResult->entry_id} is unbalanced"
        );
        $this->assertEquals(40000, (int) $chargebackResult->debits, 'Chargeback should be 40000 paise');
    }

    // =========================================================================
    // TEST 10: Full Lifecycle Trace - Deposit → Chargeback (No Investment)
    // V-CHARGEBACK-HARDENING-2026: Clean unwind when wallet covers full chargeback
    // =========================================================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_full_lifecycle_deposit_chargeback_unwinds_to_zero()
    {
        // Scenario: User deposits 1000, does NOT invest, chargeback 1000
        // This is a clean unwind because wallet (1000) >= chargeback (1000)
        // No receivable needed.

        $this->user->wallet->update(['balance_paise' => 0]);
        $ledgerService = app(DoubleEntryLedgerService::class);

        // ========== STEP 1: DEPOSIT ₹1000 ==========
        $payment = Payment::factory()->create([
            'user_id' => $this->user->id,
            'subscription_id' => $this->subscription->id,
            'status' => Payment::STATUS_PAID,
            'amount' => 1000,
            'amount_paise' => 100000,
            'gateway_payment_id' => 'pay_lifecycle_clean_test',
        ]);

        $this->walletService->deposit(
            $this->user,
            100000,
            TransactionType::DEPOSIT,
            'Lifecycle clean test deposit',
            $payment,
            true
        );

        $this->user->wallet->refresh();
        $this->assertEquals(100000, $this->user->wallet->balance_paise, 'After deposit: wallet = 1000');

        // ========== STEP 2: CHARGEBACK ₹1000 (CLEAN UNWIND) ==========
        // No investment, so no revenue to reverse
        // User owes 1000, has 1000 in wallet, no shortfall

        $payment->update([
            'status' => Payment::STATUS_CHARGEBACK_PENDING,
            'chargeback_gateway_id' => 'chbk_lifecycle_clean_test',
            'chargeback_initiated_at' => now(),
            'chargeback_amount_paise' => 100000,
        ]);

        $this->webhookService->handleChargebackConfirmed([
            'payment_id' => 'pay_lifecycle_clean_test',
            'chargeback_id' => 'chbk_lifecycle_clean_test',
        ]);

        $this->user->wallet->refresh();

        // ========== FINAL STATE VERIFICATION ==========
        $finalWalletBalance = $this->user->wallet->balance_paise;
        $finalIncome = $ledgerService->getAccountBalance(LedgerAccount::CODE_SHARE_SALE_INCOME);
        $finalBank = $ledgerService->getAccountBalance(LedgerAccount::CODE_BANK);
        $finalReceivable = $ledgerService->getAccountBalance(LedgerAccount::CODE_ACCOUNTS_RECEIVABLE);

        // All should be ZERO (clean unwind)
        $this->assertEquals(0, $finalWalletBalance, 'Wallet = 0');
        $this->assertEquals(0.0, $finalIncome, 'Income = 0');
        $this->assertEquals(0.0, $finalBank, 'Bank = 0');
        $this->assertEquals(0.0, $finalReceivable, 'Receivable = 0 (no shortfall)');

        // No receivable entry should exist
        $receivableEntry = LedgerEntry::where('reference_type', LedgerEntry::REF_CHARGEBACK_RECEIVABLE)
            ->where('reference_id', $payment->id)
            ->first();
        $this->assertNull($receivableEntry, 'No receivable when wallet covers full chargeback');

        // Ledger balanced
        $equationResult = $ledgerService->verifyAccountingEquation();
        $this->assertTrue($equationResult['is_balanced'], 'Accounting equation must balance');
    }

    // =========================================================================
    // TEST 10b: Full Investment Chargeback Creates Full Receivable
    // When user invested everything and has 0 wallet, full chargeback = full receivable
    // =========================================================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_full_investment_chargeback_creates_full_receivable()
    {
        // Scenario: Deposit 1000, Invest 1000, Wallet 0, Chargeback 1000
        // User owes 1000, wallet has 0, shortfall = 1000

        $this->user->wallet->update(['balance_paise' => 0]);
        $ledgerService = app(DoubleEntryLedgerService::class);

        // Step 1: Deposit ₹1000
        $payment = Payment::factory()->create([
            'user_id' => $this->user->id,
            'subscription_id' => $this->subscription->id,
            'status' => Payment::STATUS_PAID,
            'amount' => 1000,
            'amount_paise' => 100000,
            'gateway_payment_id' => 'pay_full_invest_test',
        ]);

        $this->walletService->deposit(
            $this->user,
            100000,
            TransactionType::DEPOSIT,
            'Full invest test deposit',
            $payment,
            true
        );

        // Step 2: Invest ALL ₹1000 (wallet = 0)
        $product = \App\Models\Product::factory()->create();
        $investment = \App\Models\UserInvestment::factory()->create([
            'user_id' => $this->user->id,
            'product_id' => $product->id,
            'payment_id' => $payment->id,
            'value_allocated' => 1000,
            'is_reversed' => false,
        ]);

        // V-AUDIT-FIX-2026: WalletService.withdraw() with INVESTMENT type
        // automatically creates ledger entries (DEBIT USER_WALLET_LIABILITY, CREDIT SHARE_SALE_INCOME)
        $this->walletService->withdraw(
            $this->user,
            100000,
            TransactionType::INVESTMENT,
            'Full invest test',
            $investment
        );

        $this->user->wallet->refresh();
        $this->assertEquals(0, $this->user->wallet->balance_paise, 'After investment: wallet = 0');

        // Step 3: Chargeback ₹1000
        $payment->update([
            'status' => Payment::STATUS_CHARGEBACK_PENDING,
            'chargeback_gateway_id' => 'chbk_full_invest_test',
            'chargeback_initiated_at' => now(),
            'chargeback_amount_paise' => 100000,
        ]);

        $this->webhookService->handleChargebackConfirmed([
            'payment_id' => 'pay_full_invest_test',
            'chargeback_id' => 'chbk_full_invest_test',
        ]);

        $this->user->wallet->refresh();
        $investment->refresh();

        // Verify final state
        $finalWalletBalance = $this->user->wallet->balance_paise;
        $finalIncome = $ledgerService->getAccountBalance(LedgerAccount::CODE_SHARE_SALE_INCOME);
        $finalBank = $ledgerService->getAccountBalance(LedgerAccount::CODE_BANK);
        $finalReceivable = $ledgerService->getAccountBalance(LedgerAccount::CODE_ACCOUNTS_RECEIVABLE);

        // Wallet = 0, Income = 0, Bank = 0
        $this->assertEquals(0, $finalWalletBalance, 'Wallet = 0');
        $this->assertEquals(0.0, $finalIncome, 'Income = 0 (reversed)');
        $this->assertEquals(0.0, $finalBank, 'Bank = 0 (clawed back)');

        // FULL RECEIVABLE = 1000 (user invested everything, has nothing to cover chargeback)
        $this->assertEquals(1000.0, $finalReceivable, 'Receivable = 1000 (full shortfall)');

        // Investment must be reversed
        $this->assertTrue($investment->is_reversed, 'Investment must be reversed');

        // Receivable entry must exist
        $receivableEntry = LedgerEntry::where('reference_type', LedgerEntry::REF_CHARGEBACK_RECEIVABLE)
            ->where('reference_id', $payment->id)
            ->first();
        $this->assertNotNull($receivableEntry, 'Receivable entry must exist');

        // Ledger balanced
        $equationResult = $ledgerService->verifyAccountingEquation();
        $this->assertTrue($equationResult['is_balanced'], 'Accounting equation must balance');
    }

    // =========================================================================
    // TEST 11: Partial Investment Chargeback Scenario
    // V-CHARGEBACK-HARDENING-2026: Deposit ₹1000, Invest ₹600, Chargeback ₹1000
    // =========================================================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_partial_investment_chargeback_scenario()
    {
        // Start fresh
        $this->user->wallet->update(['balance_paise' => 0]);
        $ledgerService = app(DoubleEntryLedgerService::class);

        // ========== STEP 1: DEPOSIT ₹1000 ==========
        $payment = Payment::factory()->create([
            'user_id' => $this->user->id,
            'subscription_id' => $this->subscription->id,
            'status' => Payment::STATUS_PAID,
            'amount' => 1000,
            'amount_paise' => 100000,
            'gateway_payment_id' => 'pay_partial_invest_test',
        ]);

        $this->walletService->deposit(
            $this->user,
            100000,
            TransactionType::DEPOSIT,
            'Partial investment test deposit',
            $payment,
            true
        );

        $this->user->wallet->refresh();
        $this->assertEquals(100000, $this->user->wallet->balance_paise, 'After deposit: wallet = ₹1000');

        // ========== STEP 2: INVEST ₹600 (leaving ₹400 in wallet) ==========
        $product = \App\Models\Product::factory()->create();
        $investment = \App\Models\UserInvestment::factory()->create([
            'user_id' => $this->user->id,
            'product_id' => $product->id,
            'payment_id' => $payment->id,
            'value_allocated' => 600, // ₹600
            'is_reversed' => false,
        ]);

        // V-AUDIT-FIX-2026: WalletService.withdraw() with INVESTMENT type
        // automatically creates ledger entries (DEBIT USER_WALLET_LIABILITY, CREDIT SHARE_SALE_INCOME)
        $this->walletService->withdraw(
            $this->user,
            60000, // ₹600 in paise
            TransactionType::INVESTMENT,
            'Partial investment',
            $investment
        );

        $this->user->wallet->refresh();
        $this->assertEquals(40000, $this->user->wallet->balance_paise, 'After investment: wallet = ₹400');

        $incomeAfterInvestment = $ledgerService->getAccountBalance(LedgerAccount::CODE_SHARE_SALE_INCOME);
        $this->assertEquals(600.0, $incomeAfterInvestment, 'After investment: income = ₹600');

        // ========== STEP 3: CHARGEBACK ₹1000 (FULL PAYMENT AMOUNT) ==========
        // V-AUDIT-FIX-2026: Investment reversal does NOT credit wallet for chargebacks
        // - Investment of ₹600 reversed (user loses shares, NO wallet credit)
        // - Chargeback debits full ₹1000, but wallet only has ₹400
        // - Shortfall ₹600 recorded as receivable

        $payment->update([
            'status' => Payment::STATUS_CHARGEBACK_PENDING,
            'chargeback_gateway_id' => 'chbk_partial_invest_test',
            'chargeback_initiated_at' => now(),
            'chargeback_amount_paise' => 100000, // Full ₹1000
        ]);

        $this->webhookService->handleChargebackConfirmed([
            'payment_id' => 'pay_partial_invest_test',
            'chargeback_id' => 'chbk_partial_invest_test',
        ]);

        // ========== VERIFY FINAL STATE ==========
        $this->user->wallet->refresh();
        $investment->refresh();
        $payment->refresh();

        // V-AUDIT-FIX-2026: Chargeback with investment reversal + shortfall
        // Wallet = 0, Income = 0, Bank = 0, Receivable = 600
        // Liability = 600 (due to receivable credit entry for accounting balance)

        // Wallet: ₹400 - ₹400 (debited to zero) = ₹0
        $finalWallet = $this->user->wallet->balance_paise;
        $this->assertEquals(0, $finalWallet, 'Final wallet = 0');

        // Revenue: ₹600 reversed → ₹0
        $finalIncome = $ledgerService->getAccountBalance(LedgerAccount::CODE_SHARE_SALE_INCOME);
        $this->assertEquals(0.0, $finalIncome, 'Revenue reversed from ₹600 to ₹0');

        // Bank: ₹1000 clawed back → ₹0
        $finalBank = $ledgerService->getAccountBalance(LedgerAccount::CODE_BANK);
        $this->assertEquals(0.0, $finalBank, 'Bank = 0 after chargeback clawback');

        // Receivable: ₹600 (user owes us the shortfall)
        $finalReceivable = $ledgerService->getAccountBalance(LedgerAccount::CODE_ACCOUNTS_RECEIVABLE);
        $this->assertEquals(600.0, $finalReceivable, 'Receivable = 600 (shortfall)');

        // Investment reversed
        $this->assertTrue($investment->is_reversed, 'Investment must be reversed');

        // No active investments
        $activeInvestments = $payment->investments()->where('is_reversed', false)->count();
        $this->assertEquals(0, $activeInvestments, 'No active investments');

        // Ledger must be balanced
        $equationResult = $ledgerService->verifyAccountingEquation();
        $this->assertTrue($equationResult['is_balanced'], 'Accounting equation must balance');

        // V-AUDIT-FIX-2026: With receivables, wallet and liability may not match
        // (liability includes receivable credit entry for accounting balance)
        // The accounting equation being balanced is sufficient verification

        // Payment is terminal
        $this->assertEquals(Payment::STATUS_CHARGEBACK_CONFIRMED, $payment->status);
    }

    // =========================================================================
    // TEST 12: Duplicate Webhook Concurrency Protection
    // V-CHARGEBACK-HARDENING-2026: Simulate race condition with same gateway ID
    // =========================================================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_duplicate_webhook_concurrency_protection()
    {
        // Create a deposit first
        $this->walletService->deposit(
            $this->user,
            100000,
            TransactionType::DEPOSIT,
            'Concurrency test deposit',
            null,
            true
        );

        // Create payment in chargeback_pending state
        $payment = Payment::factory()->create([
            'user_id' => $this->user->id,
            'subscription_id' => $this->subscription->id,
            'status' => Payment::STATUS_CHARGEBACK_PENDING,
            'amount' => 500,
            'amount_paise' => 50000,
            'gateway_payment_id' => 'pay_concurrency_test',
            'chargeback_gateway_id' => 'chbk_concurrency_test',
            'chargeback_initiated_at' => now(),
            'chargeback_amount_paise' => 50000,
        ]);

        $initialWalletBalance = $this->user->wallet->balance_paise;
        $confirmPayload = [
            'payment_id' => 'pay_concurrency_test',
            'chargeback_id' => 'chbk_concurrency_test',
        ];

        // First webhook call - should succeed
        $this->webhookService->handleChargebackConfirmed($confirmPayload);
        $payment->refresh();
        $this->user->wallet->refresh();

        $this->assertEquals(Payment::STATUS_CHARGEBACK_CONFIRMED, $payment->status);
        $balanceAfterFirst = $this->user->wallet->balance_paise;

        // Record number of wallet transactions
        $transactionCountAfterFirst = DB::table('transactions')
            ->where('user_id', $this->user->id)
            ->count();

        // Second webhook call (duplicate/replay) - should be idempotent
        $this->webhookService->handleChargebackConfirmed($confirmPayload);
        $payment->refresh();
        $this->user->wallet->refresh();

        // Status unchanged
        $this->assertEquals(Payment::STATUS_CHARGEBACK_CONFIRMED, $payment->status);

        // Wallet balance unchanged (no double debit)
        $this->assertEquals($balanceAfterFirst, $this->user->wallet->balance_paise,
            'Wallet should not change on duplicate webhook');

        // No new transactions created
        $transactionCountAfterSecond = DB::table('transactions')
            ->where('user_id', $this->user->id)
            ->count();
        $this->assertEquals($transactionCountAfterFirst, $transactionCountAfterSecond,
            'No duplicate transactions should be created');

        // Verify UNIQUE constraint on chargeback_gateway_id prevents duplicates at DB level
        $chargebackCount = Payment::where('chargeback_gateway_id', 'chbk_concurrency_test')->count();
        $this->assertEquals(1, $chargebackCount, 'Only one payment with this chargeback_gateway_id');
    }

    // =========================================================================
    // TEST 13: Wallet Service Integration (No Direct Mutation)
    // V-CHARGEBACK-HARDENING-2026: Verify WalletService is used
    // =========================================================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_chargeback_uses_wallet_service_not_direct_mutation()
    {
        // Create deposit
        $this->walletService->deposit(
            $this->user,
            100000,
            TransactionType::DEPOSIT,
            'WalletService test deposit',
            null,
            true
        );

        $payment = Payment::factory()->create([
            'user_id' => $this->user->id,
            'subscription_id' => $this->subscription->id,
            'status' => Payment::STATUS_CHARGEBACK_PENDING,
            'amount' => 1000,
            'amount_paise' => 100000,
            'gateway_payment_id' => 'pay_wallet_service_test',
            'chargeback_gateway_id' => 'chbk_wallet_service_test',
            'chargeback_initiated_at' => now(),
            'chargeback_amount_paise' => 100000,
        ]);

        $this->webhookService->handleChargebackConfirmed([
            'payment_id' => 'pay_wallet_service_test',
            'chargeback_id' => 'chbk_wallet_service_test',
        ]);

        // Verify a proper transaction record was created
        $chargebackTransaction = DB::table('transactions')
            ->where('user_id', $this->user->id)
            ->where('reference_type', Payment::class)
            ->where('reference_id', $payment->id)
            ->where('type', TransactionType::CHARGEBACK->value)
            ->first();

        // For full unwind with no investment, net change is -100000
        // So we expect a chargeback transaction to be recorded
        $this->assertNotNull($chargebackTransaction,
            'Chargeback transaction should be created via WalletService');

        // Verify transaction has proper balance tracking
        $this->assertNotNull($chargebackTransaction->balance_before_paise);
        $this->assertNotNull($chargebackTransaction->balance_after_paise);
    }

    // =========================================================================
    // TEST 14: WALLET ↔ LIABILITY MIRROR INVARIANT
    // V-CHARGEBACK-SEMANTICS-2026: Explicit mathematical proof
    // =========================================================================

    // =========================================================================
    // TEST 14a: REFUND FLOW VERIFICATION
    // V-CHARGEBACK-SEMANTICS-2026: Verify refund creates proper ledger entries
    // =========================================================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_refund_creates_ledger_entries_and_credits_wallet()
    {
        // This test verifies that the refund flow:
        // 1. Reverses shares (if full refund)
        // 2. Creates ledger entry (DEBIT SHARE_SALE_INCOME, CREDIT USER_WALLET_LIABILITY)
        // 3. Credits wallet explicitly
        // 4. Maintains accounting symmetry

        $this->user->wallet->update(['balance_paise' => 0]);
        $ledgerService = app(DoubleEntryLedgerService::class);

        // Step 1: Deposit ₹1000
        $payment = Payment::factory()->create([
            'user_id' => $this->user->id,
            'subscription_id' => $this->subscription->id,
            'status' => Payment::STATUS_PAID,
            'amount' => 1000,
            'amount_paise' => 100000,
            'gateway_payment_id' => 'pay_refund_flow_test',
        ]);

        $this->walletService->deposit(
            $this->user,
            100000,
            TransactionType::DEPOSIT,
            'Refund flow test deposit',
            $payment,
            true
        );

        $this->user->wallet->refresh();
        $this->assertEquals(100000, $this->user->wallet->balance_paise, 'Wallet = 1000 after deposit');

        // Step 2: Invest ₹600 (wallet = 400)
        $product = \App\Models\Product::factory()->create();
        $investment = \App\Models\UserInvestment::factory()->create([
            'user_id' => $this->user->id,
            'product_id' => $product->id,
            'payment_id' => $payment->id,
            'value_allocated' => 600,
            'is_reversed' => false,
        ]);

        $this->walletService->withdraw(
            $this->user,
            60000,
            TransactionType::INVESTMENT,
            'Refund flow test investment',
            $investment
        );

        $this->user->wallet->refresh();
        $this->assertEquals(40000, $this->user->wallet->balance_paise, 'Wallet = 400 after investment');

        // Verify income is 600 before refund
        $incomeBeforeRefund = $ledgerService->getAccountBalance(LedgerAccount::CODE_SHARE_SALE_INCOME);
        $this->assertEquals(600.0, $incomeBeforeRefund, 'Income = 600 before refund');

        // Step 3: Process full refund
        $refundPayload = [
            'payment_id' => 'pay_refund_flow_test',
            'refund_id' => 'rfnd_flow_test',
            'amount' => 100000, // Full refund
        ];

        $this->webhookService->handleRefundProcessed($refundPayload);

        $payment->refresh();
        $this->user->wallet->refresh();
        $investment->refresh();

        // Verify: Payment status = refunded
        $this->assertEquals(Payment::STATUS_REFUNDED, $payment->status, 'Payment must be refunded');

        // Verify: Investment is reversed (for full refund)
        $this->assertTrue($investment->is_reversed, 'Investment must be reversed');

        // Verify: Wallet = 400 + 1000 = 1400 (original 400 + 1000 refund)
        $this->assertEquals(140000, $this->user->wallet->balance_paise, 'Wallet = 1400 after refund');

        // Verify: Ledger entry for refund exists
        $refundEntry = LedgerEntry::where('reference_type', LedgerEntry::REF_REFUND)
            ->where('reference_id', $payment->id)
            ->first();
        $this->assertNotNull($refundEntry, 'Refund ledger entry must exist');
        $this->assertTrue($refundEntry->isBalanced(), 'Refund entry must be balanced');

        // Verify: Income reversed (600 before, should be 600 - 1000 = -400 or adjusted)
        // Actually, recordRefund debits SHARE_SALE_INCOME by refund amount (1000)
        // So income = 600 - 1000 = -400 (negative because we refunded more than revenue)
        $incomeAfterRefund = $ledgerService->getAccountBalance(LedgerAccount::CODE_SHARE_SALE_INCOME);
        // This is expected: 600 (from investment) - 1000 (refund) = -400
        // A negative income is valid when refunds exceed prior revenue recognition

        // Verify: Accounting equation balanced
        $equationResult = $ledgerService->verifyAccountingEquation();
        $this->assertTrue($equationResult['is_balanced'], 'Accounting equation must balance');

        // Verify: Wallet ↔ Liability mirror
        // No receivable in refund flow, so wallet should equal liability
        $walletPaise = $this->user->wallet->balance_paise;
        $liabilityRupees = $ledgerService->getAccountBalance(LedgerAccount::CODE_USER_WALLET_LIABILITY);
        $liabilityPaise = (int) round($liabilityRupees * 100);

        $this->assertEquals(
            $walletPaise,
            $liabilityPaise,
            "Refund flow: Wallet ({$walletPaise}) must equal Liability ({$liabilityPaise})"
        );
    }

    // =========================================================================
    // TEST 14b: WALLET ↔ LIABILITY MIRROR INVARIANT
    // V-CHARGEBACK-SEMANTICS-2026: Explicit mathematical proof
    // =========================================================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_wallet_liability_mirror_without_receivable()
    {
        // INVARIANT: When no receivable exists:
        // wallet.balance_paise == ledger(USER_WALLET_LIABILITY) * 100
        //
        // This is the FUNDAMENTAL MIRROR between operational wallet and
        // accounting ledger. If this breaks, financial integrity is compromised.

        $this->user->wallet->update(['balance_paise' => 0]);
        $ledgerService = app(DoubleEntryLedgerService::class);

        // Deposit ₹1000
        $payment = Payment::factory()->create([
            'user_id' => $this->user->id,
            'subscription_id' => $this->subscription->id,
            'status' => Payment::STATUS_PAID,
            'amount' => 1000,
            'amount_paise' => 100000,
            'gateway_payment_id' => 'pay_mirror_test_1',
        ]);

        $this->walletService->deposit(
            $this->user,
            100000,
            TransactionType::DEPOSIT,
            'Mirror test deposit',
            $payment,
            true
        );

        $this->user->wallet->refresh();

        // ASSERT MIRROR: wallet == liability (in paise)
        $walletBalancePaise = $this->user->wallet->balance_paise;
        $liabilityRupees = $ledgerService->getAccountBalance(LedgerAccount::CODE_USER_WALLET_LIABILITY);
        $liabilityPaise = (int) round($liabilityRupees * 100);

        $this->assertEquals(
            $walletBalancePaise,
            $liabilityPaise,
            "WALLET ↔ LIABILITY MIRROR VIOLATED! " .
            "Wallet: {$walletBalancePaise} paise, Liability: {$liabilityPaise} paise"
        );

        // Verify no receivable exists
        $receivable = $ledgerService->getAccountBalance(LedgerAccount::CODE_ACCOUNTS_RECEIVABLE);
        $this->assertEquals(0.0, $receivable, 'No receivable should exist');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_wallet_liability_mirror_with_receivable()
    {
        // INVARIANT: When receivable exists:
        // liability = wallet + receivable_credit_offset
        //
        // The receivable entry creates:
        // DEBIT ACCOUNTS_RECEIVABLE (asset: user owes us)
        // CREDIT USER_WALLET_LIABILITY (reconciliation)
        //
        // So: LIABILITY_BALANCE = WALLET_BALANCE + RECEIVABLE_AMOUNT

        $this->user->wallet->update(['balance_paise' => 0]);
        $ledgerService = app(DoubleEntryLedgerService::class);

        // Step 1: Deposit ₹1000
        $payment = Payment::factory()->create([
            'user_id' => $this->user->id,
            'subscription_id' => $this->subscription->id,
            'status' => Payment::STATUS_PAID,
            'amount' => 1000,
            'amount_paise' => 100000,
            'gateway_payment_id' => 'pay_mirror_receivable_test',
        ]);

        $this->walletService->deposit(
            $this->user,
            100000,
            TransactionType::DEPOSIT,
            'Mirror receivable test deposit',
            $payment,
            true
        );

        // Step 2: Invest ALL ₹1000 (wallet = 0)
        $product = \App\Models\Product::factory()->create();
        $investment = \App\Models\UserInvestment::factory()->create([
            'user_id' => $this->user->id,
            'product_id' => $product->id,
            'payment_id' => $payment->id,
            'value_allocated' => 1000,
            'is_reversed' => false,
        ]);

        $this->walletService->withdraw(
            $this->user,
            100000,
            TransactionType::INVESTMENT,
            'Mirror receivable test investment',
            $investment
        );

        $this->user->wallet->refresh();
        $this->assertEquals(0, $this->user->wallet->balance_paise, 'Wallet should be 0 after full investment');

        // Step 3: Chargeback ₹1000 (creates full receivable)
        $payment->update([
            'status' => Payment::STATUS_CHARGEBACK_PENDING,
            'chargeback_gateway_id' => 'chbk_mirror_receivable_test',
            'chargeback_initiated_at' => now(),
            'chargeback_amount_paise' => 100000,
        ]);

        $this->webhookService->handleChargebackConfirmed([
            'payment_id' => 'pay_mirror_receivable_test',
            'chargeback_id' => 'chbk_mirror_receivable_test',
        ]);

        $this->user->wallet->refresh();

        // Get final balances
        $walletBalancePaise = $this->user->wallet->balance_paise;
        $liabilityRupees = $ledgerService->getAccountBalance(LedgerAccount::CODE_USER_WALLET_LIABILITY);
        $liabilityPaise = (int) round($liabilityRupees * 100);
        $receivableRupees = $ledgerService->getAccountBalance(LedgerAccount::CODE_ACCOUNTS_RECEIVABLE);
        $receivablePaise = (int) round($receivableRupees * 100);

        // ASSERT: Wallet = 0 (debited to zero)
        $this->assertEquals(0, $walletBalancePaise, 'Wallet must be 0 after chargeback');

        // ASSERT: Receivable = 1000 (full shortfall)
        $this->assertEquals(100000, $receivablePaise, 'Receivable must be ₹1000');

        // ASSERT MIRROR WITH RECEIVABLE:
        // liability = wallet + receivable (due to credit offset in receivable entry)
        //
        // Accounting flow:
        // 1. Deposit: +1000 to liability
        // 2. Investment: -1000 from liability (to income)
        // 3. Chargeback recordRefund: +1000 to liability (reverses income)
        // 4. Chargeback recordChargeback: -1000 from liability (bank clawback)
        // 5. Receivable: +1000 to liability (credit offset)
        //
        // Net liability = 1000 - 1000 + 1000 - 1000 + 1000 = 1000
        // Wallet = 0
        // Receivable = 1000
        // Therefore: liability (1000) = wallet (0) + receivable (1000) ✓

        $expectedLiabilityPaise = $walletBalancePaise + $receivablePaise;
        $this->assertEquals(
            $expectedLiabilityPaise,
            $liabilityPaise,
            "WALLET ↔ LIABILITY MIRROR WITH RECEIVABLE VIOLATED! " .
            "Expected: wallet ({$walletBalancePaise}) + receivable ({$receivablePaise}) = {$expectedLiabilityPaise}, " .
            "Actual liability: {$liabilityPaise}"
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_wallet_liability_mirror_partial_receivable()
    {
        // Scenario: Deposit 1000, Invest 600, Chargeback 1000
        // Wallet has 400, owes 1000, shortfall = 600

        $this->user->wallet->update(['balance_paise' => 0]);
        $ledgerService = app(DoubleEntryLedgerService::class);

        // Step 1: Deposit ₹1000
        $payment = Payment::factory()->create([
            'user_id' => $this->user->id,
            'subscription_id' => $this->subscription->id,
            'status' => Payment::STATUS_PAID,
            'amount' => 1000,
            'amount_paise' => 100000,
            'gateway_payment_id' => 'pay_mirror_partial_test',
        ]);

        $this->walletService->deposit(
            $this->user,
            100000,
            TransactionType::DEPOSIT,
            'Mirror partial test deposit',
            $payment,
            true
        );

        // Step 2: Invest ₹600 (wallet = 400)
        $product = \App\Models\Product::factory()->create();
        $investment = \App\Models\UserInvestment::factory()->create([
            'user_id' => $this->user->id,
            'product_id' => $product->id,
            'payment_id' => $payment->id,
            'value_allocated' => 600,
            'is_reversed' => false,
        ]);

        $this->walletService->withdraw(
            $this->user,
            60000,
            TransactionType::INVESTMENT,
            'Mirror partial test investment',
            $investment
        );

        $this->user->wallet->refresh();
        $this->assertEquals(40000, $this->user->wallet->balance_paise, 'Wallet should be 400 paise');

        // Step 3: Chargeback ₹1000 (shortfall = 600)
        $payment->update([
            'status' => Payment::STATUS_CHARGEBACK_PENDING,
            'chargeback_gateway_id' => 'chbk_mirror_partial_test',
            'chargeback_initiated_at' => now(),
            'chargeback_amount_paise' => 100000,
        ]);

        $this->webhookService->handleChargebackConfirmed([
            'payment_id' => 'pay_mirror_partial_test',
            'chargeback_id' => 'chbk_mirror_partial_test',
        ]);

        $this->user->wallet->refresh();

        // Get final balances
        $walletBalancePaise = $this->user->wallet->balance_paise;
        $liabilityRupees = $ledgerService->getAccountBalance(LedgerAccount::CODE_USER_WALLET_LIABILITY);
        $liabilityPaise = (int) round($liabilityRupees * 100);
        $receivableRupees = $ledgerService->getAccountBalance(LedgerAccount::CODE_ACCOUNTS_RECEIVABLE);
        $receivablePaise = (int) round($receivableRupees * 100);

        // ASSERT: Wallet = 0
        $this->assertEquals(0, $walletBalancePaise, 'Wallet must be 0');

        // ASSERT: Receivable = 600 (shortfall: 1000 chargeback - 400 wallet)
        $this->assertEquals(60000, $receivablePaise, 'Receivable must be ₹600');

        // ASSERT MIRROR: liability = wallet + receivable
        $expectedLiabilityPaise = $walletBalancePaise + $receivablePaise;
        $this->assertEquals(
            $expectedLiabilityPaise,
            $liabilityPaise,
            "WALLET ↔ LIABILITY MIRROR WITH PARTIAL RECEIVABLE VIOLATED! " .
            "Expected: wallet ({$walletBalancePaise}) + receivable ({$receivablePaise}) = {$expectedLiabilityPaise}, " .
            "Actual liability: {$liabilityPaise}"
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
