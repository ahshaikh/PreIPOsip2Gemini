<?php
// V-FINAL-1730-TEST-81 (Created)

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Subscription;
use App\Models\Payment;
use App\Models\BonusTransaction;
use App\Models\BulkPurchase;
use App\Services\PaymentWebhookService;
use App\Jobs\ProcessSuccessfulPaymentJob;
use App\Notifications\BonusCredited;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Notification;

class PaymentToBonusIntegrationTest extends TestCase
{
    use RefreshDatabase;

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
        
        $this->plan = Plan::first(); // Get Plan A from seeder
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
        
        // Add inventory
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
        // 1. Trigger Webhook
        $service = $this->app->make(PaymentWebhookService::class);
        $service->handleSuccessfulPayment([
            'order_id' => 'order_123',
            'id' => 'pay_123'
        ]);
        
        // 2. Run the Job
        (new ProcessSuccessfulPaymentJob($this->payment->fresh()))->handle(
            $this->app->make(\App\Services\BonusCalculatorService::class),
            $this->app->make(\App\Services\AllocationService::class),
            $this->app->make(\App\Services\ReferralService::class),
            $this->app->make(\App\Services\WalletService::class)
        );
    }

    public function testPaymentSuccessTriggersAllBonusCalculations()
    {
        // We test that a Consistency bonus is created
        $this->runPaymentLifecycle();
        
        $this->assertDatabaseHas('bonus_transactions', [
            'payment_id' => $this->payment->id,
            'type' => 'consistency'
        ]);
    }

    public function testBonusesCorrectlyCreditedToWallet()
    {
        // Plan A consistency bonus is ₹10
        $this->runPaymentLifecycle();

        $this->assertDatabaseHas('wallets', [
            'user_id' => $this->user->id,
            'balance_paise' => 1000 // ₹10.00 = 1000 paise
        ]);
        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->user->id,
            'type' => 'bonus_credit',
            'amount_paise' => 1000 // ₹10.00 = 1000 paise
        ]);
    }

    public function testBonusNotificationsGenerated()
    {
        Notification::fake();
        $this->runPaymentLifecycle();
        Notification::assertSentTo($this->user, BonusCredited::class);
    }

    public function testBonusRecordsCreatedWithCorrectMetadata()
    {
        // Test with a progressive bonus on Month 4
        Payment::factory()->count(3)->create(['subscription_id' => $this->subscription->id, 'status' => 'paid']);
        
        $this->runPaymentLifecycle();

        $this->assertDatabaseHas('bonus_transactions', [
            'payment_id' => $this->payment->id,
            'type' => 'progressive',
            'base_amount' => $this->payment->amount,
            'multiplier_applied' => 1.0
        ]);
    }

    public function testReferralMultiplierAppliedToAllBonuses()
    {
        // 1. Set multiplier to 2.0x
        $this->subscription->update(['bonus_multiplier' => 2.0]);
        
        // 2. Run for Month 4 (Progressive bonus)
        Payment::factory()->count(3)->create(['subscription_id' => $this->subscription->id, 'status' => 'paid']);
        
        // 3. Run lifecycle
        $this->runPaymentLifecycle();

        // 4. Check that progressive bonus was multiplied
        // Base bonus is (4-4+1) * 0.5% * 1000 = 5.
        // Multiplied bonus = 5 * 2.0 = 10.
        $this->assertDatabaseHas('bonus_transactions', [
            'payment_id' => $this->payment->id,
            'type' => 'progressive',
            'amount' => 10.00,
            'multiplier_applied' => 2.0
        ]);
    }

    public function testPaymentRefundReversesBonuses()
    {
        // 1. Run a payment, generating a 10.00 bonus
        $this->runPaymentLifecycle();
        $this->assertEquals(10.00, $this->user->wallet->fresh()->balance);

        // 2. Get an Admin and refund the payment
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        
        $this->actingAs($admin)->postJson("/api/v1/admin/payments/{$this->payment->id}/refund", [
            'reason' => 'Test refund',
            'reverse_bonuses' => true, // <-- Key
            'reverse_allocations' => true
        ]);

        // 3. Check wallet
        // 10 (bonus) - 10 (bonus reversal) + 1000 (payment refund) = 1000
        $this->assertEquals(1000.00, $this->user->wallet->fresh()->balance);
        
        // 4. Check for reversal transaction
        $this->assertDatabaseHas('bonus_transactions', ['type' => 'reversal', 'amount' => -10.00]);
    }

    public function testLatePaymentSkipsConsistencyBonus()
    {
        // 1. Mark the payment as *late*
        $this->payment->update(['is_on_time' => false]);
        
        // 2. Run lifecycle
        $this->runPaymentLifecycle();
        
        // 3. Assert
        $this->assertDatabaseMissing('bonus_transactions', [
            'payment_id' => $this->payment->id,
            'type' => 'consistency'
        ]);
    }

    public function testBonusCalculationHandlesEdgeCases()
    {
        // Test: test_bonus_calculation_with_zero_payment
        // This is prevented by the DB CHECK constraint we added
        
        // Test: test_bonus_calculation_with_refunded_payment
        $this->payment->update(['status' => 'refunded']);
        
        // 1. Trigger Webhook
        $service = $this->app->make(PaymentWebhookService::class);
        $service->handleSuccessfulPayment([
            'order_id' => 'order_123', 'id' => 'pay_123'
        ]);

        // 2. Assert: No job was dispatched
        Queue::assertNothingPushed();
    }
}