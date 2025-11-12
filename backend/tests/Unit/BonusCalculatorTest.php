<?php
// V-DEPLOY-1730-008

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\BonusCalculatorService;
use App\Models\User;
use App\Models\Plan;
use App\Models\PlanConfig;
use App\Models\Subscription;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BonusCalculatorTest extends TestCase
{
    use RefreshDatabase;

    protected $service;
    protected $user;
    protected $plan;
    protected $subscription;

    public function setUp(): void
    {
        parent::setUp();
        
        // 1. Setup our service
        $this->service = new BonusCalculatorService();

        // 2. Setup a dummy user, plan, and subscription
        $this->user = User::factory()->create();
        $this->plan = Plan::factory()->create();
        $this->subscription = Subscription::factory()->create([
            'user_id' => $this->user->id,
            'plan_id' => $this->plan->id,
            'bonus_multiplier' => 1.0,
            'consecutive_payments_count' => 0,
        ]);

        // 3. Setup the configurable logic for the plan
        $this->plan->configs()->create([
            'config_key' => 'progressive_config',
            'value' => json_encode(['rate' => 0.5, 'start_month' => 4])
        ]);
        $this->plan->configs()->create([
            'config_key' => 'milestone_config',
            'value' => json_encode([['month' => 12, 'amount' => 1000]])
        ]);
        $this->plan->configs()->create([
            'config_key' => 'consistency_config',
            'value' => json_encode(['amount_per_payment' => 10, 'streaks' => []])
        ]);
    }

    /**
     * Test that no bonus is given before the start month.
     */
    public function test_no_bonus_before_start_month(): void
    {
        // This is the 3rd payment
        Payment::factory()->count(2)->create(['subscription_id' => $this->subscription->id, 'status' => 'paid']);
        $payment = Payment::factory()->create(['subscription_id' => $this->subscription->id, 'amount' => 1000]);
        
        $totalBonus = $this->service->calculateAndAwardBonuses($payment);

        $this->assertDatabaseMissing('bonus_transactions', ['type' => 'progressive']);
        $this->assertEquals(0, $totalBonus);
    }

    /**
     * Test that the first progressive bonus is calculated correctly on month 4.
     */
    public function test_calculates_progressive_bonus_on_month_4(): void
    {
        // This is the 4th payment
        Payment::factory()->count(3)->create(['subscription_id' => $this->subscription->id, 'status' => 'paid']);
        $payment = Payment::factory()->create([
            'subscription_id' => $this->subscription->id, 
            'amount' => 1000,
            'is_on_time' => true,
        ]);
        
        $totalBonus = $this->service->calculateAndAwardBonuses($payment);

        // (month 4 - start_month 4 + 1) * 0.005 * 1000 * 1.0 = 1 * 0.005 * 1000 = 5
        $expectedProgressive = 5.00;
        // Consistency bonus
        $expectedConsistency = 10.00;
        
        $this->assertEquals($expectedProgressive + $expectedConsistency, $totalBonus);
        $this->assertDatabaseHas('bonus_transactions', [
            'type' => 'progressive',
            'amount' => $expectedProgressive
        ]);
    }
    
    /**
     * Test that the referral multiplier is correctly applied.
     */
    public function test_referral_multiplier_applies_to_bonuses(): void
    {
        // Set multiplier to 2.0x
        $this->subscription->update(['bonus_multiplier' => 2.0]);
        
        // This is the 4th payment
        Payment::factory()->count(3)->create(['subscription_id' => $this->subscription->id, 'status' => 'paid']);
        $payment = Payment::factory()->create([
            'subscription_id' => $this->subscription->id, 
            'amount' => 1000,
            'is_on_time' => true,
        ]);
        
        $totalBonus = $this->service->calculateAndAwardBonuses($payment);

        // (month 4 - start_month 4 + 1) * 0.005 * 1000 * 2.0 (multiplier) = 10
        $expectedProgressive = 10.00;
        // Consistency bonus (multiplier NOT applied)
        $expectedConsistency = 10.00;
        
        $this->assertEquals($expectedProgressive + $expectedConsistency, $totalBonus);
        $this->assertDatabaseHas('bonus_transactions', [
            'type' => 'progressive',
            'amount' => $expectedProgressive,
            'multiplier_applied' => 2.0
        ]);
    }
}