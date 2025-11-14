<?php
// V-FINAL-1730-TEST-27

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\BonusCalculatorService;
use App\Models\User;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Payment;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BonusCalculatorProgressiveTest extends TestCase
{
    use RefreshDatabase;

    protected $service;
    protected $plan;
    protected $subscription;

    public function setUp(): void
    {
        parent::setUp();
        $this->service = new BonusCalculatorService();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this->seed(\Database\Seeders\SettingsSeeder::class); // Defaults

        // Setup Standard Plan
        $this->plan = Plan::factory()->create(['monthly_amount' => 1000]);
        
        // Config: 0.5% Rate, Starts Month 4, Max 10%
        $this->plan->configs()->create([
            'config_key' => 'progressive_config',
            'value' => [
                'rate' => 0.5, 
                'start_month' => 4,
                'max_percentage' => 10,
                'overrides' => []
            ]
        ]);

        $user = User::factory()->create();
        $this->subscription = Subscription::factory()->create([
            'user_id' => $user->id,
            'plan_id' => $this->plan->id,
            'bonus_multiplier' => 1.0
        ]);
    }

    /** @test */
    public function test_progressive_bonus_not_awarded_before_start_month()
    {
        // Month 3 Payment
        Payment::factory()->count(2)->create(['subscription_id' => $this->subscription->id, 'status' => 'paid']);
        $payment = Payment::factory()->create(['subscription_id' => $this->subscription->id, 'amount' => 1000]);

        $bonus = $this->service->calculateAndAwardBonuses($payment);

        $this->assertDatabaseMissing('bonus_transactions', ['type' => 'progressive']);
    }

    /** @test */
    public function test_progressive_bonus_calculates_correctly_at_month_4()
    {
        // Month 4 Payment
        // Formula: (4 - 4 + 1) * 0.5% * 1000 = 1 * 0.005 * 1000 = 5
        Payment::factory()->count(3)->create(['subscription_id' => $this->subscription->id, 'status' => 'paid']);
        $payment = Payment::factory()->create(['subscription_id' => $this->subscription->id, 'amount' => 1000]);

        $this->service->calculateAndAwardBonuses($payment);

        $this->assertDatabaseHas('bonus_transactions', [
            'type' => 'progressive',
            'amount' => 5.00
        ]);
    }

    /** @test */
    public function test_progressive_bonus_increases_linearly_each_month()
    {
        // Month 5 Payment
        // Formula: (5 - 4 + 1) * 0.5% * 1000 = 2 * 0.005 * 1000 = 10
        Payment::factory()->count(4)->create(['subscription_id' => $this->subscription->id, 'status' => 'paid']);
        $payment = Payment::factory()->create(['subscription_id' => $this->subscription->id, 'amount' => 1000]);

        $this->service->calculateAndAwardBonuses($payment);

        $this->assertDatabaseHas('bonus_transactions', [
            'type' => 'progressive',
            'amount' => 10.00
        ]);
    }

    /** @test */
    public function test_progressive_bonus_respects_configured_rate()
    {
        // Change rate to 1.0%
        $this->plan->configs()->update([
            'value' => ['rate' => 1.0, 'start_month' => 4, 'max_percentage' => 10]
        ]);

        // Month 4 Payment
        // Formula: 1 * 1.0% * 1000 = 10
        Payment::factory()->count(3)->create(['subscription_id' => $this->subscription->id, 'status' => 'paid']);
        $payment = Payment::factory()->create(['subscription_id' => $this->subscription->id, 'amount' => 1000]);

        $this->service->calculateAndAwardBonuses($payment);

        $this->assertDatabaseHas('bonus_transactions', [
            'type' => 'progressive',
            'amount' => 10.00
        ]);
    }

    /** @test */
    public function test_progressive_bonus_applies_referral_multiplier()
    {
        // Set Multiplier to 2.0
        $this->subscription->update(['bonus_multiplier' => 2.0]);

        // Month 4 Payment
        // Formula: (5.00 Base Bonus) * 2.0 = 10.00
        Payment::factory()->count(3)->create(['subscription_id' => $this->subscription->id, 'status' => 'paid']);
        $payment = Payment::factory()->create(['subscription_id' => $this->subscription->id, 'amount' => 1000]);

        $this->service->calculateAndAwardBonuses($payment);

        $this->assertDatabaseHas('bonus_transactions', [
            'type' => 'progressive',
            'amount' => 10.00,
            'multiplier_applied' => 2.0
        ]);
    }

    /** @test */
    public function test_progressive_bonus_uses_month_override_if_configured()
    {
        // Override Month 4 to be 5.0% (Instead of 0.5%)
        $this->plan->configs()->update([
            'value' => [
                'rate' => 0.5, 
                'start_month' => 4,
                'overrides' => [4 => 5.0] // Month 4 override
            ]
        ]);

        // Month 4 Payment
        // Formula: 5.0% * 1000 = 50.00
        Payment::factory()->count(3)->create(['subscription_id' => $this->subscription->id, 'status' => 'paid']);
        $payment = Payment::factory()->create(['subscription_id' => $this->subscription->id, 'amount' => 1000]);

        $this->service->calculateAndAwardBonuses($payment);

        $this->assertDatabaseHas('bonus_transactions', [
            'type' => 'progressive',
            'amount' => 50.00
        ]);
    }

    /** @test */
    public function test_progressive_bonus_caps_at_maximum_percentage()
    {
        // Set Max Cap to 1%
        // Set Month to 100 (Calculated rate would be ~48%)
        $this->plan->configs()->update([
            'value' => ['rate' => 0.5, 'start_month' => 4, 'max_percentage' => 1.0]
        ]);

        Payment::factory()->count(99)->create(['subscription_id' => $this->subscription->id, 'status' => 'paid']);
        $payment = Payment::factory()->create(['subscription_id' => $this->subscription->id, 'amount' => 1000]);

        $this->service->calculateAndAwardBonuses($payment);

        // Should be capped at 1.0% = 10.00
        $this->assertDatabaseHas('bonus_transactions', [
            'type' => 'progressive',
            'amount' => 10.00
        ]);
    }

    /** @test */
    public function test_progressive_bonus_uses_payment_amount_as_base()
    {
        // Month 4 Payment of 5000
        // Formula: 0.5% * 5000 = 25.00
        Payment::factory()->count(3)->create(['subscription_id' => $this->subscription->id, 'status' => 'paid']);
        $payment = Payment::factory()->create(['subscription_id' => $this->subscription->id, 'amount' => 5000]);

        $this->service->calculateAndAwardBonuses($payment);

        $this->assertDatabaseHas('bonus_transactions', [
            'amount' => 25.00,
            'base_amount' => 5000.00
        ]);
    }

    /** @test */
    public function test_progressive_bonus_respects_global_toggle()
    {
        // Disable globally
        Setting::updateOrCreate(['key' => 'progressive_bonus_enabled'], ['value' => 'false']);

        // Month 4 Payment
        Payment::factory()->count(3)->create(['subscription_id' => $this->subscription->id, 'status' => 'paid']);
        $payment = Payment::factory()->create(['subscription_id' => $this->subscription->id, 'amount' => 1000]);

        $this->service->calculateAndAwardBonuses($payment);

        $this->assertDatabaseMissing('bonus_transactions', ['type' => 'progressive']);
    }

    /** @test */
    public function test_progressive_bonus_formula_evaluation_works()
    {
        // Since we standardized on "Linear + Overrides" instead of unsafe "Eval()",
        // this test confirms the standard linear formula logic works as expected
        // (Month - Start + 1) * Rate
        
        // Month 6 (3rd eligible month)
        // 3 * 0.5% = 1.5%
        // 1.5% * 1000 = 15
        
        Payment::factory()->count(5)->create(['subscription_id' => $this->subscription->id, 'status' => 'paid']);
        $payment = Payment::factory()->create(['subscription_id' => $this->subscription->id, 'amount' => 1000]);

        $this->service->calculateAndAwardBonuses($payment);

        $this->assertDatabaseHas('bonus_transactions', [
            'type' => 'progressive',
            'amount' => 15.00
        ]);
    }
}