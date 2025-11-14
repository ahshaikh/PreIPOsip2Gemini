<?php
// V-FINAL-1730-TEST-01

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\BonusCalculatorService;
use App\Models\User;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BonusCalculatorTest extends TestCase
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
        
        // Setup a Plan with specific rules for testing
        $this->plan = Plan::factory()->create(['monthly_amount' => 5000]);
        $this->plan->configs()->createMany([
            ['config_key' => 'progressive_config', 'value' => ['rate' => 0.5, 'start_month' => 4]],
            ['config_key' => 'milestone_config', 'value' => [['month' => 12, 'amount' => 2000]]],
            ['config_key' => 'consistency_config', 'value' => ['amount_per_payment' => 50]]
        ]);

        $user = User::factory()->create();
        $this->subscription = Subscription::factory()->create([
            'user_id' => $user->id,
            'plan_id' => $this->plan->id,
            'bonus_multiplier' => 1.0,
            'consecutive_payments_count' => 0,
        ]);
    }

    /** @test */
    public function it_does_not_award_progressive_bonus_before_start_month()
    {
        // Simulate Month 3 (Start month is 4)
        Payment::factory()->count(2)->create(['subscription_id' => $this->subscription->id, 'status' => 'paid']);
        $payment = Payment::factory()->create(['subscription_id' => $this->subscription->id, 'amount' => 5000, 'is_on_time' => true]);

        $this->service->calculateAndAwardBonuses($payment);

        $this->assertDatabaseMissing('bonus_transactions', ['type' => 'progressive']);
        // Should still get Consistency bonus (50)
        $this->assertDatabaseHas('bonus_transactions', ['type' => 'consistency', 'amount' => 50]);
    }

    /** @test */
    public function it_awards_correct_progressive_bonus_on_month_4()
    {
        // Simulate Month 4
        Payment::factory()->count(3)->create(['subscription_id' => $this->subscription->id, 'status' => 'paid']);
        $payment = Payment::factory()->create(['subscription_id' => $this->subscription->id, 'amount' => 5000, 'is_on_time' => true]);

        $this->service->calculateAndAwardBonuses($payment);

        // Formula: (Month 4 - Start 4 + 1) * 0.5% * 5000 = 1 * 0.005 * 5000 = 25
        $this->assertDatabaseHas('bonus_transactions', [
            'type' => 'progressive',
            'amount' => 25
        ]);
    }

    /** @test */
    public function it_multiplies_bonuses_based_on_referral_tier()
    {
        // Set 2.0x Multiplier
        $this->subscription->update(['bonus_multiplier' => 2.0]);

        // Simulate Month 4
        Payment::factory()->count(3)->create(['subscription_id' => $this->subscription->id, 'status' => 'paid']);
        $payment = Payment::factory()->create(['subscription_id' => $this->subscription->id, 'amount' => 5000, 'is_on_time' => true]);

        $this->service->calculateAndAwardBonuses($payment);

        // Base 25 * 2.0 = 50
        $this->assertDatabaseHas('bonus_transactions', [
            'type' => 'progressive',
            'amount' => 50,
            'multiplier_applied' => 2.0
        ]);
    }

    /** @test */
    public function it_awards_milestone_bonus_only_on_exact_month()
    {
        // Simulate Month 12
        Payment::factory()->count(11)->create(['subscription_id' => $this->subscription->id, 'status' => 'paid']);
        // Set consecutive payments to 12 to match requirement
        $this->subscription->update(['consecutive_payments_count' => 12]);
        
        $payment = Payment::factory()->create(['subscription_id' => $this->subscription->id, 'amount' => 5000, 'is_on_time' => true]);

        $this->service->calculateAndAwardBonuses($payment);

        $this->assertDatabaseHas('bonus_transactions', [
            'type' => 'milestone',
            'amount' => 2000
        ]);
    }
}