<?php
// V-FINAL-1730-TEST-31

namespace Tests\Unit;

use Tests\UnitTestCase;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\CelebrationEvent;
use App\Models\Setting;
use Carbon\Carbon;

class BonusCalculatorCelebrationTest extends UnitTestCase
{
    protected $planA;
    protected $planB;
    protected $userA;
    protected $userB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this->seed(\Database\Seeders\SettingsSeeder::class); // For global toggle

        // Create Plan A (50/100)
        $this->planA = Plan::factory()->create(['slug' => 'plan_a']);
        $this->planA->configs()->create([
            'config_key' => 'celebration_bonus_config',
            'value' => ['birthday_amount' => 50, 'anniversary_amount' => 100]
        ]);
        
        // Create Plan B (200/500)
        $this->planB = Plan::factory()->create(['slug' => 'plan_b']);
        $this->planB->configs()->create([
            'config_key' => 'celebration_bonus_config',
            'value' => ['birthday_amount' => 200, 'anniversary_amount' => 500]
        ]);

        // Create User A on Plan A
        $this->userA = User::factory()->create();
        $this->userA->wallet()->create();
        UserProfile::create(['user_id' => $this->userA->id, 'dob' => '1990-11-14']);
        Subscription::factory()->create([
            'user_id' => $this->userA->id,
            'plan_id' => $this->planA->id,
            'status' => 'active',
            'start_date' => '2023-11-14' // 2 years ago
        ]);
        
        // Create User B on Plan B
        $this->userB = User::factory()->create();
        $this->userB->wallet()->create();
        UserProfile::create(['user_id' => $this->userB->id, 'dob' => '1985-01-01']);
        Subscription::factory()->create([
            'user_id' => $this->userB->id,
            'plan_id' => $this->planB->id,
            'status' => 'active',
            'start_date' => '2024-11-14' // 1 year ago
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_birthday_bonus_awarded_on_user_birthday()
    {
        // Travel to User A's birthday
        $this->travelTo(Carbon::parse('2025-11-14'));

        $this->artisan('app:process-celebration-bonuses');

        // User A gets bonus
        $this->assertDatabaseHas('bonus_transactions', [
            'user_id' => $this->userA->id,
            'amount' => 50
        ]);
        
        // User B does not
        $this->assertDatabaseMissing('bonus_transactions', ['user_id' => $this->userB->id]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_birthday_bonus_amount_varies_by_plan()
    {
        // Travel to User B's birthday
        $this->travelTo(Carbon::parse('2025-01-01'));
        
        $this->artisan('app:process-celebration-bonuses');

        // User B gets Plan B bonus (200)
        $this->assertDatabaseHas('bonus_transactions', [
            'user_id' => $this->userB->id,
            'amount' => 200
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_anniversary_bonus_awarded_on_subscription_anniversary()
    {
        // Travel to both users' anniversary (Nov 14)
        $this->travelTo(Carbon::parse('2025-11-14'));

        $this->artisan('app:process-celebration-bonuses');

        // User A (2 years)
        $this->assertDatabaseHas('bonus_transactions', [
            'user_id' => $this->userA->id,
            'description' => 'Happy 2 Year Anniversary!'
        ]);
        
        // User B (1 year)
        $this->assertDatabaseHas('bonus_transactions', [
            'user_id' => $this->userB->id,
            'description' => 'Happy 1 Year Anniversary!'
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_anniversary_bonus_amount_varies_by_year()
    {
        $this->travelTo(Carbon::parse('2025-11-14'));

        $this->artisan('app:process-celebration-bonuses');

        // User A: Plan A (100 base) * 2 years = 200
        $this->assertDatabaseHas('bonus_transactions', [
            'user_id' => $this->userA->id,
            'amount' => 200
        ]);

        // User B: Plan B (500 base) * 1 year = 500
        $this->assertDatabaseHas('bonus_transactions', [
            'user_id' => $this->userB->id,
            'amount' => 500
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_festival_bonus_awarded_for_configured_events()
    {
        // Create a festival for today
        CelebrationEvent::create([
            'name' => 'Test Festival',
            'event_date' => now(),
            'bonus_amount_by_plan' => [
                'plan_a' => 25,
                'plan_b' => 75,
            ]
        ]);

        $this->artisan('app:process-celebration-bonuses');

        // User A gets 25
        $this->assertDatabaseHas('bonus_transactions', [
            'user_id' => $this->userA->id,
            'amount' => 25,
            'description' => 'Happy Test Festival!'
        ]);
        
        // User B gets 75
        $this->assertDatabaseHas('bonus_transactions', [
            'user_id' => $this->userB->id,
            'amount' => 75,
            'description' => 'Happy Test Festival!'
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_celebration_bonus_respects_global_toggle()
    {
        Setting::updateOrCreate(['key' => 'celebration_bonus_enabled'], ['value' => 'false']);
        
        $this->travelTo(Carbon::parse('2025-11-14')); // Both birthday and anniversary
        
        $this->artisan('app:process-celebration-bonuses');

        $this->assertDatabaseMissing('bonus_transactions', ['type' => 'celebration']);
    }
}
