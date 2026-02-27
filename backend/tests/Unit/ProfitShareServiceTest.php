<?php
// V-FINAL-1730-TEST-43 (Created)

namespace Tests\Unit;

use Tests\UnitTestCase;
use App\Services\ProfitShareService;
use App\Models\User;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\ProfitShare;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;

class ProfitShareServiceTest extends UnitTestCase
{
    protected $service;
    protected $planA, $planB;
    protected $userA, $userB;
    protected $period;

    protected function setUp(): void
    {
        parent::setUp();
        // V-WAVE2-FIX: Use DI container to resolve service with its dependencies
        $this->service = app(ProfitShareService::class);
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this->seed(\Database\Seeders\SettingsSeeder::class);

        // Override settings for test to ensure eligibility
        \App\Models\Setting::updateOrCreate(['key' => 'profit_share_min_investment'], ['value' => '100', 'type' => 'number']);
        \App\Models\Setting::updateOrCreate(['key' => 'profit_share_min_months'], ['value' => '1', 'type' => 'number']);
        \App\Models\Setting::updateOrCreate(['key' => 'profit_share_require_active_subscription'], ['value' => 'true', 'type' => 'boolean']);

        // --- Setup Plans ---
        // Plan A: 5% Share
        $this->planA = Plan::factory()->create(['monthly_amount' => 1000]);
        $this->planA->configs()->create(['config_key' => 'profit_share', 'value' => ['percentage' => 5]]);
        
        // Plan B: 10% Share
        $this->planB = Plan::factory()->create(['monthly_amount' => 1000]);
        $this->planB->configs()->create(['config_key' => 'profit_share', 'value' => ['percentage' => 10]]);

        // Fixed date for consistency
        $baseDate = \Carbon\Carbon::parse('2025-01-01');
        \Carbon\Carbon::setTestNow($baseDate);

        // --- Setup Users ---
        // User A: Eligible
        $this->userA = User::factory()->create(['created_at' => $baseDate->copy()->subMonths(4)]);
        $this->userA->wallet->update(['balance_paise' => 0, 'locked_balance_paise' => 0]);
        Subscription::factory()->create([
            'user_id' => $this->userA->id, 
            'plan_id' => $this->planA->id, 
            'status' => 'active',
            'amount' => 1000,
            'start_date' => $baseDate->copy()->subMonths(3)
        ]);

        // User B: Eligible
        $this->userB = User::factory()->create(['created_at' => $baseDate->copy()->subMonths(4)]);
        $this->userB->wallet->update(['balance_paise' => 0, 'locked_balance_paise' => 0]);
        Subscription::factory()->create([
            'user_id' => $this->userB->id, 
            'plan_id' => $this->planB->id, 
            'status' => 'active',
            'amount' => 1000,
            'start_date' => $baseDate->copy()->subMonths(3)
        ]);

        // --- Setup Profit Share ---
        $this->period = ProfitShare::factory()->create([
            'total_pool' => 10000, // ₹10k to distribute
            'status' => 'pending',
            'start_date' => $baseDate->copy()->subMonth(),
            'end_date' => $baseDate->copy()
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_calculate_profit_uses_correct_formula()
    {
        // 2 users with same investment weight (1000). Total weight = 2000.
        // User A Ratio = 1000/2000 = 0.5
        // User B Ratio = 1000/2000 = 0.5
        // Pool = 10000
        
        // User A (Plan A @ 5%): 10000 * 0.5 * 0.05 = 250
        // User B (Plan B @ 10%): 10000 * 0.5 * 0.10 = 500

        $this->service->calculateDistribution($this->period);

        $this->assertDatabaseHas('user_profit_shares', [
            'user_id' => $this->userA->id,
            'amount' => 250
        ]);
        $this->assertDatabaseHas('user_profit_shares', [
            'user_id' => $this->userB->id,
            'amount' => 500
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_calculate_distribution_checks_eligibility()
    {
        // User C: Ineligible (New User)
        $userC = User::factory()->create(['created_at' => now()]); // Joined today
        Subscription::factory()->create(['user_id' => $userC->id, 'plan_id' => $this->planA->id, 'status' => 'active', 'amount' => 1000]);

        $this->service->calculateDistribution($this->period);

        // Should still only find User A and B
        $this->assertEquals(2, $this->period->distributions()->count());
        $this->assertDatabaseMissing('user_profit_shares', ['user_id' => $userC->id]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_calculate_distribution_applies_plan_percentage()
    {
        // This is implicitly tested in test_calculate_profit_uses_correct_formula
        // User A (5%) got 250
        // User B (10%) got 500
        // This confirms the plan % was used.
        $this->service->calculateDistribution($this->period);
        $this->assertDatabaseHas('user_profit_shares', ['user_id' => $this->userA->id, 'amount' => 250]);
        $this->assertDatabaseHas('user_profit_shares', ['user_id' => $this->userB->id, 'amount' => 500]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_distribute_to_wallets_credits_correctly()
    {
        $admin = User::factory()->create();
        $this->service->calculateDistribution($this->period);
        
        // At this point, User A wallet = 0
        $this->assertEquals(0, $this->userA->wallet->balance);

        $this->service->distributeToWallets($this->period, $admin);

        // User A wallet should be credited
        $this->assertEquals(250, $this->userA->wallet->fresh()->balance);
        $this->assertEquals(500, $this->userB->wallet->fresh()->balance);

        // Status updated
        $this->assertEquals('distributed', $this->period->fresh()->status);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_reversal_debits_wallets_correctly()
    {
        $admin = User::factory()->create();
        $this->service->calculateDistribution($this->period);
        $this->service->distributeToWallets($this->period, $admin);

        // Wallets are now 250 and 500
        $this->assertEquals(250, $this->userA->wallet->fresh()->balance);

        // --- REVERSE ---
        $this->service->reverseDistribution($this->period->fresh(), 'Correction');

        // 1. Wallets should be debited
        $this->assertEquals(0, $this->userA->wallet->fresh()->balance);
        $this->assertEquals(0, $this->userB->wallet->fresh()->balance);
        
        // 2. Status updated
        $this->assertEquals('reversed', $this->period->fresh()->status);
        
        // 3. Reversal transactions created
        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->userA->id,
            'type' => 'admin_adjustment',
            'amount_paise' => 25000 // ₹250 = 25000 paise
        ]);
    }

    protected function tearDown(): void
    {
        \Carbon\Carbon::setTestNow();
        parent::tearDown();
    }
}
