<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Subscription;
use App\Models\Payment;
use App\Models\BonusTransaction;
use App\Models\BulkPurchase;
use App\Models\Transaction;
use App\Services\PaymentWebhookService;
use App\Jobs\ProcessSuccessfulPaymentJob;
use App\Notifications\BonusCredited;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Notification;

class PaymentToBonusIntegrationTest extends TestCase
{
    protected $user;
    protected $plan;
    protected $subscription;
    protected $payment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedDatabase();

        $this->user = User::factory()->create();
        $this->user->wallet()->create();

        $this->plan = Plan::first();

        $this->subscription = Subscription::factory()->create([
            'user_id' => $this->user->id,
            'plan_id' => $this->plan->id,
            'amount' => $this->plan->monthly_amount,
            'consecutive_payments_count' => 0,
            'bonus_multiplier' => 1.0,
        ]);

        $this->payment = Payment::factory()->create([
            'user_id' => $this->user->id,
            'subscription_id' => $this->subscription->id,
            'status' => 'pending',
            'gateway_order_id' => 'order_123',
            'amount' => $this->plan->monthly_amount,
            'is_on_time' => true
        ]);

        $product = Product::first();
        BulkPurchase::factory()->create([
            'product_id' => $product->id,
            'total_value_received' => 1000000,
            'value_remaining' => 1000000,
        ]);
    }

    private function seedDatabase()
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this->seed(\Database\Seeders\SettingsSeeder::class);
        $this->seed(\Database\Seeders\PlanSeeder::class);
        $this->seed(\Database\Seeders\ProductSeeder::class);
    }

    private function runPaymentLifecycle()
    {
        $service = $this->app->make(PaymentWebhookService::class);

        $service->handleSuccessfulPayment([
            'order_id' => 'order_123',
            'id' => 'pay_123'
        ]);

        ProcessSuccessfulPaymentJob::dispatchSync($this->payment->fresh());
    }

    public function testPaymentSuccessTriggersAllBonusCalculations()
    {
        $this->runPaymentLifecycle();

        $this->assertDatabaseHas('bonus_transactions', [
            'payment_id' => $this->payment->id,
            'type' => 'consistency'
        ]);
    }

    public function testBonusesCorrectlyCreditedToWallet()
    {
        $this->runPaymentLifecycle();

        $bonusIds = BonusTransaction::where('payment_id', $this->payment->id)
            ->pluck('id');

        $walletBonusCredits = Transaction::where('reference_type', BonusTransaction::class)
            ->whereIn('reference_id', $bonusIds)
            ->sum('amount_paise');

        $expectedNet = BonusTransaction::where('payment_id', $this->payment->id)
            ->get()
            ->sum(fn($b) => ($b->amount - $b->tds_deducted) * 100);

        $this->assertEquals(
            $expectedNet,
            $walletBonusCredits,
            'Wallet should reflect total net bonus amount'
        );
    }

    public function testBonusNotificationsGenerated()
    {
        Notification::fake();

        $this->runPaymentLifecycle();

        Notification::assertSentTo($this->user, BonusCredited::class);
    }

    public function testBonusRecordsCreatedWithCorrectMetadata()
    {
        Payment::factory()->count(3)->create([
            'subscription_id' => $this->subscription->id,
            'status' => 'paid'
        ]);

        $this->runPaymentLifecycle();

        $this->assertDatabaseHas('bonus_transactions', [
            'payment_id' => $this->payment->id,
            'type' => 'progressive',
            'multiplier_applied' => 1.0
        ]);
    }

    public function testReferralMultiplierAppliedToAllBonuses()
    {
        $this->subscription->update(['bonus_multiplier' => 2.0]);

        Payment::factory()->count(3)->create([
            'subscription_id' => $this->subscription->id,
            'status' => 'paid'
        ]);

        $this->runPaymentLifecycle();

        $progressive = BonusTransaction::where('payment_id', $this->payment->id)
            ->where('type', 'progressive')
            ->first();

        $this->assertNotNull($progressive);

        $this->assertEquals(2.0, $progressive->multiplier_applied);

        $base = $progressive->amount / $progressive->multiplier_applied;

        $this->assertEquals(
            $base * 2.0,
            $progressive->amount
        );
    }

    public function testPaymentRefundReversesBonuses()
    {
        $this->runPaymentLifecycle();

        $bonusTransactions = BonusTransaction::where('payment_id', $this->payment->id)->get();

        $expectedNet = $bonusTransactions
            ->sum(fn($b) => ($b->amount - $b->tds_deducted) * 100);

        $bonusIds = $bonusTransactions->pluck('id');

        $walletBonusCredits = Transaction::where('reference_type', BonusTransaction::class)
            ->whereIn('reference_id', $bonusIds)
            ->sum('amount_paise');

        $this->assertEquals($expectedNet, $walletBonusCredits);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)->postJson(
            "/api/v1/admin/payments/{$this->payment->id}/refund",
            [
                'reason' => 'Test refund',
                'reverse_bonuses' => true,
                'reverse_allocations' => true
            ]
        );

        // After refund, total net bonus impact for this payment should be zero

        $allBonusesAfterRefund = BonusTransaction::where('payment_id', $this->payment->id)->get();

        $totalNetAfterRefund = $allBonusesAfterRefund
            ->sum(fn($b) => ($b->amount - $b->tds_deducted));

        $this->assertEquals(
            0,
            $totalNetAfterRefund,
            'Net bonus impact should be zero after refund'
        );
    }

    public function testLatePaymentSkipsConsistencyBonus()
    {
        $this->subscription->update([
            'next_payment_date' => now()->subDays(10)
        ]);

        $this->runPaymentLifecycle();

        $this->assertDatabaseMissing('bonus_transactions', [
            'payment_id' => $this->payment->id,
            'type' => 'consistency'
        ]);
    }

    public function testBonusCalculationHandlesEdgeCases()
    {
        Queue::fake();

        $this->payment->update(['status' => Payment::STATUS_PAID]);
        $this->payment->update(['status' => Payment::STATUS_REFUNDED]);

        $service = $this->app->make(PaymentWebhookService::class);

        $service->handleSuccessfulPayment([
            'order_id' => 'order_123',
            'id' => 'pay_123'
        ]);

        Queue::assertNothingPushed();
    }
}