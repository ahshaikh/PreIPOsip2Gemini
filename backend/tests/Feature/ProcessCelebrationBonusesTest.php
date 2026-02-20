<?php
// V-FINAL-1730-TEST-74 (Created)

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Wallet;
use Illuminate\Support\Carbon;

class ProcessCelebrationBonusesTest extends TestCase
{
    use RefreshDatabase;

    protected $userA;
    protected $userB;
    protected $planA;
    protected $planB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this->seed(\Database\Seeders\SettingsSeeder::class);

        // --- Setup Plan A ---
        $this->planA = Plan::factory()->create(['slug' => 'plan_a']);
        $this->planA->configs()->create([
            'config_key' => 'celebration_bonus_config',
            'value' => ['birthday_amount' => 50, 'anniversary_amount' => 100]
        ]);
        
        // --- Setup Plan B ---
        $this->planB = Plan::factory()->create(['slug' => 'plan_b']);
        $this->planB->configs()->create([
            'config_key' => 'celebration_bonus_config',
            'value' => ['birthday_amount' => 200, 'anniversary_amount' => 500]
        ]);

        // --- Setup User A (Birthday Today, 2-Year Anniversary Today) ---
        $this->userA = User::factory()->create();
        $this->userA->wallet()->create(['balance_paise' => 0, 'locked_balance_paise' => 0]);
        UserProfile::create([
            'user_id' => $this->userA->id,
            'dob' => '1990-11-15' // Birthday is Nov 15
        ]);
        Subscription::factory()->create([
            'user_id' => $this->userA->id,
            'plan_id' => $this->planA->id,
            'status' => 'active',
            'start_date' => '2023-11-15' // 2-Year Anniversary is Nov 15
        ]);

        // --- Setup User B (No events today) ---
        $this->userB = User::factory()->create();
        $this->userB->wallet()->create(['balance_paise' => 0, 'locked_balance_paise' => 0]);
        UserProfile::create(['user_id' => $this->userB->id, 'dob' => '1985-01-01']);
        Subscription::factory()->create([
            'user_id' => $this->userB->id,
            'plan_id' => $this->planB->id,
            'status' => 'active',
            'start_date' => '2024-01-01'
        ]);

        // --- Travel to the specific date for testing ---
        Carbon::setTestNow(Carbon::parse('2025-11-15'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow(); // Reset time
        parent::tearDown();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_identifies_birthdays_today_and_awards_bonus()
    {
        // Run the command
        $this->artisan('app:process-celebration-bonuses');

        // Check User A (Birthday Today)
        $this->assertDatabaseHas('bonus_transactions', [
            'user_id' => $this->userA->id,
            'type' => 'celebration',
            'description' => 'Happy Birthday! Here is your bonus.',
            'amount' => 50 // From Plan A config
        ]);

        // Check User B (Birthday NOT Today)
        $this->assertDatabaseMissing('bonus_transactions', [
            'user_id' => $this->userB->id,
            'description' => 'Happy Birthday! Here is your bonus.'
        ]);
    }
    
    #[\PHPUnit\Framework\Attributes\Test]
    public function test_awards_birthday_bonus()
    {
        // This is implicitly tested by the test above.
        // We can double-check the wallet.
        
        $this->artisan('app:process-celebration-bonuses');
        
        // User A's wallet should have 50 (birthday) + 200 (anniversary) = 250
        $this->assertEquals(250, $this->userA->wallet->fresh()->balance);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_identifies_subscription_anniversaries()
    {
        $this->artisan('app:process-celebration-bonuses');
        
        // User A (Anniversary Today)
        $this->assertDatabaseHas('bonus_transactions', [
            'user_id' => $this->userA->id,
            'type' => 'celebration',
            'description' => 'Happy 2 Year Anniversary!'
        ]);
        
        // User B (Anniversary NOT Today)
        $this->assertDatabaseMissing('bonus_transactions', [
            'user_id' => $this->userB->id,
            'description' => 'Happy 1 Year Anniversary!'
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_awards_anniversary_bonus()
    {
        // This test verifies the calculation (Base * Years)
        
        $this->artisan('app:process-celebration-bonuses');
        
        // User A: Plan A (100 base) * 2 years = 200
        $this->assertDatabaseHas('bonus_transactions', [
            'user_id' => $this->userA->id,
            'type' => 'celebration',
            'amount' => 200
        ]);
    }
}