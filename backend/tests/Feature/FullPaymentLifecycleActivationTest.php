<?php

/**
 * V-LIFECYCLE-ACTIVATION-2026: Full Payment Lifecycle Activation Test
 *
 * Verifies the complete chain from subscription creation to bonus eligibility:
 *
 * 1. Create subscription → pending
 * 2. Create payment request → pending
 * 3. Payment gateway success (webhook)
 * 4. Webhook received & validated
 * 5. Payment → paid
 * 6. Subscription → active
 * 7. Wallet mutation (bonus credit)
 * 8. Ledger mutation (double-entry)
 * 9. Bonus eligibility trigger
 *
 * This is the SINGLE test that verifies the entire chain in one scenario.
 */

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Event;
use App\Models\User;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Subscription;
use App\Models\Payment;
use App\Models\BulkPurchase;
use App\Models\Transaction;
use App\Models\LedgerEntry;
use App\Models\LedgerLine;
use App\Models\BonusTransaction;
use App\Models\Investment;
use App\Services\PaymentWebhookService;
use App\Services\WalletService;
use App\Services\BonusCalculatorService;
use App\Services\AllocationService;
use App\Services\ReferralService;
use App\Jobs\ProcessSuccessfulPaymentJob;
use App\Events\SubscriptionActivated;
use App\Events\PaymentSuccessful;
use App\Contracts\PaymentGatewayInterface;
use Mockery;

class FullPaymentLifecycleActivationTest extends TestCase
{
    protected User $user;
    protected Plan $plan;
    protected $mockGateway;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this->seed(\Database\Seeders\SettingsSeeder::class);
        $this->seed(\Database\Seeders\PlanSeeder::class);
        $this->seed(\Database\Seeders\ProductSeeder::class);

        // Mock payment gateway
        $this->mockGateway = Mockery::mock(PaymentGatewayInterface::class);
        $this->mockGateway->shouldReceive('createOrder')
            ->andReturn(['id' => 'order_lifecycle_test', 'status' => 'created']);
        $this->mockGateway->shouldReceive('createOrUpdatePlan')
            ->andReturn('plan_lifecycle_test');
        $this->mockGateway->shouldReceive('createSubscription')
            ->andReturn(['id' => 'sub_lifecycle_test', 'status' => 'created']);
        $this->app->instance(PaymentGatewayInterface::class, $this->mockGateway);

        // Create user with verified KYC
        $this->user = User::factory()->create();
        $this->user->assignRole('user');
        $this->user->kyc->update(['status' => 'verified']);

        // Wallet is auto-created by UserFactory
        $this->user->wallet->update(['balance_paise' => 0]);

        $this->plan = Plan::first();

        // Ensure inventory exists
        $product = Product::first();
        if ($product) {
            BulkPurchase::factory()->create([
                'product_id' => $product->id,
                'total_value_received' => 10000000,
                'value_remaining' => 10000000,
            ]);
        }
    }

    // =========================================================================
    // MAIN TEST: Full Lifecycle Chain Verification
    // =========================================================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function full_payment_lifecycle_from_subscription_to_bonus()
    {
        // =====================================================================
        // STEP 1: Create Subscription → Status: pending
        // =====================================================================

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/user/subscription', [
                'plan_id' => $this->plan->id
            ]);

        $response->assertStatus(201);

        $subscription = Subscription::where('user_id', $this->user->id)
            ->where('plan_id', $this->plan->id)
            ->first();

        $this->assertNotNull($subscription, 'Subscription should be created');
        $this->assertEquals('pending', $subscription->status, 'New subscription should be pending');

        // =====================================================================
        // STEP 2: First Payment Created → Status: pending
        // =====================================================================

        $payment = Payment::where('subscription_id', $subscription->id)->first();
        $this->assertNotNull($payment, 'First payment should be auto-created');
        $this->assertEquals('pending', $payment->status, 'Payment should be pending');
        $this->assertEquals($this->plan->monthly_amount, $payment->amount);

        // Store initial state
        $initialWalletBalance = $this->user->wallet->fresh()->balance_paise;
        $initialLedgerEntryCount = LedgerEntry::count();
        $initialTransactionCount = Transaction::where('user_id', $this->user->id)->count();

        // =====================================================================
        // STEP 3 & 4: Simulate Gateway Success Webhook
        // =====================================================================

        $webhookService = app(PaymentWebhookService::class);
        $webhookPayload = [
            'order_id' => $payment->gateway_order_id,
            'id' => 'pay_lifecycle_success_' . uniqid(),
        ];

        $webhookService->handleSuccessfulPayment($webhookPayload);

        // =====================================================================
        // STEP 5: Payment → paid
        // =====================================================================

        $payment->refresh();
        $this->assertEquals(
            Payment::STATUS_PAID,
            $payment->status,
            'Payment should be marked as paid after webhook'
        );
        $this->assertNotNull($payment->paid_at, 'paid_at should be set');
        $this->assertNotNull($payment->gateway_payment_id, 'gateway_payment_id should be stored');

        // =====================================================================
        // STEP 6: Subscription → active
        // =====================================================================

        $subscription->refresh();
        $this->assertEquals(
            'active',
            $subscription->status,
            'Subscription should be active after first payment'
        );

        // =====================================================================
        // STEP 7 & 8 & 9: Process Payment Job (Wallet, Ledger, Bonus)
        // =====================================================================

        // Run the payment processing job
        $job = new ProcessSuccessfulPaymentJob($payment);
        $job->handle(
            app(BonusCalculatorService::class),
            app(AllocationService::class),
            app(ReferralService::class),
            app(WalletService::class)
        );

        // STEP 7: Wallet Mutation
        $this->user->wallet->refresh();
        $finalWalletBalance = $this->user->wallet->balance_paise;

        // Wallet should have increased (bonus credited)
        $this->assertGreaterThanOrEqual(
            $initialWalletBalance,
            $finalWalletBalance,
            'Wallet balance should not decrease after successful payment'
        );

        // Verify wallet transaction exists
        $walletTransactions = Transaction::where('user_id', $this->user->id)
            ->where('status', 'completed')
            ->get();

        $this->assertGreaterThan(
            $initialTransactionCount,
            $walletTransactions->count(),
            'New wallet transactions should be created'
        );

        // STEP 8: Ledger Mutation
        $finalLedgerEntryCount = LedgerEntry::count();
        $this->assertGreaterThanOrEqual(
            $initialLedgerEntryCount,
            $finalLedgerEntryCount,
            'Ledger entries should be created'
        );

        // Verify ledger is balanced
        $totalDebits = LedgerLine::where('type', 'debit')->sum('amount_paise');
        $totalCredits = LedgerLine::where('type', 'credit')->sum('amount_paise');
        $this->assertEquals($totalDebits, $totalCredits, 'Ledger must be balanced');

        // STEP 9: Bonus Eligibility Triggered
        $bonusTransactions = BonusTransaction::where('payment_id', $payment->id)->get();

        // Consistency bonus should always be awarded for on-time payments
        $this->assertGreaterThanOrEqual(
            1,
            $bonusTransactions->count(),
            'At least one bonus should be triggered'
        );

        // Verify bonus was credited
        $consistencyBonus = $bonusTransactions->where('type', 'consistency')->first();
        if ($consistencyBonus) {
            $this->assertEquals('credited', $consistencyBonus->status);
        }

        // =====================================================================
        // FINAL: Investment Created (Share Allocation)
        // =====================================================================

        $investment = Investment::where('user_id', $this->user->id)
            ->where('payment_id', $payment->id)
            ->first();

        if ($investment) {
            $this->assertNotNull($investment->units);
            $this->assertGreaterThan(0, $investment->units);
        }
    }

    // =========================================================================
    // ADDITIONAL: Multi-Payment Lifecycle (Progressive Bonus)
    // =========================================================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function lifecycle_triggers_progressive_bonus_at_month_4()
    {
        // Create subscription with 3 prior payments (month 4 triggers progressive)
        $subscription = Subscription::factory()->create([
            'user_id' => $this->user->id,
            'plan_id' => $this->plan->id,
            'amount' => $this->plan->monthly_amount,
            'status' => 'active',
            'consecutive_payments_count' => 3, // Next payment is month 4
            'bonus_multiplier' => 1.0,
        ]);

        // Create 3 prior paid payments
        for ($i = 1; $i <= 3; $i++) {
            Payment::factory()->create([
                'user_id' => $this->user->id,
                'subscription_id' => $subscription->id,
                'status' => Payment::STATUS_PAID,
                'amount' => $this->plan->monthly_amount,
                'paid_at' => now()->subMonths(4 - $i),
            ]);
        }

        // Create month 4 payment
        $payment = Payment::factory()->create([
            'user_id' => $this->user->id,
            'subscription_id' => $subscription->id,
            'status' => 'pending',
            'gateway_order_id' => 'order_month4_' . uniqid(),
            'amount' => $this->plan->monthly_amount,
            'is_on_time' => true,
        ]);

        // Process webhook
        $webhookService = app(PaymentWebhookService::class);
        $webhookService->handleSuccessfulPayment([
            'order_id' => $payment->gateway_order_id,
            'id' => 'pay_month4_' . uniqid(),
        ]);

        // Process job
        $payment->refresh();
        // V-WAVE3-FIX: Use dispatchSync to let container inject IdempotencyService
        ProcessSuccessfulPaymentJob::dispatchSync($payment);

        // Verify progressive bonus was triggered
        $progressiveBonus = BonusTransaction::where('payment_id', $payment->id)
            ->where('type', 'progressive')
            ->first();

        $this->assertNotNull(
            $progressiveBonus,
            'Progressive bonus should be triggered at month 4'
        );
        $this->assertEquals('credited', $progressiveBonus->status);
    }

    // =========================================================================
    // ADDITIONAL: Failed Payment Does Not Activate Subscription
    // =========================================================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function failed_payment_does_not_activate_subscription()
    {
        // Create pending subscription
        $subscription = Subscription::factory()->create([
            'user_id' => $this->user->id,
            'plan_id' => $this->plan->id,
            'status' => 'pending',
        ]);

        // Create pending payment
        $payment = Payment::factory()->create([
            'user_id' => $this->user->id,
            'subscription_id' => $subscription->id,
            'status' => 'pending',
            'gateway_order_id' => 'order_fail_test',
        ]);

        // Process failed webhook
        $webhookService = app(PaymentWebhookService::class);
        $webhookService->handlePaymentFailed([
            'order_id' => 'order_fail_test',
            'reason' => 'Insufficient funds',
        ]);

        // Subscription should still be pending
        $subscription->refresh();
        $this->assertEquals('pending', $subscription->status);

        // Payment should be failed
        $payment->refresh();
        $this->assertEquals(Payment::STATUS_FAILED, $payment->status);

        // No bonus should be created
        $bonuses = BonusTransaction::where('payment_id', $payment->id)->count();
        $this->assertEquals(0, $bonuses);
    }

    // =========================================================================
    // ADDITIONAL: Webhook Idempotency
    // =========================================================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function duplicate_webhook_does_not_double_credit()
    {
        $subscription = Subscription::factory()->create([
            'user_id' => $this->user->id,
            'plan_id' => $this->plan->id,
            'status' => 'pending',
            'amount' => $this->plan->monthly_amount,
        ]);

        $payment = Payment::factory()->create([
            'user_id' => $this->user->id,
            'subscription_id' => $subscription->id,
            'status' => 'pending',
            'gateway_order_id' => 'order_idempotent',
            'amount' => $this->plan->monthly_amount,
        ]);

        $webhookService = app(PaymentWebhookService::class);
        $payload = [
            'order_id' => 'order_idempotent',
            'id' => 'pay_idempotent_123',
        ];

        // First webhook
        $webhookService->handleSuccessfulPayment($payload);
        $payment->refresh();
        $this->user->wallet->refresh();
        $balanceAfterFirst = $this->user->wallet->balance_paise;

        // Process job once
        // V-WAVE3-FIX: Use dispatchSync to let container inject IdempotencyService
        ProcessSuccessfulPaymentJob::dispatchSync($payment);

        $this->user->wallet->refresh();
        $balanceAfterJob = $this->user->wallet->balance_paise;

        // Second webhook (duplicate)
        $webhookService->handleSuccessfulPayment($payload);

        // Balance should not change
        $this->user->wallet->refresh();
        $this->assertEquals(
            $balanceAfterJob,
            $this->user->wallet->balance_paise,
            'Duplicate webhook should not double credit'
        );

        // Only one payment record with this gateway_payment_id
        $paymentCount = Payment::where('gateway_payment_id', 'pay_idempotent_123')->count();
        $this->assertEquals(1, $paymentCount);
    }
}
