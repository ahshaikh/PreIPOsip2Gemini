<?php
// V-FINAL-1730-315

namespace Tests\Feature;

use Tests\FeatureTestCase;
use App\Models\User;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Subscription;
use App\Models\Payment;
use App\Models\BulkPurchase;
use App\Services\PaymentWebhookService;
use App\Jobs\ProcessSuccessfulPaymentJob;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;

class PaymentLifecycleTest extends FeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Seed core data
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this->seed(\Database\Seeders\SettingsSeeder::class);
    }

    /**
     * Test: Register > KYC > Buy Plan > Pay > Verify Bonus/Allocation
     */
    public function test_full_payment_lifecycle_succeeds()
    {
        // $this->markTestSkipped('V-REFACTOR-2026: Complex multi-job integration test requires orchestration fix for async bonus processing.');

        // 1. Setup: Create all the needed models
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('user');
        $user->kyc->update(['status' => 'verified']); // Pre-verify KYC
        
        $plan = Plan::factory()->create(['monthly_amount' => 1000]);
        // Add bonus configs required for consistency bonus
        $plan->configs()->createMany([
            ['config_key' => 'consistency_config', 'value' => ['amount_per_payment' => 10]],
        ]);
        $product = Product::factory()->create(['face_value_per_unit' => 100]);

        // Add 1,000,000 in inventory
        $purchase = BulkPurchase::factory()->create([
            'product_id' => $product->id,
            'total_value_received' => 1000000,
            'value_remaining' => 1000000,
        ]);

        // 2. Act 1: User subscribes (creates a 'pending' payment)
        $this->actingAs($user);
        $this->postJson('/api/v1/user/subscription', ['plan_id' => $plan->id]);

        $payment = Payment::first();
        $this->assertEquals('pending', $payment->status);
        $this->assertEquals(1000, $payment->amount);

        // 3. Act 2: Simulate Razorpay Webhook
        Queue::fake(); // Tell Laravel to hold jobs
        
        $service = $this->app->make(PaymentWebhookService::class);
        $service->handleSuccessfulPayment([
            'order_id' => $payment->gateway_order_id, // This is null, but logic handles it
            'id' => 'pay_mock_12345'
        ]);

        // 4. Assert: Check that the payment was marked 'paid'
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'paid',
            'gateway_payment_id' => 'pay_mock_12345'
        ]);

        // 5. Assert: Check that the main Job was pushed to the queue
        Queue::assertPushed(ProcessSuccessfulPaymentJob::class);

        // CRITICAL: Refresh payment model to get updated status from database
        // Without this, the job sees 'pending' status and skips processing
        $payment->refresh();

        // 6. Act 3: Now, *actually run* the job
        // (We must resolve dependencies from the container)
        $job = new ProcessSuccessfulPaymentJob($payment);
        app()->call([$job, 'handle']);

        // 6b. Also run the ProcessPaymentBonusJob (it was dispatched inside the main job)
        $bonusJob = new \App\Jobs\ProcessPaymentBonusJob($payment);
        app()->call([$bonusJob, 'handle']);

        // 7. Assert: Check the final database state (The Payoff)
        // Bonus created?
        $this->assertDatabaseHas('bonus_transactions', [
            'user_id' => $user->id,
            'payment_id' => $payment->id,
            'type' => 'consistency' // The 1st bonus to be created
        ]);

        // V-WALLET-FIRST-2026: Verify wallet is credited (no auto-investment)
        // The new architecture credits payment + bonus to wallet.
        // User must manually click "Buy Shares" to create investments.
        $user->wallet->refresh();
        $expectedWalletPaise = 100000 + 1000; // 1000 payment + 10 bonus (in paise)
        $this->assertGreaterThanOrEqual($expectedWalletPaise, $user->wallet->balance_paise,
            'Wallet should be credited with payment + bonus');

        // Note: user_investments and bulk_purchase deduction would only happen
        // after user manually triggers "Buy Shares" action, which is a separate flow.
    }
}
