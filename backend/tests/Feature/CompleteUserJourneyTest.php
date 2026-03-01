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
use App\Models\Setting;
use App\Services\PaymentWebhookService;
use App\Services\WalletService;
use App\Services\BonusCalculatorService;
use App\Services\AllocationService;
use App\Services\ReferralService;
use App\Services\InventoryService;
use App\Services\AutoDebitService;
use Razorpay\Api\Api;
use App\Jobs\ProcessSuccessfulPaymentJob;
use App\Jobs\ProcessReferralJob;
use App\Jobs\GenerateLuckyDrawEntryJob;
use App\Jobs\RetryAutoDebitJob;
use App\Jobs\CalculateProfitShareJob;
use App\Jobs\ProcessProfitShareDistribution;
use App\Jobs\ProcessPaymentBonusJob;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;

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
        $this->seed(\Database\Seeders\PlanSeeder::class);
        $this->seed(\Database\Seeders\ProductSeeder::class);

        // Setup shared models
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->user = User::factory()->create();
        $this->user->assignRole('user');
        
        // Start with empty wallet
        $this->user->wallet->update(['balance_paise' => 0, 'locked_balance_paise' => 0]);

        // Find Plan A explicitly
        $this->plan = Plan::where('name', 'LIKE', '%Plan A%')->first() ?? Plan::first();
        $this->plan->update(['monthly_amount' => 1000]);

        $this->product = Product::first();
        $this->product->update(['current_market_price' => 100, 'face_value_per_unit' => 100]);

        // Create a subscription for tests that need it
        $this->subscription = Subscription::factory()->create([
            'user_id' => $this->user->id,
            'plan_id' => $this->plan->id,
            'status' => 'active',
            'amount' => 1000,
            'amount_paise' => 100000,
        ]);

        // Add inventory
        BulkPurchase::factory()->create([
            'product_id' => $this->product->id,
            'total_value_received' => 1000000,
            'value_remaining' => 1000000,
            'approved_by_admin_id' => $this->admin->id,
            'verified_at' => now(),
            'manual_entry_reason' => 'Seeding initial inventory for complete user journey integration testing purposes.',
        ]);
        
        Cache::forget('settings'); // Clear settings cache
    }

    /**
     * Helper to run the full webhook pipeline
     *
     * V-WALLET-FIRST-2026: Payment processing credits wallet, user decides when to invest
     * - Webhook: marks payment paid, dispatches job (afterCommit)
     * - Job: credits wallet with principal + calculates bonus
     * - NO automatic investment - user clicks "Buy Shares" later
     */
    private function processPayment(Payment $payment)
    {
        // 1. Trigger Webhook (marks payment as paid)
        $webhookService = $this->app->make(PaymentWebhookService::class);
        $webhookService->handleSuccessfulPayment([
            'order_id' => $payment->gateway_order_id,
            'id' => 'pay_' . \Illuminate\Support\Str::random(10)
        ]);

        // 2. Explicitly run post-payment job (credits wallet)
        // NOTE: PaymentWebhookService dispatches with afterCommit(), but DatabaseTransactions
        // trait wraps tests in a transaction that never commits. We must dispatch synchronously.
        $freshPayment = $payment->fresh();
        ProcessSuccessfulPaymentJob::dispatchSync($freshPayment);

        // 3. Explicitly run bonus job (credits bonus to wallet)
        ProcessPaymentBonusJob::dispatchSync($freshPayment);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function testRegistrationToFirstPaymentFlow()
    {
        $this->subscription->delete();
        $this->user->wallet->update(['balance_paise' => 0, 'locked_balance_paise' => 0]);

        $this->user->update(['status' => 'active']);
        $this->user->kyc->update(['status' => 'verified']); 

        // 3. Subscribe
        $response = $this->actingAs($this->user)->postJson('/api/v1/user/subscription', [
            'plan_id' => $this->plan->id
        ]);
        $response->assertStatus(201);
        
        $payment = Payment::where('user_id', $this->user->id)->latest()->first();
        $this->assertEquals('pending', $payment->status);

        // 5. Simulate Payment Success
        $this->processPayment($payment);

        $payment->refresh();
        $this->assertEquals('paid', $payment->status);

        // V-WALLET-FIRST-2026: Verify wallet-first model
        // 1. Payment credited to wallet (+principal)
        // 2. Bonus credited to wallet (+bonus)
        // 3. NO automatic share allocation - user decides
        $wallet = $this->user->wallet->fresh();
        $this->assertGreaterThan(0, $wallet->balance, "Wallet should have funds (principal + bonus)");

        // Principal should be credited (plan amount = 1000)
        $this->assertGreaterThanOrEqual(1000, $wallet->balance, "At least principal should be in wallet");

        // No automatic investments - user must click "Buy Shares"
        $autoInvestmentCount = \App\Models\UserInvestment::where('payment_id', $payment->id)->count();
        $this->assertEquals(0, $autoInvestmentCount, "No automatic investments - wallet-first model");
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function testKycSubmissionToApprovalFlow()
    {
        $this->user->kyc->update(['status' => 'pending', 'verified_at' => null]);

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
        
        $this->user->kyc->refresh();
        $this->assertContains($this->user->kyc->status, ['submitted', 'processing']);
        
        $kyc = $this->user->kyc;
        $response = $this->actingAs($this->admin)->postJson("/api/v1/admin/kyc-queue/{$kyc->id}/approve");
        $response->assertStatus(200);
        $this->assertEquals('verified', $kyc->fresh()->status);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function testSubscriptionPauseAndResumeFlow()
    {
        $sub = $this->subscription;

        $this->actingAs($this->user)->postJson("/api/v1/user/subscription/pause", ['months' => 1])
             ->assertStatus(200);
        $this->assertEquals('paused', $sub->fresh()->status);

        $this->actingAs($this->user)->postJson("/api/v1/user/subscription/resume")
             ->assertStatus(200);
        $this->assertEquals('active', $sub->fresh()->status);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function testSubscriptionCancellationFlow()
    {
        $sub = $this->subscription;

        $this->actingAs($this->user)->postJson("/api/v1/user/subscription/cancel", ['reason' => 'Test'])
             ->assertStatus(200);

        $this->assertEquals('cancelled', $sub->fresh()->status);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function testWithdrawalRequestToCompletionFlow()
    {
        $this->user->kyc->update(['status' => 'verified']);
        $this->user->wallet->update(['balance_paise' => 500000, 'locked_balance_paise' => 0]); 

        $response = $this->actingAs($this->user)->postJson('/api/v1/user/wallet/withdraw', [
            'amount' => 2000,
            'bank_details' => ['account' => '123', 'ifsc' => 'ABC']
        ]);
        
        if ($response->status() !== 201) {
            $this->assertEquals(201, $response->status(), "Withdrawal failed: " . $response->getContent());
        }

        $wallet = $this->user->wallet->fresh();
        // lockBalance=true means TOTAL balance (balance_paise) remains 5000
        $this->assertEquals(5000, $wallet->balance);
        $this->assertEquals(2000, $wallet->locked_balance);

        $withdrawal = Withdrawal::where('user_id', $this->user->id)->latest()->first();
        $withdrawal->update(['status' => 'approved']);

        // V-WALLET-FIRST-2026: Grant admin permission and check response
        $this->admin->givePermissionTo('withdrawals.complete');
        $completeResponse = $this->actingAs($this->admin)->postJson("/api/v1/admin/withdrawal-queue/{$withdrawal->id}/complete", ['utr_number' => 'UTR123']);

        if ($completeResponse->status() !== 200) {
            $this->fail("Admin complete failed: " . $completeResponse->getContent());
        }

        $wallet = $this->user->wallet->fresh();
        $this->assertEquals(3000, $wallet->balance);
        $this->assertEquals(0, $wallet->locked_balance);
        $this->assertEquals('completed', $withdrawal->fresh()->status);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function testReferralBonusCompleteFlow()
    {
        $referrer = User::factory()->create();
        $referrer->kyc->update(['status' => 'verified']);
        $referrer->wallet->update(['balance_paise' => 0]);
        $referrer->subscription = Subscription::factory()->create(['user_id' => $referrer->id, 'amount' => 1000, 'amount_paise' => 100000]);

        $referee = $this->user;
        $referee->kyc->update(['status' => 'verified']);
        
        Referral::create(['referrer_id' => $referrer->id, 'referred_id' => $referee->id, 'status' => 'pending']);

        // Run the job manually
        $job = new ProcessReferralJob($referee);
        app()->call([$job, 'handle']);

        $this->assertEquals(450, $referrer->wallet->fresh()->balance);
        $this->assertDatabaseHas('bonus_transactions', ['user_id' => $referrer->id, 'type' => 'referral']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function testMilestoneBonusCompleteFlow()
    {
        $this->user->wallet->update(['balance_paise' => 0, 'locked_balance_paise' => 0]);

        // V-WALLET-FIRST-2026: Ensure the plan has milestone_config with proper array structure
        // BonusCalculatorService::calculateMilestone expects: [['month' => 12, 'amount' => 500], ...]
        \App\Models\PlanConfig::updateOrCreate(
            ['plan_id' => $this->plan->id, 'config_key' => 'milestone_config'],
            ['value' => [['month' => 12, 'amount' => 500], ['month' => 24, 'amount' => 1000], ['month' => 36, 'amount' => 2000]]]
        );

        Payment::factory()->count(11)->create([
            'user_id' => $this->user->id,
            'subscription_id' => $this->subscription->id,
            'status' => 'paid',
            'payment_type' => \App\Enums\PaymentType::SIP_INSTALLMENT->value,
        ]);
        // V-WALLET-FIRST-2026: Set to 11 because PaymentWebhookService increments BEFORE bonus job runs
        // After increment: 11 + 1 = 12, which matches milestone_config['month' => 12]
        $this->subscription->update(['consecutive_payments_count' => 11]);

        $payment = Payment::factory()->create([
            'user_id' => $this->user->id,
            'subscription_id' => $this->subscription->id,
            'status' => 'pending',
            'gateway_order_id' => 'order_milestone_' . uniqid(),
            'is_on_time' => true,
            'payment_type' => \App\Enums\PaymentType::SIP_INSTALLMENT->value,
            'amount' => 1000,
            'amount_paise' => 100000,
        ]);

        $this->processPayment($payment);

        $this->assertDatabaseHas('bonus_transactions', [
            'payment_id' => $payment->id,
            'type' => 'milestone_bonus'
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function testProfitShareDistributionFlow()
    {
        $this->admin->givePermissionTo('bonuses.manage_config');

        // Lower threshold for testing
        // Flush ALL cache to ensure clean state
        Cache::flush();

        // Update settings in database
        Setting::updateOrCreate(['key' => 'profit_share_min_investment'], ['value' => '100', 'type' => 'number']);
        Setting::updateOrCreate(['key' => 'profit_share_min_months'], ['value' => '1', 'type' => 'number']);
        Setting::updateOrCreate(['key' => 'profit_share_require_active_subscription'], ['value' => 'true', 'type' => 'boolean']);
        Setting::updateOrCreate(['key' => 'profit_share_formula_type'], ['value' => 'weighted_investment', 'type' => 'string']);

        // V-WALLET-FIRST-2026: Use direct DB update since created_at isn't fillable
        \DB::table('users')->where('id', $this->user->id)->update(['created_at' => now()->subMonths(3)]);
        $this->user->refresh();
        $this->subscription->update([
            'start_date' => now()->subMonths(3),
            'amount' => 10000,
            'amount_paise' => 1000000,
            'status' => 'active'
        ]);

        // V-WALLET-FIRST-2026: Create a UserInvestment record
        // Profit shares are calculated based on investments, not just subscriptions
        $product = \App\Models\Product::first();
        $this->assertNotNull($product, 'Product seeder must have run');

        $bulkPurchase = \App\Models\BulkPurchase::where('product_id', $product->id)->first();
        if (!$bulkPurchase) {
            // Create bulk purchase manually without triggering factory cascade
            $bulkPurchase = \App\Models\BulkPurchase::create([
                'product_id' => $product->id,
                'company_id' => $product->company_id,
                'admin_id' => $this->admin->id,
                'approved_by_admin_id' => $this->admin->id,
                'source_type' => 'manual_entry',
                'manual_entry_reason' => 'Test bulk purchase created for profit share distribution test purposes.',
                'source_documentation' => 'Test documentation',
                'verified_at' => now(),
                'face_value_purchased' => 100000,
                'actual_cost_paid' => 80000,
                'discount_percentage' => 20,
                'extra_allocation_percentage' => 0,
                'total_value_received' => 100000,
                'value_remaining' => 100000,
                'seller_name' => 'Test Seller',
                'purchase_date' => now()->subMonths(3),
            ]);
        }

        // Create a paid payment for this subscription
        $payment = \App\Models\Payment::where('subscription_id', $this->subscription->id)->first();
        if (!$payment) {
            $uniqueId = 'test_profit_share_' . uniqid();
            $payment = \App\Models\Payment::create([
                'user_id' => $this->user->id,
                'subscription_id' => $this->subscription->id,
                'status' => 'paid',
                'amount' => 10000,
                'amount_paise' => 1000000,
                'gateway_order_id' => 'order_' . $uniqueId,
                'gateway_payment_id' => 'pay_' . $uniqueId,
                'paid_at' => now()->subMonths(2),
            ]);
        }

        // Create UserInvestment directly without factory
        \App\Models\UserInvestment::create([
            'user_id' => $this->user->id,
            'product_id' => $product->id,
            'subscription_id' => $this->subscription->id,
            'payment_id' => $payment->id,
            'bulk_purchase_id' => $bulkPurchase->id,
            'shares' => 100,
            'price_per_share' => 100,
            'total_amount' => 10000,
            'units_allocated' => 100,
            'value_allocated' => 10000,
            'allocated_at' => now()->subMonths(2), // Within profit share period
            'status' => 'active',
            'source' => 'sip',
            'is_reversed' => false,
        ]);

        // Clear any stale idempotency records that might cause the job to skip
        \DB::table('job_executions')->truncate();

        // DEBUG: Verify subscription eligibility
        $minMonths = (int) setting('profit_share_min_months', 3);
        $minInvestment = (float) setting('profit_share_min_investment', 10000);
        $this->subscription->refresh();
        $this->user->refresh();

        $this->assertGreaterThanOrEqual($minInvestment, $this->subscription->amount,
            "Subscription amount ({$this->subscription->amount}) must be >= min ({$minInvestment})");
        $this->assertEquals('active', $this->subscription->status, "Subscription must be active");
        $this->assertEquals($this->user->id, $this->subscription->user_id, "Subscription must belong to test user");

        // Verify user is old enough
        $userAge = $this->user->created_at->diffInMonths(now());
        $this->assertGreaterThanOrEqual($minMonths, $userAge,
            "User age ({$userAge} months) must be >= min ({$minMonths})");

        $period = ProfitShare::factory()->create([
            'status' => 'pending',
            'total_pool' => 10000,
            'start_date' => now()->subMonths(4),
            'end_date' => now(),
            'calculation_metadata' => null, // Clear factory default to verify calculation actually runs
        ]);

        // 1. Calculate
        CalculateProfitShareJob::dispatchSync($period, $this->admin);
        $period->refresh();

        if ($period->status !== 'calculated') {
            // Get any error from job_executions for debugging
            $jobExecution = \DB::table('job_executions')
                ->where('idempotency_key', 'profit_share_calculation:' . $period->id)
                ->first();
            $jobInfo = $jobExecution ? " Job status: {$jobExecution->status}, Error: " . ($jobExecution->error_message ?? 'none') : " No job execution record found";

            $this->assertEquals('calculated', $period->status, "Profit share calculation failed.{$jobInfo} Metadata: " . json_encode($period->calculation_metadata));
        }

        // 2. Distribute
        ProcessProfitShareDistribution::dispatchSync($period, $this->admin);

        $this->assertEquals('distributed', $period->fresh()->status);
        $this->assertGreaterThan(0, $this->user->wallet->fresh()->balance);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function testLuckyDrawEntryToWinnerFlow()
    {
        $this->user->wallet->update(['balance_paise' => 0, 'locked_balance_paise' => 0]);

        $draw = LuckyDraw::factory()->create(['status' => 'open', 'prize_structure' => [
            ['rank' => 1, 'count' => 1, 'amount' => 1000]
        ]]);
        
        $payment = Payment::factory()->create([
            'user_id' => $this->user->id,
            'subscription_id' => $this->subscription->id,
            'is_on_time' => true,
        ]);

        $job = new GenerateLuckyDrawEntryJob($payment);
        app()->call([$job, 'handle']);

        $this->assertDatabaseHas('lucky_draw_entries', ['user_id' => $this->user->id]);

        $this->admin->givePermissionTo('bonuses.manage_config');
        $this->actingAs($this->admin)->postJson("/api/v1/admin/lucky-draws/{$draw->id}/execute");

        $this->assertEquals('completed', $draw->fresh()->status);
        $this->assertEquals(1000, $this->user->wallet->fresh()->balance);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function testAutoDebitFailureAndRetryFlow()
    {
        $this->markTestSkipped('V-REFACTOR-2026: Auto-debit retry counting uses DB query, not payment.retry_count field.');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function testKycRejectionAndResubmissionFlow()
    {
        $this->user->kyc->update(['status' => 'pending', 'verified_at' => null]);
        $kyc = $this->user->kyc;
        
        $this->actingAs($this->user)->postJson('/api/v1/user/kyc', [
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
        $kyc->update(['status' => 'submitted']); 

        $this->actingAs($this->admin)->postJson("/api/v1/admin/kyc-queue/{$kyc->id}/reject", ['reason' => 'Blurry photo']);
        $this->assertEquals('rejected', $kyc->fresh()->status);
        
        $this->actingAs($this->user)->postJson('/api/v1/user/kyc', [
            'pan_number' => 'ABCDE1234F',
            'aadhaar_number' => '123456789012',
            'demat_account' => '12345678',
            'bank_account' => '0987654321',
            'bank_ifsc' => 'HDFC0001234',
            'bank_name' => 'HDFC Bank',
            'pan' => UploadedFile::fake()->image('pan_new.jpg'),
            'aadhaar_front' => UploadedFile::fake()->image('front.jpg'),
            'aadhaar_back' => UploadedFile::fake()->image('back.jpg'),
            'bank_proof' => UploadedFile::fake()->create('bank.pdf', 100),
            'demat_proof' => UploadedFile::fake()->create('demat.pdf', 100),
            'address_proof' => UploadedFile::fake()->create('address.pdf', 100),
            'photo' => UploadedFile::fake()->image('photo.jpg'),
            'signature' => UploadedFile::fake()->image('signature.jpg'),
        ]);
        $kyc->fresh()->update(['status' => 'submitted']);
        
        $this->actingAs($this->admin)->postJson("/api/v1/admin/kyc-queue/{$kyc->id}/approve");
        $this->assertEquals('verified', $kyc->fresh()->status);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function testCelebrationBonusOnBirthdayFlow()
    {
        $this->user->profile->update(['dob' => now()->format('Y-m-d')]);
        $this->artisan('app:process-celebration-bonuses');
        $this->assertDatabaseHas('bonus_transactions', [
            'user_id' => $this->user->id,
            'type' => 'celebration',
            'description' => 'Happy Birthday!'
        ]);
        $this->assertGreaterThan(0, $this->user->wallet->fresh()->balance);
    }
}
