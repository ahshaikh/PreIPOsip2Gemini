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
	// $this->markTestSkipped('Skipping until production SIP lifecycle is implemented.');

        // 1. Setup: Create all the needed models
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('user');
        $user->kyc->update(['status' => 'verified']); // Pre-verify KYC
        
        $plan = Plan::factory()->create(['monthly_amount' => 1000]);
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

        // 6. Act 3: Now, *actually run* the job
        // (We must resolve dependencies from the container)
        $job = new ProcessSuccessfulPaymentJob($payment);
        app()->call([$job, 'handle']);

        // 7. Assert: Check the final database state (The Payoff)
        // Bonus created?
        $this->assertDatabaseHas('bonus_transactions', [
            'user_id' => $user->id,
            'payment_id' => $payment->id,
            'type' => 'consistency' // The 1st bonus to be created
        ]);
        
        // Share allocated? (1000 base + 10 consistency = 1010 value)
        // 1010 value / 100 face_value = 10.1 units
        $this->assertDatabaseHas('user_investments', [
            'user_id' => $user->id,
            'payment_id' => $payment->id,
            'value_allocated' => 1010,
            'units_allocated' => 10.1
        ]);
        
        // Inventory deducted?
        $this->assertDatabaseHas('bulk_purchases', [
            'id' => $purchase->id,
            'value_remaining' => 1000000 - 1010 // 998990
        ]);
    }
}
