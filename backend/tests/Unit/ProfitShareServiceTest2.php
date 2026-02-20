<?php
// V-FINAL-1730-TEST-77 (Created)

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\ProfitShareService;
use App\Models\User;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\ProfitShare;
use App\Models\Wallet;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProfitShareServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $service;
    protected $planA, $planB;
    protected $userA, $userB;
    protected $period;
    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->app->make(ProfitShareService::class);
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this->seed(\Database\Seeders\SettingsSeeder::class); // For TDS thresholds

        // --- Setup Plans ---
        $this->planA = Plan::factory()->create(['monthly_amount' => 1000]);
        $this->planA->configs()->create(['config_key' => 'profit_share', 'value' => ['percentage' => 5]]);
        
        $this->planB = Plan::factory()->create(['monthly_amount' => 2000]);
        $this->planB->configs()->create(['config_key' => 'profit_share', 'value' => ['percentage' => 10]]);

        // --- Setup Users (Eligible: created 4 months ago) ---
        $this->userA = User::factory()->create(['created_at' => now()->subMonths(4)]);
        $this->userA->wallet()->create(['balance_paise' => 0, 'locked_balance_paise' => 0]);
        $this->userA->kyc->update(['pan_number' => 'PAN-A']); // For TDS
        Subscription::factory()->create(['user_id' => $this->userA->id, 'plan_id' => $this->planA->id, 'status' => 'active']);

        $this->userB = User::factory()->create(['created_at' => now()->subMonths(4)]);
        $this->userB->wallet()->create(['balance_paise' => 0, 'locked_balance_paise' => 0]);
        $this->userB->kyc->update(['pan_number' => 'PAN-B']); // For TDS
        Subscription::factory()->create(['user_id' => $this->userB->id, 'plan_id' => $this->planB->id, 'status' => 'active']);
        
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        // --- Setup Profit Share ---
        $this->period = ProfitShare::factory()->create([
            'total_pool' => 30000, // ₹30k to distribute
            'status' => 'pending'
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_profit_share_zero_profit_no_distribution()
    {
        $this->period->update(['total_pool' => 0]);
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Total pool is zero or negative");
        
        $this->service->calculateDistribution($this->period);
        $this->assertEquals('cancelled', $this->period->fresh()->status);
    }
    
    #[\PHPUnit\Framework\Attributes\Test]
    public function test_profit_share_calculation_formula_division_by_zero()
    {
        // Delete all subscriptions so total weight is 0
        Subscription::query()->delete();
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("No eligible investments found");

        $this->service->calculateDistribution($this->period);
    }
    
    #[\PHPUnit\Framework\Attributes\Test]
    public function test_calculate_distribution_applies_plan_percentage()
    {
        // Total Weight = 1000 (A) + 2000 (B) = 3000
        // Pool = 30000
        // User A Ratio = 1000/3000 = 0.333...
        // User B Ratio = 2000/3000 = 0.666...
        
        // User A (Plan A @ 5%): 30000 * 0.333 * 0.05 = 500
        // User B (Plan B @ 10%): 30000 * 0.666 * 0.10 = 2000
        
        $this->service->calculateDistribution($this->period);

        $this->assertDatabaseHas('user_profit_shares', [
            'user_id' => $this->userA->id,
            'amount' => 500
        ]);
        $this->assertDatabaseHas('user_profit_shares', [
            'user_id' => $this->userB->id,
            'amount' => 2000
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_calculate_distribution_checks_eligibility()
    {
        // User C: Ineligible (New User, joined 1 month ago)
        $userC = User::factory()->create(['created_at' => now()->subMonth(1)]);
        Subscription::factory()->create(['user_id' => $userC->id, 'plan_id' => $this->planA->id, 'status' => 'active']);

        $this->service->calculateDistribution($this->period);
        
        // Still only 2 users in the distribution
        $this->assertEquals(2, $this->period->distributions()->count());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_profit_share_tax_deduction_tds()
    {
        // Set TDS threshold to 400
        Setting::updateOrCreate(['key' => 'tds_threshold'], ['value' => 400]);
        // Set TDS rate to 10%
        Setting::updateOrCreate(['key' => 'tds_rate'], ['value' => 0.10]);
        
        $this->service->calculateDistribution($this->period);
        
        // User A: Gross = 500. TDS = 50. Net = 450.
        // User B: Gross = 2000. TDS = 200. Net = 1800.
        
        $this->service->distributeToWallets($this->period, $this->admin);

        // Check wallet balances (Net)
        $this->assertEquals(450, $this->userA->wallet->fresh()->balance);
        $this->assertEquals(1800, $this->userB->wallet->fresh()->balance);
        
        // Check BonusTransaction (Gross + TDS)
        $this->assertDatabaseHas('bonus_transactions', [
            'user_id' => $this->userA->id,
            'amount' => 500,
            'tds_deducted' => 50
        ]);
        $this->assertDatabaseHas('bonus_transactions', [
            'user_id' => $this->userB->id,
            'amount' => 2000,
            'tds_deducted' => 200
        ]);
    }
    
    #[\PHPUnit\Framework\Attributes\Test]
    public function test_profit_share_reversal_insufficient_balance()
    {
        $this->service->calculateDistribution($this->period);
        $this->service->distributeToWallets($this->period, $this->admin);

        // User A balance = 450
        $this->userA->wallet->update(['balance_paise' => 10000]); // User spent the money - ₹100
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("insufficient funds");
        
        $this->service->reverseDistribution($this->period, "Test Reversal");
        
        // Assert transaction was rolled back and status is still 'distributed'
        $this->assertEquals('distributed', $this->period->fresh()->status);
    }
}