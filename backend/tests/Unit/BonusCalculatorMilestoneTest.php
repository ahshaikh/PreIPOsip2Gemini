<?php
// V-FINAL-1730-TEST-28

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\BonusCalculatorService;
use App\Models\User;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Payment;
use App\Models\Setting;
class BonusCalculatorMilestoneTest extends TestCase
{
    protected $service;
    protected $plan;
    protected $subscription;

    public function setUp(): void
    {
        parent::setUp();
        $this->service = new BonusCalculatorService();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this->seed(\Database\Seeders\SettingsSeeder::class); // Default settings

        // Setup Plan with Milestone Config
        $this->plan = Plan::factory()->create(['monthly_amount' => 1000]);
        $this->plan->configs()->create([
            'config_key' => 'milestone_config',
            'value' => [
                ['month' => 12, 'amount' => 1000], // 12th month = 1000 bonus
                ['month' => 24, 'amount' => 2000],
                ['month' => 36, 'amount' => 5000],
            ]
        ]);

        // Setup User & Subscription
        $user = User::factory()->create();
        $this->subscription = Subscription::factory()->create([
            'user_id' => $user->id,
            'plan_id' => $this->plan->id,
            'bonus_multiplier' => 1.0,
            'consecutive_payments_count' => 0 // Will update in tests
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_milestone_bonus_awarded_at_month_12()
    {
        // Simulate 11 previous paid payments
        Payment::factory()->count(11)->create(['subscription_id' => $this->subscription->id, 'status' => 'paid']);
        
        // Update streak to 11 (ready for 12th)
        $this->subscription->update(['consecutive_payments_count' => 11]);

        // Process 12th Payment
        $payment = Payment::factory()->create(['subscription_id' => $this->subscription->id, 'amount' => 1000, 'status' => 'pending']);
        
        // Simulate "On Time" payment processing which increments streak to 12
        $this->subscription->increment('consecutive_payments_count'); 
        $payment->update(['status' => 'paid', 'is_on_time' => true]);

        $this->service->calculateAndAwardBonuses($payment);

        $this->assertDatabaseHas('bonus_transactions', [
            'type' => 'milestone',
            'amount' => 1000.00,
            'description' => 'Milestone Bonus'
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_milestone_bonus_awarded_at_month_24()
    {
        Payment::factory()->count(23)->create(['subscription_id' => $this->subscription->id, 'status' => 'paid']);
        $this->subscription->update(['consecutive_payments_count' => 24]); // Streak maintained

        $payment = Payment::factory()->create(['subscription_id' => $this->subscription->id, 'amount' => 1000, 'status' => 'paid', 'is_on_time' => true]);

        $this->service->calculateAndAwardBonuses($payment);

        $this->assertDatabaseHas('bonus_transactions', [
            'type' => 'milestone',
            'amount' => 2000.00
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_milestone_bonus_awarded_at_month_36()
    {
        Payment::factory()->count(35)->create(['subscription_id' => $this->subscription->id, 'status' => 'paid']);
        $this->subscription->update(['consecutive_payments_count' => 36]); 

        $payment = Payment::factory()->create(['subscription_id' => $this->subscription->id, 'amount' => 1000, 'status' => 'paid', 'is_on_time' => true]);

        $this->service->calculateAndAwardBonuses($payment);

        $this->assertDatabaseHas('bonus_transactions', [
            'type' => 'milestone',
            'amount' => 5000.00
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_milestone_bonus_not_awarded_if_non_consecutive()
    {
        // 11 payments made, but streak was broken recently
        Payment::factory()->count(11)->create(['subscription_id' => $this->subscription->id, 'status' => 'paid']);
        $this->subscription->update(['consecutive_payments_count' => 1]); // Broken streak! Only 1 consecutive

        // 12th Payment
        $payment = Payment::factory()->create(['subscription_id' => $this->subscription->id, 'amount' => 1000, 'status' => 'paid']);

        $this->service->calculateAndAwardBonuses($payment);

        $this->assertDatabaseMissing('bonus_transactions', ['type' => 'milestone']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_milestone_bonus_amount_varies_by_plan()
    {
        // Create Plan B with higher milestones
        $planB = Plan::factory()->create(['monthly_amount' => 5000]);
        $planB->configs()->create([
            'config_key' => 'milestone_config',
            'value' => [['month' => 12, 'amount' => 10000]] // Higher bonus
        ]);

        $user = User::factory()->create();
        $subB = Subscription::factory()->create(['user_id' => $user->id, 'plan_id' => $planB->id, 'consecutive_payments_count' => 12]);

        Payment::factory()->count(11)->create(['subscription_id' => $subB->id, 'status' => 'paid']);
        $payment = Payment::factory()->create(['subscription_id' => $subB->id, 'amount' => 5000, 'status' => 'paid', 'is_on_time' => true]);

        $this->service->calculateAndAwardBonuses($payment);

        $this->assertDatabaseHas('bonus_transactions', [
            'user_id' => $user->id,
            'type' => 'milestone',
            'amount' => 10000.00
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_milestone_bonus_applies_referral_multiplier()
    {
        $this->subscription->update(['bonus_multiplier' => 2.5, 'consecutive_payments_count' => 12]);
        Payment::factory()->count(11)->create(['subscription_id' => $this->subscription->id, 'status' => 'paid']);
        
        $payment = Payment::factory()->create(['subscription_id' => $this->subscription->id, 'amount' => 1000, 'status' => 'paid', 'is_on_time' => true]);

        $this->service->calculateAndAwardBonuses($payment);

        // 1000 (Base) * 2.5 (Multiplier) = 2500
        $this->assertDatabaseHas('bonus_transactions', [
            'type' => 'milestone',
            'amount' => 2500.00,
            'multiplier_applied' => 2.5
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_milestone_bonus_uses_configured_amounts()
    {
        // Update config to weird amount
        $this->plan->configs()->update([
            'value' => [['month' => 12, 'amount' => 1234.56]]
        ]);

        $this->subscription->update(['consecutive_payments_count' => 12]);
        Payment::factory()->count(11)->create(['subscription_id' => $this->subscription->id, 'status' => 'paid']);
        
        $payment = Payment::factory()->create(['subscription_id' => $this->subscription->id, 'amount' => 1000, 'status' => 'paid', 'is_on_time' => true]);

        $this->service->calculateAndAwardBonuses($payment);

        $this->assertDatabaseHas('bonus_transactions', [
            'amount' => 1234.56
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_milestone_bonus_supports_custom_milestones()
    {
        // Add an unusual milestone at month 6
        $this->plan->configs()->update([
            'value' => [
                ['month' => 6, 'amount' => 500],
                ['month' => 12, 'amount' => 1000]
            ]
        ]);

        // Simulate Month 6
        Payment::factory()->count(5)->create(['subscription_id' => $this->subscription->id, 'status' => 'paid']);
        $this->subscription->update(['consecutive_payments_count' => 6]);
        
        $payment = Payment::factory()->create(['subscription_id' => $this->subscription->id, 'amount' => 1000, 'status' => 'paid', 'is_on_time' => true]);

        $this->service->calculateAndAwardBonuses($payment);

        $this->assertDatabaseHas('bonus_transactions', [
            'type' => 'milestone',
            'amount' => 500.00
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_milestone_bonus_respects_global_toggle()
    {
        Setting::updateOrCreate(['key' => 'milestone_bonus_enabled'], ['value' => 'false']);

        $this->subscription->update(['consecutive_payments_count' => 12]);
        Payment::factory()->count(11)->create(['subscription_id' => $this->subscription->id, 'status' => 'paid']);
        
        $payment = Payment::factory()->create(['subscription_id' => $this->subscription->id, 'amount' => 1000, 'status' => 'paid', 'is_on_time' => true]);

        $this->service->calculateAndAwardBonuses($payment);

        $this->assertDatabaseMissing('bonus_transactions', ['type' => 'milestone']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_milestone_bonus_only_awarded_once_per_milestone()
    {
        // Month 12 Payment - Awards Bonus
        Payment::factory()->count(11)->create(['subscription_id' => $this->subscription->id, 'status' => 'paid']);
        $this->subscription->update(['consecutive_payments_count' => 12]);
        $payment12 = Payment::factory()->create(['subscription_id' => $this->subscription->id, 'amount' => 1000, 'status' => 'paid', 'is_on_time' => true]);
        
        $this->service->calculateAndAwardBonuses($payment12);
        $this->assertDatabaseCount('bonus_transactions', 1);

        // Month 13 Payment - Should NOT award Month 12 bonus again
        $this->subscription->update(['consecutive_payments_count' => 13]);
        $payment13 = Payment::factory()->create(['subscription_id' => $this->subscription->id, 'amount' => 1000, 'status' => 'paid', 'is_on_time' => true]);
        
        $this->service->calculateAndAwardBonuses($payment13);
        
        // Still only 1 milestone bonus
        $this->assertDatabaseCount('bonus_transactions', 1);
    }
}