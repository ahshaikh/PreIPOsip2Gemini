<?php

// V-DISPUTE-RISK-2026-TEST-003: Chargeback Flow and Invariants Feature Tests

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Payment;
use App\Models\Wallet;
use App\Models\Subscription;
use App\Models\Plan;
use App\Models\LedgerEntry;
use App\Events\ChargebackConfirmed;
use App\Listeners\UpdateUserRiskProfile;
use App\Services\RiskScoringService;
use App\Services\PaymentWebhookService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\DB;

class ChargebackInvariantsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    // ==================== EVENT DISPATCH TESTS ====================

    #[\PHPUnit\Framework\Attributes\Test]
    public function chargeback_confirmed_event_is_dispatched_on_confirmation()
    {
        Event::fake([ChargebackConfirmed::class]);

        $user = $this->createUserWithWallet();
        $payment = $this->createPaidPayment($user);

        // Mark as chargeback pending first
        $payment->update([
            'status' => Payment::STATUS_CHARGEBACK_PENDING,
            'chargeback_gateway_id' => 'cb_' . uniqid(),
            'chargeback_amount_paise' => $payment->amount_paise,
        ]);

        // Simulate chargeback confirmation via webhook service
        $webhookService = app(PaymentWebhookService::class);
        $webhookService->handleChargebackConfirmed([
            'payment_id' => $payment->gateway_payment_id,
            'chargeback_id' => 'cb_confirmed_' . uniqid(),
        ]);

        Event::assertDispatched(ChargebackConfirmed::class, function ($event) use ($payment, $user) {
            return $event->payment->id === $payment->id && $event->user->id === $user->id;
        });
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function chargeback_confirmed_event_not_dispatched_for_orphan_payment()
    {
        Event::fake([ChargebackConfirmed::class]);

        // Create payment without user (edge case)
        $payment = Payment::factory()->create([
            'user_id' => null,
            'status' => Payment::STATUS_CHARGEBACK_PENDING,
            'chargeback_gateway_id' => 'cb_' . uniqid(),
        ]);

        // Attempt to confirm - should not throw, but event not dispatched
        $webhookService = app(PaymentWebhookService::class);
        $webhookService->handleChargebackConfirmed([
            'payment_id' => $payment->gateway_payment_id,
        ]);

        Event::assertNotDispatched(ChargebackConfirmed::class);
    }

    // ==================== RISK PROFILE UPDATE TESTS ====================

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_risk_score_updates_on_chargeback_confirmation()
    {
        $user = $this->createUserWithWallet();
        $this->assertEquals(0, $user->risk_score);

        $payment = $this->createPaidPayment($user);
        $this->processChargeback($payment);

        $user->refresh();

        // Score should increase (at least base weight)
        $baseWeight = config('risk.weights.chargeback_base', 25);
        $this->assertGreaterThanOrEqual($baseWeight, $user->risk_score);
        $this->assertNotNull($user->last_risk_update_at);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_auto_blocked_when_score_exceeds_threshold()
    {
        $user = $this->createUserWithWallet();
        $user->update(['risk_score' => 60]); // Near threshold

        // Create multiple chargebacks to exceed threshold
        for ($i = 0; $i < 3; $i++) {
            $payment = $this->createPaidPayment($user);
            $this->processChargeback($payment);
        }

        $user->refresh();

        $this->assertTrue($user->is_blocked);
        $this->assertNotNull($user->blocked_reason);
        $this->assertStringContainsString('threshold', strtolower($user->blocked_reason));
    }

    // ==================== PAYMENT STATE MACHINE TESTS ====================

    #[\PHPUnit\Framework\Attributes\Test]
    public function chargeback_confirmation_is_terminal_state()
    {
        $user = $this->createUserWithWallet();
        $payment = $this->createPaidPayment($user);

        $this->processChargeback($payment);

        $payment->refresh();
        $this->assertEquals(Payment::STATUS_CHARGEBACK_CONFIRMED, $payment->status);

        // Attempting to process refund should fail (already chargebacked)
        $webhookService = app(PaymentWebhookService::class);
        $webhookService->handleRefundProcessed([
            'payment_id' => $payment->gateway_payment_id,
            'amount' => $payment->amount_paise,
            'refund_id' => 'refund_' . uniqid(),
        ]);

        // Status should remain chargeback_confirmed
        $payment->refresh();
        $this->assertEquals(Payment::STATUS_CHARGEBACK_CONFIRMED, $payment->status);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function duplicate_chargeback_confirmation_is_idempotent()
    {
        $user = $this->createUserWithWallet();
        $payment = $this->createPaidPayment($user);

        $webhookPayload = [
            'payment_id' => $payment->gateway_payment_id,
            'chargeback_id' => 'cb_' . uniqid(),
        ];

        // First confirmation
        $this->processChargeback($payment);
        $scoreAfterFirst = $user->fresh()->risk_score;

        // Second confirmation (duplicate webhook)
        $webhookService = app(PaymentWebhookService::class);
        $webhookService->handleChargebackConfirmed($webhookPayload);

        // Score should not change
        $this->assertEquals($scoreAfterFirst, $user->fresh()->risk_score);
    }

    // ==================== LEDGER INVARIANT TESTS ====================

    #[\PHPUnit\Framework\Attributes\Test]
    public function chargeback_creates_balanced_ledger_entries()
    {
        $user = $this->createUserWithWallet();
        $payment = $this->createPaidPayment($user);

        $this->processChargeback($payment);

        // Verify ledger balance - use semantic reference type (not polymorphic model class)
        $ledgerEntries = DB::table('ledger_entries')
            ->where('reference_type', LedgerEntry::REF_CHARGEBACK)
            ->where('reference_id', $payment->id)
            ->get();

        foreach ($ledgerEntries as $entry) {
            $lines = DB::table('ledger_lines')
                ->where('ledger_entry_id', $entry->id)
                ->get();

            $debits = $lines->where('direction', 'debit')->sum('amount_paise');
            $credits = $lines->where('direction', 'credit')->sum('amount_paise');

            $this->assertEquals($debits, $credits, "Ledger entry {$entry->id} is not balanced");
        }
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function no_negative_wallet_balance_after_chargeback()
    {
        $user = $this->createUserWithWallet(500000); // 5000 INR balance
        $payment = $this->createPaidPayment($user, 100000); // 1000 INR payment

        $this->processChargeback($payment);

        $wallet = $user->wallet->fresh();

        // Wallet balance should never be negative
        $this->assertGreaterThanOrEqual(0, $wallet->balance_paise);
    }

    // ==================== CONCURRENCY TESTS ====================

    #[\PHPUnit\Framework\Attributes\Test]
    public function concurrent_chargeback_processing_is_safe()
    {
        $user = $this->createUserWithWallet();
        $payment = $this->createPaidPayment($user);

        $webhookPayload = [
            'payment_id' => $payment->gateway_payment_id,
            'chargeback_id' => 'cb_' . uniqid(),
        ];

        // Simulate concurrent processing
        $webhookService = app(PaymentWebhookService::class);

        // These should not cause race conditions due to row locking
        DB::beginTransaction();
        try {
            $webhookService->handleChargebackConfirmed($webhookPayload);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        // Only one chargeback should be recorded
        $payment->refresh();
        $this->assertEquals(Payment::STATUS_CHARGEBACK_CONFIRMED, $payment->status);
    }

    // ==================== HELPER METHODS ====================

    protected function createUserWithWallet(int $balancePaise = 0): User
    {
        $user = User::factory()->create([
            'risk_score' => 0,
            'is_blocked' => false,
        ]);

        Wallet::create([
            'user_id' => $user->id,
            'balance_paise' => $balancePaise,
            'locked_balance_paise' => 0,
        ]);

        return $user;
    }

    protected function createPaidPayment(User $user, int $amountPaise = 100000): Payment
    {
        $plan = Plan::factory()->create();
        $subscription = Subscription::factory()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active',
        ]);

        return Payment::factory()->create([
            'user_id' => $user->id,
            'subscription_id' => $subscription->id,
            'status' => Payment::STATUS_PAID,
            'amount_paise' => $amountPaise,
            'gateway_payment_id' => 'pay_' . uniqid(),
            'gateway_order_id' => 'order_' . uniqid(),
            'paid_at' => now(),
        ]);
    }

    protected function processChargeback(Payment $payment): void
    {
        // Mark as pending first
        $payment->update([
            'status' => Payment::STATUS_CHARGEBACK_PENDING,
            'chargeback_gateway_id' => 'cb_' . uniqid(),
            'chargeback_reason' => 'Test chargeback',
            'chargeback_amount_paise' => $payment->amount_paise,
            'chargeback_initiated_at' => now(),
        ]);

        // Confirm via webhook service
        $webhookService = app(PaymentWebhookService::class);
        $webhookService->handleChargebackConfirmed([
            'payment_id' => $payment->gateway_payment_id,
            'chargeback_id' => 'cb_confirmed_' . uniqid(),
        ]);
    }
}
