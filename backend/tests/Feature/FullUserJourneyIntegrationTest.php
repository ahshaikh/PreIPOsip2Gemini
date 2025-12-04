<?php
// V-TEST-SUITE-005 (Full User Journey Integration Test)

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserKyc;
use App\Models\Wallet;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Payment;
use App\Models\BonusTransaction;
use App\Services\WalletService;
use App\Services\BonusCalculatorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

/**
 * This integration test simulates a complete user journey from registration
 * through payment processing and bonus awards.
 */
class FullUserJourneyIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Plan $plan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this->seed(\Database\Seeders\SettingsSeeder::class);

        // Create admin
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        // Create a plan with bonus configurations
        $this->plan = Plan::factory()->create([
            'name' => 'Premium SIP',
            'monthly_amount' => 5000,
            'is_active' => true
        ]);

        $this->plan->configs()->createMany([
            ['config_key' => 'progressive_config', 'value' => ['rate' => 0.5, 'start_month' => 4]],
            ['config_key' => 'milestone_config', 'value' => [['month' => 6, 'amount' => 1000], ['month' => 12, 'amount' => 2500]]],
            ['config_key' => 'consistency_config', 'value' => ['amount_per_payment' => 50]]
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function complete_user_registration_to_first_bonus_journey()
    {
        // ==================== STEP 1: USER REGISTRATION ====================
        $response = $this->postJson('/api/v1/auth/register', [
            'username' => 'journey_user',
            'email' => 'journey@test.com',
            'mobile' => '9876543210',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!'
        ]);

        $response->assertStatus(201);
        $userId = $response->json('user.id');
        $this->assertNotNull($userId);

        $user = User::find($userId);
        $this->assertNotNull($user);
        $this->assertEquals('journey_user', $user->username);

        // ==================== STEP 2: VERIFY WALLET CREATED ====================
        $this->assertDatabaseHas('wallets', ['user_id' => $userId, 'balance' => 0]);

        // ==================== STEP 3: VERIFY KYC RECORD CREATED ====================
        $this->assertDatabaseHas('user_kyc', ['user_id' => $userId, 'status' => 'pending']);

        // ==================== STEP 4: USER SUBMITS KYC ====================
        $kyc = UserKyc::where('user_id', $userId)->first();
        $kyc->update([
            'pan_number' => 'ABCDE1234F',
            'status' => 'submitted',
            'submitted_at' => now()
        ]);

        // ==================== STEP 5: ADMIN APPROVES KYC ====================
        $response = $this->actingAs($this->admin)
            ->postJson("/api/v1/admin/kyc-queue/{$kyc->id}/approve");

        $response->assertStatus(200);
        $this->assertDatabaseHas('user_kyc', [
            'id' => $kyc->id,
            'status' => 'verified'
        ]);

        // ==================== STEP 6: USER CREATES SUBSCRIPTION ====================
        $subscription = Subscription::create([
            'user_id' => $userId,
            'plan_id' => $this->plan->id,
            'monthly_amount' => $this->plan->monthly_amount,
            'status' => 'pending',
            'consecutive_payments_count' => 0,
            'bonus_multiplier' => 1.0,
            'starts_at' => now()
        ]);

        // ==================== STEP 7: PROCESS FIRST PAYMENT ====================
        $payment = Payment::create([
            'subscription_id' => $subscription->id,
            'amount' => 5000,
            'status' => 'paid',
            'is_on_time' => true,
            'paid_at' => now()
        ]);

        // Update subscription status and consecutive count
        $subscription->update([
            'status' => 'active',
            'consecutive_payments_count' => 1
        ]);

        // ==================== STEP 8: VERIFY CONSISTENCY BONUS ====================
        $bonusService = new BonusCalculatorService();
        $bonusService->calculateAndAwardBonuses($payment);

        $this->assertDatabaseHas('bonus_transactions', [
            'user_id' => $userId,
            'type' => 'consistency',
            'amount' => 50 // Defined in consistency_config
        ]);

        // ==================== STEP 9: VERIFY WALLET CREDITED ====================
        $wallet = Wallet::where('user_id', $userId)->first();
        $this->assertEquals(50, $wallet->balance);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_wallet_adjustment_flow()
    {
        // Create user with wallet
        $user = User::factory()->create();
        $user->assignRole('user');
        UserProfile::create(['user_id' => $user->id]);
        UserKyc::create(['user_id' => $user->id, 'status' => 'verified']);
        Wallet::create(['user_id' => $user->id, 'balance' => 1000]);

        // ==================== ADMIN CREDITS USER ====================
        $response = $this->actingAs($this->admin)
            ->postJson("/api/v1/admin/users/{$user->id}/adjust-balance", [
                'type' => 'credit',
                'amount' => 500,
                'description' => 'Promotional credit'
            ]);

        $response->assertStatus(200);
        $this->assertEquals(1500, $response->json('new_balance'));

        // Verify transaction recorded
        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'type' => 'admin_adjustment',
            'amount' => 500,
            'description' => 'Admin Adjustment: Promotional credit'
        ]);

        // ==================== ADMIN DEBITS USER ====================
        $response = $this->actingAs($this->admin)
            ->postJson("/api/v1/admin/users/{$user->id}/adjust-balance", [
                'type' => 'debit',
                'amount' => 200,
                'description' => 'Fee reversal'
            ]);

        $response->assertStatus(200);
        $this->assertEquals(1300, $response->json('new_balance'));

        // Verify negative transaction recorded
        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'type' => 'admin_adjustment',
            'amount' => -200
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function milestone_bonus_awarded_at_correct_month()
    {
        // Create user with subscription
        $user = User::factory()->create();
        $user->assignRole('user');
        Wallet::create(['user_id' => $user->id, 'balance' => 0]);

        $subscription = Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $this->plan->id,
            'monthly_amount' => 5000,
            'status' => 'active',
            'consecutive_payments_count' => 5, // One less than milestone
            'bonus_multiplier' => 1.0,
            'starts_at' => now()->subMonths(5)
        ]);

        // Create previous payments
        for ($i = 0; $i < 5; $i++) {
            Payment::create([
                'subscription_id' => $subscription->id,
                'amount' => 5000,
                'status' => 'paid',
                'is_on_time' => true,
                'paid_at' => now()->subMonths(5 - $i)
            ]);
        }

        // ==================== 6TH PAYMENT - MILESTONE MONTH ====================
        $subscription->update(['consecutive_payments_count' => 6]);

        $sixthPayment = Payment::create([
            'subscription_id' => $subscription->id,
            'amount' => 5000,
            'status' => 'paid',
            'is_on_time' => true,
            'paid_at' => now()
        ]);

        $bonusService = new BonusCalculatorService();
        $bonusService->calculateAndAwardBonuses($sixthPayment);

        // Verify milestone bonus of ₹1000 (month 6 milestone)
        $this->assertDatabaseHas('bonus_transactions', [
            'user_id' => $user->id,
            'type' => 'milestone',
            'amount' => 1000
        ]);

        // Verify consistency bonus also awarded
        $this->assertDatabaseHas('bonus_transactions', [
            'user_id' => $user->id,
            'type' => 'consistency',
            'payment_id' => $sixthPayment->id
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function referral_multiplier_affects_bonus_calculation()
    {
        // Create user with 2x multiplier (e.g., from referral campaign)
        $user = User::factory()->create();
        $user->assignRole('user');
        Wallet::create(['user_id' => $user->id, 'balance' => 0]);

        $subscription = Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $this->plan->id,
            'monthly_amount' => 5000,
            'status' => 'active',
            'consecutive_payments_count' => 4,
            'bonus_multiplier' => 2.0, // 2x multiplier!
            'starts_at' => now()->subMonths(3)
        ]);

        // Create previous payments (months 1-3)
        for ($i = 0; $i < 3; $i++) {
            Payment::create([
                'subscription_id' => $subscription->id,
                'amount' => 5000,
                'status' => 'paid',
                'is_on_time' => true,
                'paid_at' => now()->subMonths(3 - $i)
            ]);
        }

        // ==================== 4TH PAYMENT - PROGRESSIVE STARTS ====================
        $fourthPayment = Payment::create([
            'subscription_id' => $subscription->id,
            'amount' => 5000,
            'status' => 'paid',
            'is_on_time' => true,
            'paid_at' => now()
        ]);

        $bonusService = new BonusCalculatorService();
        $bonusService->calculateAndAwardBonuses($fourthPayment);

        // Base progressive: (4-4+1) * 0.5% * 5000 = 25
        // With 2x multiplier: 25 * 2 = 50
        $this->assertDatabaseHas('bonus_transactions', [
            'user_id' => $user->id,
            'type' => 'progressive',
            'amount' => 50,
            'multiplier_applied' => 2.0
        ]);

        // Base consistency: 50
        // With 2x multiplier: 50 * 2 = 100
        $this->assertDatabaseHas('bonus_transactions', [
            'user_id' => $user->id,
            'type' => 'consistency',
            'amount' => 100,
            'multiplier_applied' => 2.0
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function withdrawal_flow_with_locked_balance()
    {
        $user = User::factory()->create();
        $user->assignRole('user');
        UserProfile::create(['user_id' => $user->id]);
        UserKyc::create(['user_id' => $user->id, 'status' => 'verified']);
        $wallet = Wallet::create(['user_id' => $user->id, 'balance' => 5000, 'locked_balance' => 0]);

        $walletService = new WalletService();

        // ==================== USER REQUESTS WITHDRAWAL ====================
        $transaction = $walletService->withdraw(
            $user,
            2000,
            'withdrawal_request',
            'User withdrawal request',
            null,
            true // Lock the balance
        );

        // Verify balance moved to locked
        $wallet->refresh();
        $this->assertEquals(3000, $wallet->balance);
        $this->assertEquals(2000, $wallet->locked_balance);
        $this->assertEquals('pending', $transaction->status);

        // ==================== ADMIN REJECTS WITHDRAWAL ====================
        // This would unlock the funds back
        $unlockTransaction = $walletService->unlockFunds(
            $user,
            2000,
            'withdrawal_cancelled',
            'Admin rejected - invalid bank details'
        );

        $wallet->refresh();
        $this->assertEquals(5000, $wallet->balance); // Back to original
        $this->assertEquals(0, $wallet->locked_balance);
        $this->assertEquals('completed', $unlockTransaction->status);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function bulk_bonus_award_to_multiple_users()
    {
        // Create multiple users
        $users = [];
        for ($i = 0; $i < 5; $i++) {
            $user = User::factory()->create();
            $user->assignRole('user');
            Wallet::create(['user_id' => $user->id, 'balance' => 100]);
            $users[] = $user;
        }

        // ==================== ADMIN BULK AWARDS BONUS ====================
        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/users/bulk-action', [
                'user_ids' => array_map(fn($u) => $u->id, $users),
                'action' => 'bonus',
                'data' => ['amount' => 250]
            ]);

        $response->assertStatus(200);
        $this->assertStringContainsString('5 users', $response->json('message'));

        // Verify all users received the bonus
        foreach ($users as $user) {
            $this->assertEquals(350, $user->wallet->fresh()->balance); // 100 + 250
        }
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function settings_update_clears_cache_correctly()
    {
        // Pre-populate cache
        cache()->put('settings', ['old_setting' => 'value'], 3600);

        // Update theme settings
        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/settings/theme', [
                'primary_color' => '#FF0000'
            ]);

        $response->assertStatus(200);

        // Cache should be cleared
        $this->assertNull(cache()->get('settings'));

        // Setting should be in database
        $this->assertDatabaseHas('settings', [
            'key' => 'theme_primary_color',
            'value' => '#FF0000'
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function concurrent_wallet_operations_maintain_integrity()
    {
        $user = User::factory()->create();
        $user->assignRole('user');
        Wallet::create(['user_id' => $user->id, 'balance' => 1000]);

        $walletService = new WalletService();

        // Perform multiple operations
        $walletService->deposit($user, 500, 'deposit', 'Op 1');
        $walletService->deposit($user, 300, 'bonus_credit', 'Op 2');
        $walletService->withdraw($user, 200, 'withdrawal', 'Op 3');
        $walletService->deposit($user, 100, 'refund', 'Op 4');

        // Final balance: 1000 + 500 + 300 - 200 + 100 = 1700
        $wallet = $user->wallet->fresh();
        $this->assertEquals(1700, $wallet->balance);

        // Verify transaction count
        $transactionCount = $wallet->transactions()->count();
        $this->assertEquals(4, $transactionCount);

        // Verify running balance in transactions
        $transactions = $wallet->transactions()->orderBy('id')->get();
        $expectedBalances = [1500, 1800, 1600, 1700];
        foreach ($transactions as $index => $tx) {
            $this->assertEquals($expectedBalances[$index], $tx->balance_after);
        }
    }
}
