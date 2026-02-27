<?php
// V-FINAL-1730-TEST-27

namespace Tests\Unit;

use Tests\UnitTestCase;
use App\Services\BonusCalculatorService;
use App\Models\User;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Payment;
use App\Models\Setting;
class BonusCalculatorProgressiveTest extends UnitTestCase
{
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

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_progressive_bonus_not_awarded_before_start_month()
    {
        // Month 3 Payment
        $this->subscription->update(['consecutive_payments_count' => 3]);
        $payment = Payment::factory()->create([
            'subscription_id' => $this->subscription->id, 
            'amount_paise' => 100000,
            'amount' => 1000.00
        ]);

        $this->service->calculateAndAwardBonuses($payment);

        $this->assertDatabaseMissing('bonus_transactions', ['type' => 'progressive']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_progressive_bonus_calculates_correctly_at_month_4()
    {
        // Month 4 Payment
        // Formula: (4 - 4 + 1) * 0.5% * 1000 = 1 * 0.005 * 1000 = 5
        $this->subscription->update(['consecutive_payments_count' => 4]);
        $payment = Payment::factory()->create([
            'subscription_id' => $this->subscription->id, 
            'amount_paise' => 100000,
            'amount' => 1000.00,
            'status' => 'paid'
        ]);

        $this->service->calculateAndAwardBonuses($payment);

        $this->assertDatabaseHas('bonus_transactions', [
            'type' => 'progressive',
            'amount' => 5.00
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_progressive_bonus_increases_linearly_each_month()
    {
        // Month 5 Payment
        // Formula: (5 - 4 + 1) * 0.5% * 1000 = 2 * 0.005 * 1000 = 10
        $this->subscription->update(['consecutive_payments_count' => 5]);
        $payment = Payment::factory()->create([
            'subscription_id' => $this->subscription->id, 
            'amount_paise' => 100000,
            'amount' => 1000.00,
            'status' => 'paid'
        ]);

        $this->service->calculateAndAwardBonuses($payment);

        $this->assertDatabaseHas('bonus_transactions', [
            'type' => 'progressive',
            'amount' => 10.00
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_progressive_bonus_respects_configured_rate()
    {
        // Setup Plan with 1.0% Rate
        $plan = Plan::factory()->create(['monthly_amount' => 1000]);
        $config = ['rate' => 1.0, 'start_month' => 4, 'max_percentage' => 10, 'overrides' => []];
        $plan->configs()->create(['config_key' => 'progressive_config', 'value' => $config]);

        $user = User::factory()->create();
        $subscription = Subscription::factory()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'bonus_multiplier' => 1.0,
            'consecutive_payments_count' => 4
        ]);

        $payment = Payment::factory()->create([
            'subscription_id' => $subscription->id, 
            'amount_paise' => 100000,
            'amount' => 1000.00,
            'status' => 'paid'
        ]);

        $this->service->calculateAndAwardBonuses($payment);

        // Formula: 1 * 1.0% * 1000 = 10
        $this->assertDatabaseHas('bonus_transactions', [
            'type' => 'progressive',
            'amount' => 10.00
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_progressive_bonus_applies_referral_multiplier()
    {
        // Set Multiplier to 2.0
        $this->subscription->update(['bonus_multiplier' => 2.0, 'consecutive_payments_count' => 4]);

        // Month 4 Payment
        // Formula: (5.00 Base Bonus) * 2.0 = 10.00
        $payment = Payment::factory()->create([
            'subscription_id' => $this->subscription->id, 
            'amount_paise' => 100000,
            'amount' => 1000.00,
            'status' => 'paid'
        ]);

        $this->service->calculateAndAwardBonuses($payment);

        $this->assertDatabaseHas('bonus_transactions', [
            'type' => 'progressive',
            'amount' => 10.00,
            'multiplier_applied' => 2.0
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_progressive_bonus_uses_month_override_if_configured()
    {
        // Setup Plan with override
        $plan = Plan::factory()->create(['monthly_amount' => 1000]);
        $config = [
            'rate' => 0.5, 
            'start_month' => 4,
            'max_percentage' => 10,
            'overrides' => [4 => 5.0]
        ];
        $plan->configs()->create(['config_key' => 'progressive_config', 'value' => $config]);

        $user = User::factory()->create();
        $subscription = Subscription::factory()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'bonus_multiplier' => 1.0,
            'consecutive_payments_count' => 4
        ]);

        $payment = Payment::factory()->create([
            'subscription_id' => $subscription->id, 
            'amount_paise' => 100000,
            'amount' => 1000.00,
            'status' => 'paid'
        ]);

        $this->service->calculateAndAwardBonuses($payment);

        // Formula: 5.0% * 1000 = 50.00
        $this->assertDatabaseHas('bonus_transactions', [
            'type' => 'progressive',
            'amount' => 50.00
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_progressive_bonus_caps_at_maximum_percentage()
    {
        // Setup Plan with 1% cap
        $plan = Plan::factory()->create(['monthly_amount' => 1000]);
        $config = ['rate' => 0.5, 'start_month' => 4, 'max_percentage' => 1.0, 'overrides' => []];
        $plan->configs()->create(['config_key' => 'progressive_config', 'value' => $config]);

        $user = User::factory()->create();
        $subscription = Subscription::factory()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'bonus_multiplier' => 1.0,
            'consecutive_payments_count' => 100
        ]);

        $payment = Payment::factory()->create([
            'subscription_id' => $subscription->id, 
            'amount_paise' => 100000,
            'amount' => 1000.00,
            'status' => 'paid'
        ]);

        $this->service->calculateAndAwardBonuses($payment);

        // Should be capped at 1.0% = 10.00
        $this->assertDatabaseHas('bonus_transactions', [
            'type' => 'progressive',
            'amount' => 10.00
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_progressive_bonus_uses_payment_amount_as_base()
    {
        // Month 4 Payment of 5000
        // Formula: 0.5% * 5000 = 25.00
        $this->subscription->update(['consecutive_payments_count' => 4]);
        $payment = Payment::factory()->create([
            'subscription_id' => $this->subscription->id, 
            'amount_paise' => 500000,
            'amount' => 5000.00,
            'status' => 'paid'
        ]);

        $this->service->calculateAndAwardBonuses($payment);

        $this->assertDatabaseHas('bonus_transactions', [
            'type' => 'progressive',
            'amount' => 25.00,
            'base_amount' => 5000.00
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_progressive_bonus_respects_global_toggle()
    {
        // Disable globally
        Setting::updateOrCreate(['key' => 'progressive_bonus_enabled'], ['value' => 'false']);

        // Month 4 Payment
        $this->subscription->update(['consecutive_payments_count' => 4]);
        $payment = Payment::factory()->create([
            'subscription_id' => $this->subscription->id, 
            'amount_paise' => 100000,
            'amount' => 1000.00
        ]);

        $this->service->calculateAndAwardBonuses($payment);

        $this->assertDatabaseMissing('bonus_transactions', ['type' => 'progressive']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_progressive_bonus_formula_evaluation_works()
    {
        // Since we standardized on "Linear + Overrides" instead of unsafe "Eval()",
        // this test confirms the standard linear formula logic works as expected
        // (Month - Start + 1) * Rate
        
        // Month 6 (3rd eligible month)
        // 3 * 0.5% = 1.5%
        // 1.5% * 1000 = 15
        
        $this->subscription->update(['consecutive_payments_count' => 6]);
        $payment = Payment::factory()->create([
            'subscription_id' => $this->subscription->id, 
            'amount_paise' => 100000,
            'amount' => 1000.00,
            'status' => 'paid'
        ]);

        $this->service->calculateAndAwardBonuses($payment);

        $this->assertDatabaseHas('bonus_transactions', [
            'type' => 'progressive',
            'amount' => 15.00
        ]);
    }
}
