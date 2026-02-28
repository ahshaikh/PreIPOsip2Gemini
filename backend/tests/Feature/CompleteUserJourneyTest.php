<?php
// V-FINAL-1730-TEST-92 (Created)

namespace Tests\Feature;

use Tests\FeatureTestCase;
use App\Models\User;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Subscription;
use App\Models\Payment;
use App\Models\BonusTransaction;
use App\Models\BulkPurchase;
use App\Models\UserKyc;
use App\Models\Withdrawal;
use App\Models\Referral;
use App\Models\LuckyDraw;
use App\Models\LuckyDrawEntry;
use App\Models\ProfitShare;
use App\Models\UserProfile;
use App\Services\PaymentWebhookService;
use App\Services\WalletService;
use App\Services\BonusCalculatorService;
use App\Services\AllocationService;
use App\Services\ReferralService;
use App\Services\InventoryService;
use App\Jobs\ProcessSuccessfulPaymentJob;
use App\Jobs\ProcessReferralJob;
use App\Jobs\GenerateLuckyDrawEntryJob;
use App\Jobs\RetryAutoDebitJob;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Carbon;
use Illuminate\Http\UploadedFile;

class CompleteUserJourneyTest extends FeatureTestCase
{
    protected $admin;
    protected $user;
    protected $plan;
    protected $product;
    protected $subscription;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed the core data
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this->seed(\Database\Seeders\SettingsSeeder::class);
        // ProductSeeder is now self-contained - no UserSeeder coupling required
        $this->seed(\Database\Seeders\PlanSeeder::class);
        $this->seed(\Database\Seeders\ProductSeeder::class);

        // Setup shared models
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->user = User::factory()->create();
        $this->user->assignRole('user');
        $this->user->wallet()->create(['balance_paise' => 0, 'locked_balance_paise' => 0]);

        $this->plan = Plan::first();
        $this->product = Product::first();

        // Create a subscription for tests that need it
        $this->subscription = Subscription::factory()->create([
            'user_id' => $this->user->id,
            'plan_id' => $this->plan->id,
            'status' => 'active',
        ]);

        // Add inventory
        BulkPurchase::factory()->create([
            'product_id' => $this->product->id,
            'total_value_received' => 1000000,
            'value_remaining' => 1000000,
        ]);
    }

    /**
     * Helper to run the full webhook->job pipeline
     */
    private function processPayment(Payment $payment)
    {
        // 1. Trigger Webhook
        $webhookService = $this->app->make(PaymentWebhookService::class);
        $webhookService->handleSuccessfulPayment([
            'order_id' => $payment->gateway_order_id,
            'id' => 'pay_' . \Illuminate\Support\Str::random(10)
        ]);
        
        // 2. Run the Queued Job
        // V-WAVE3-FIX: Use dispatchSync to let container inject IdempotencyService
        ProcessSuccessfulPaymentJob::dispatchSync($payment->fresh());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function testRegistrationToFirstPaymentFlow()
    {
        Queue::fake(); // Prevent jobs from running automatically
        
        // 1. Register (Fails, user must be active)
        $this->user->update(['status' => 'pending']); // Simulate pending OTP
        $this->user->kyc->update(['status' => 'verified']); // Pre-verify KYC
        
        // 2. Activate (Simulate OTP)
        $this->user->update(['status' => 'active']);

        // 3. Subscribe
        $response = $this->actingAs($this->user)->postJson('/api/v1/user/subscription', [
            'plan_id' => $this->plan->id
        ]);
        $response->assertStatus(201);
        
        // 4. Get the Pending Payment
        $payment = Payment::first();
        $this->assertEquals('pending', $payment->status);

        // 5. Simulate Payment Success
        $this->processPayment($payment);

        // 6. Assert Final State
        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'paid']);
        $this->assertDatabaseHas('user_investments', ['payment_id' => $payment->id]);
        $this->assertDatabaseHas('bonus_transactions', ['payment_id' => $payment->id, 'type' => 'consistency']);
        $this->assertGreaterThan(0, $this->user->wallet->fresh()->balance);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function testKycSubmissionToApprovalFlow()
    {
        // 1. Submit KYC
        $response = $this->actingAs($this->user)->postJson('/api/v1/user/kyc', [
            'pan_number' => 'ABCDE1234F',
            'aadhaar_number' => '123456789012',
            'demat_account' => '12345678',
            'bank_account' => '0987654321',
            'bank_ifsc' => 'HDFC0001234',
            'bank_name' => 'HDFC Bank',
            'pan' => UploadedFile::fake()->image('pan.jpg'),
            'aadhaar_front' => UploadedFile::fake()->image('front.jpg'),
            'aadhaar_back' => UploadedFile::fake()->image('back.jpg'),
            'bank_proof' => UploadedFile::fake()->create('bank.pdf', 100),
            'demat_proof' => UploadedFile::fake()->create('demat.pdf', 100),
            'address_proof' => UploadedFile::fake()->create('address.pdf', 100),
            'photo' => UploadedFile::fake()->image('photo.jpg'),
            'signature' => UploadedFile::fake()->image('signature.jpg'),
        ]);
        $response->assertStatus(201);
        $this->assertEquals('processing', $this->user->kyc->fresh()->status);
        
        // 2. Admin Approves (Simulate auto-verify failure, manual approval)
        $kyc = $this->user->kyc->fresh();
        $kyc->update(['status' => 'submitted']); // Move to manual queue
        
        $response = $this->actingAs($this->admin)->postJson("/api/v1/admin/kyc-queue/{$kyc->id}/approve");
        $response->assertStatus(200);
        $this->assertEquals('verified', $kyc->fresh()->status);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function testSubscriptionPauseAndResumeFlow()
    {
        $sub = Subscription::factory()->create(['user_id' => $this->user->id, 'status' => 'active']);
        
        // 1. Pause
        $this->actingAs($this->user)->postJson("/api/v1/user/subscription/pause", ['months' => 2])
             ->assertStatus(200);
        $this->assertEquals('paused', $sub->fresh()->status);

        // 2. Resume
        $this->actingAs($this->user)->postJson("/api/v1/user/subscription/resume")
             ->assertStatus(200);
        $this->assertEquals('active', $sub->fresh()->status);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function testSubscriptionCancellationFlow()
    {
        $sub = Subscription::factory()->create(['user_id' => $this->user->id, 'status' => 'active']);
        
        $this->actingAs($this->user)->postJson("/api/v1/user/subscription/cancel", ['reason' => 'Test'])
             ->assertStatus(200);
             
        $this->assertEquals('cancelled', $sub->fresh()->status);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function testWithdrawalRequestToCompletionFlow()
    {
        $this->user->kyc->update(['status' => 'verified']);
        $this->user->wallet->update(['balance_paise' => 500000]); // ₹5000
        
        // 1. User requests
        $this->actingAs($this->user)->postJson('/api/v1/user/wallet/withdraw', [
            'amount' => 2000,
            'bank_details' => ['account' => '123', 'ifsc' => 'ABC']
        ]);
        $this->assertEquals(3000, $this->user->wallet->fresh()->balance);
        $this->assertEquals(2000, $this->user->wallet->fresh()->locked_balance);
        
        // 2. Admin approves
        $withdrawal = Withdrawal::first();
        $withdrawal->update(['status' => 'approved']); // Simulate auto-approve failure
        
        // 3. Admin completes
        $this->actingAs($this->admin)->postJson("/api/v1/admin/withdrawal-queue/{$withdrawal->id}/complete", ['utr_number' => 'UTR123']);
        
        $this->assertEquals(3000, $this->user->wallet->fresh()->balance);
        $this->assertEquals(0, $this->user->wallet->fresh()->locked_balance);
        $this->assertEquals('completed', $withdrawal->fresh()->status);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function testReferralBonusCompleteFlow()
    {
        $referrer = User::factory()->create();
        $referrer->kyc->update(['status' => 'verified']);
        $referrer->wallet()->create();
        $referrer->subscription = Subscription::factory()->create(['user_id' => $referrer->id]);

        $referee = $this->user;
        $referee->kyc->update(['status' => 'verified']);
        
        Referral::create(['referrer_id' => $referrer->id, 'referred_id' => $referee->id, 'status' => 'pending']);

        // Run the job
        (new ProcessReferralJob($referee))->handle(
            $this->app->make(ReferralService::class),
            $this->app->make(WalletService::class)
        );

        $this->assertEquals(500, $referrer->wallet->fresh()->balance); // Default bonus
        $this->assertDatabaseHas('bonus_transactions', ['user_id' => $referrer->id, 'type' => 'referral']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function testMilestoneBonusCompleteFlow()
    {
        // 1. Create 11 past paid payments
        Payment::factory()->count(11)->create([
            'subscription_id' => $this->subscription->id,
            'status' => 'paid'
        ]);
        // 2. Set streak to 11
        $this->subscription->update(['consecutive_payments_count' => 11]);

        // 3. Create the 12th payment
        $payment = Payment::factory()->create([
            'subscription_id' => $this->subscription->id,
            'status' => 'pending',
            'gateway_order_id' => 'order_milestone',
            'is_on_time' => true,
        ]);
        
        // 4. Process the 12th payment
        $this->processPayment($payment);

        // 5. Assert: Milestone bonus was created
        $this->assertDatabaseHas('bonus_transactions', [
            'payment_id' => $payment->id,
            'type' => 'milestone'
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function testProfitShareDistributionFlow()
    {
        $this->user->update(['created_at' => now()->subMonths(4)]); // Make eligible
        $period = ProfitShare::factory()->create(['status' => 'pending', 'total_pool' => 10000]);

        // 1. Calculate
        $this->actingAs($this->admin)->postJson("/api/v1/admin/profit-sharing/{$period->id}/calculate");
        $this->assertEquals('calculated', $period->fresh()->status);
        
        // 2. Distribute
        $this->actingAs($this->admin)->postJson("/api/v1/admin/profit-sharing/{$period->id}/distribute");
        
        $this->assertEquals('distributed', $period->fresh()->status);
        $this->assertGreaterThan(0, $this->user->wallet->fresh()->balance);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function testLuckyDrawEntryToWinnerFlow()
    {
        $draw = LuckyDraw::factory()->create(['status' => 'open', 'prize_structure' => [
            ['rank' => 1, 'count' => 1, 'amount' => 1000]
        ]]);
        $payment = Payment::factory()->create([
            'subscription_id' => $this->subscription->id,
            'is_on_time' => true,
        ]);
        
        // 1. Generate Entry
        (new GenerateLuckyDrawEntryJob($payment))->handle($this->app->make(\App\Services\LuckyDrawService::class));
        
        $this->assertDatabaseHas('lucky_draw_entries', ['user_id' => $this->user->id]);

        // 2. Execute Draw
        $this->actingAs($this->admin)->postJson("/api/v1/admin/lucky-draws/{$draw->id}/execute");

        // 3. Assert
        $this->assertEquals('completed', $draw->fresh()->status);
        $this->assertEquals(1000, $this->user->wallet->fresh()->balance);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function testAutoDebitFailureAndRetryFlow()
    {
        Queue::fake();
        // 1. Create a payment that is pending and has failed twice
        $payment = Payment::factory()->create([
            'subscription_id' => $this->subscription->id,
            'status' => 'pending',
            'retry_count' => 2
        ]);
        
        // 2. Manually run the retry job, simulating a *failure*
        $mockApi = $this->mock(Api::class); // Mock Razorpay
        $mockApi->shouldReceive('payment->createRecursion')->andThrow(new \Exception('Bank declined'));

        (new RetryAutoDebitJob($payment))->handle($this->app->make(AutoDebitService::class), $mockApi);

        // 3. Assert: retry_count incremented
        $this->assertEquals(3, $payment->fresh()->retry_count);
        // 4. Assert: Job was queued *again*
        Queue::assertPushed(RetryAutoDebitJob::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function testKycRejectionAndResubmissionFlow()
    {
        $kyc = $this->user->kyc;
        
        // 1. Submit
        $this->actingAs($this->user)->postJson('/api/v1/user/kyc', [
            'pan_number' => 'ABCDE1234F', /* ... all other fields ... */
            'pan' => UploadedFile::fake()->image('pan.jpg'), /* ... all other files ... */
        ]);
        $kyc->update(['status' => 'submitted']); // Simulate job moved to manual

        // 2. Reject
        $this->actingAs($this->admin)->postJson("/api/v1/admin/kyc-queue/{$kyc->id}/reject", ['reason' => 'Blurry photo']);
        $this->assertEquals('rejected', $kyc->fresh()->status);
        
        // 3. Resubmit
        $this->actingAs($this->user)->postJson('/api/v1/user/kyc', [
            'pan_number' => 'ABCDE1234F', /* ... */
            'pan' => UploadedFile::fake()->image('pan_new.jpg'), /* ... */
        ]);
        $kyc->fresh()->update(['status' => 'submitted']);
        
        // 4. Approve
        $this->actingAs($this->admin)->postJson("/api/v1/admin/kyc-queue/{$kyc->id}/approve");
        $this->assertEquals('verified', $kyc->fresh()->status);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function testCelebrationBonusOnBirthdayFlow()
    {
        // 1. Set user's birthday to today
        $this->user->profile->update(['dob' => now()->format('Y-m-d')]);
        Subscription::factory()->create(['user_id' => $this->user->id, 'plan_id' => $this->plan->id, 'status' => 'active']);
        
        // 2. Run the cron job
        $this->artisan('app:process-celebration-bonuses');
        
        // 3. Assert
        $this->assertDatabaseHas('bonus_transactions', [
            'user_id' => $this->user->id,
            'type' => 'celebration',
            'description' => 'Happy Birthday!'
        ]);
        $this->assertGreaterThan(0, $this->user->wallet->fresh()->balance);
    }
}
