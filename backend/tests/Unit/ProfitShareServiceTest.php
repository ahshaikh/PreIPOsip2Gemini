<?php
// V-FINAL-1730-TEST-43 (Created)

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\ProfitShareService;
use App\Models\User;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\ProfitShare;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;

class ProfitShareServiceTest extends TestCase
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

        // --- Setup Plans ---
        // Plan A: 5% Share
        $this->planA = Plan::factory()->create(['monthly_amount' => 1000]);
        $this->planA->configs()->create(['config_key' => 'profit_share', 'value' => ['percentage' => 5]]);
        
        // Plan B: 10% Share
        $this->planB = Plan::factory()->create(['monthly_amount' => 1000]);
        $this->planB->configs()->create(['config_key' => 'profit_share', 'value' => ['percentage' => 10]]);

        // --- Setup Users ---
        // User A: Eligible
        $this->userA = User::factory()->create(['created_at' => now()->subMonths(4)]);
        $this->userA->wallet()->create(['balance_paise' => 0, 'locked_balance_paise' => 0]);
        Subscription::factory()->create(['user_id' => $this->userA->id, 'plan_id' => $this->planA->id, 'status' => 'active']);

        // User B: Eligible
        $this->userB = User::factory()->create(['created_at' => now()->subMonths(4)]);
        $this->userB->wallet()->create(['balance_paise' => 0, 'locked_balance_paise' => 0]);
        Subscription::factory()->create(['user_id' => $this->userB->id, 'plan_id' => $this->planB->id, 'status' => 'active']);

        // --- Setup Profit Share ---
        $this->period = ProfitShare::factory()->create([
            'total_pool' => 10000, // ₹10k to distribute
            'status' => 'pending'
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_calculate_profit_uses_correct_formula()
    {
        // 2 users with same investment weight (1000). Total weight = 2000.
        // User A Ratio = 1000/2000 = 0.5
        // User B Ratio = 1000/2000 = 0.5
        // Pool = 10000
        
        // User A (Plan A @ 5%): 10000 * 0.5 * (0.05 * 10) = 2500
        // User B (Plan B @ 10%): 10000 * 0.5 * (0.10 * 10) = 5000
        // (Note: Service has a 10x boost factor in formula)

        $this->service->calculateDistribution($this->period);

        $this->assertDatabaseHas('user_profit_shares', [
            'user_id' => $this->userA->id,
            'amount' => 2500
        ]);
        $this->assertDatabaseHas('user_profit_shares', [
            'user_id' => $this->userB->id,
            'amount' => 5000
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_calculate_distribution_checks_eligibility()
    {
        // User C: Ineligible (New User)
        $userC = User::factory()->create(['created_at' => now()]); // Joined today
        Subscription::factory()->create(['user_id' => $userC->id, 'plan_id' => $this->planA->id, 'status' => 'active']);

        $this->service->calculateDistribution($this->period);

        // Should still only find User A and B
        $this->assertEquals(2, $this->period->distributions()->count());
        $this->assertDatabaseMissing('user_profit_shares', ['user_id' => $userC->id]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_calculate_distribution_applies_plan_percentage()
    {
        // This is implicitly tested in test_calculate_profit_uses_correct_formula
        // User A (5%) got 2500
        // User B (10%) got 5000
        // This confirms the plan % was used.
        $this->service->calculateDistribution($this->period);
        $this->assertDatabaseHas('user_profit_shares', ['user_id' => $this->userA->id, 'amount' => 2500]);
        $this->assertDatabaseHas('user_profit_shares', ['user_id' => $this->userB->id, 'amount' => 5000]);
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
        $this->assertEquals(2500, $this->userA->wallet->fresh()->balance);
        $this->assertEquals(5000, $this->userB->wallet->fresh()->balance);

        // Status updated
        $this->assertEquals('distributed', $this->period->fresh()->status);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_reversal_debits_wallets_correctly()
    {
        $admin = User::factory()->create();
        $this->service->calculateDistribution($this->period);
        $this->service->distributeToWallets($this->period, $admin);

        // Wallets are now 2500 and 5000
        $this->assertEquals(2500, $this->userA->wallet->fresh()->balance);

        // --- REVERSE ---
        $this->service->reverseDistribution($this->period);

        // 1. Wallets should be debited
        $this->assertEquals(0, $this->userA->wallet->fresh()->balance);
        $this->assertEquals(0, $this->userB->wallet->fresh()->balance);
        
        // 2. Status updated
        $this->assertEquals('reversed', $this->period->fresh()->status);
        
        // 3. Reversal transactions created
        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->userA->id,
            'type' => 'reversal',
            'amount' => -2500
        ]);
    }
}