<?php
// V-FINAL-1730-TEST-28-FIXED

namespace Tests\Unit;

use Tests\UnitTestCase;
use App\Services\BonusCalculatorService;
use App\Models\User;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\BonusTransaction;

class BonusCalculatorMilestoneTest extends UnitTestCase
{
    protected $service;
    protected $plan;
    protected $subscription;

    public function setUp(): void
    {
        parent::setUp();
        $this->service = new BonusCalculatorService();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this->seed(\Database\Seeders\SettingsSeeder::class);

        $this->plan = Plan::factory()->create(['monthly_amount' => 1000]);
        $this->plan->configs()->create([
            'config_key' => 'milestone_config',
            'value' => [
                ['month' => 12, 'amount' => 1000],
                ['month' => 24, 'amount' => 2000],
                ['month' => 36, 'amount' => 5000],
            ]
        ]);

        $user = User::factory()->create();
        $this->subscription = Subscription::factory()->create([
            'user_id' => $user->id,
            'plan_id' => $this->plan->id,
            'bonus_multiplier' => 1.0,
            'consecutive_payments_count' => 0
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_milestone_bonus_awarded_at_month_12()
    {
        Payment::factory()->count(11)->create(['subscription_id' => $this->subscription->id, 'status' => 'paid']);
        $this->subscription->update(['consecutive_payments_count' => 11]);

        $payment = Payment::factory()->create(['subscription_id' => $this->subscription->id, 'amount_paise' => 100000, 'status' => 'pending']); 
        $this->subscription->increment('consecutive_payments_count'); 
        $payment->update(['status' => 'paid', 'is_on_time' => true]);

        $this->service->calculateAndAwardBonuses($payment);

        $this->assertDatabaseHas('bonus_transactions', [
            'type' => 'milestone_bonus',
            'amount' => 1000.00,
            'description' => 'Milestone Bonus - Payment #12'
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_milestone_bonus_awarded_at_month_24()
    {
        Payment::factory()->count(23)->create(['subscription_id' => $this->subscription->id, 'status' => 'paid']);
        $this->subscription->update(['consecutive_payments_count' => 24]); 

        $payment = Payment::factory()->create(['subscription_id' => $this->subscription->id, 'amount_paise' => 100000, 'status' => 'paid', 'is_on_time' => true]);

        $this->service->calculateAndAwardBonuses($payment);

        $this->assertDatabaseHas('bonus_transactions', [
            'type' => 'milestone_bonus',
            'amount' => 2000.00
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_milestone_bonus_awarded_at_month_36()
    {
        Payment::factory()->count(35)->create(['subscription_id' => $this->subscription->id, 'status' => 'paid']);
        $this->subscription->update(['consecutive_payments_count' => 36]); 

        $payment = Payment::factory()->create(['subscription_id' => $this->subscription->id, 'amount_paise' => 100000, 'status' => 'paid', 'is_on_time' => true]);

        $this->service->calculateAndAwardBonuses($payment);

        $this->assertDatabaseHas('bonus_transactions', [
            'type' => 'milestone_bonus',
            'amount' => 5000.00
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_milestone_bonus_not_awarded_if_non_consecutive()
    {
        Payment::factory()->count(11)->create(['subscription_id' => $this->subscription->id, 'status' => 'paid']);
        $this->subscription->update(['consecutive_payments_count' => 1]); 

        $payment = Payment::factory()->create(['subscription_id' => $this->subscription->id, 'amount_paise' => 100000, 'status' => 'paid']);

        $this->service->calculateAndAwardBonuses($payment);

        $this->assertDatabaseMissing('bonus_transactions', ['type' => 'milestone_bonus']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_milestone_bonus_amount_varies_by_plan()
    {
        $planB = Plan::factory()->create(['monthly_amount' => 5000]);
        $planB->configs()->create([
            'config_key' => 'milestone_config',
            'value' => [['month' => 12, 'amount' => 10000]]
        ]);

        $userB = User::factory()->create();
        $subB = Subscription::factory()->create(['user_id' => $userB->id, 'plan_id' => $planB->id, 'consecutive_payments_count' => 12]);

        Payment::factory()->count(11)->create(['subscription_id' => $subB->id, 'status' => 'paid']);
        $payment = Payment::factory()->create(['subscription_id' => $subB->id, 'amount_paise' => 500000, 'status' => 'paid', 'is_on_time' => true]);

        $this->service->calculateAndAwardBonuses($payment);

        $this->assertDatabaseHas('bonus_transactions', [
            'user_id' => $userB->id,
            'type' => 'milestone_bonus',
            'amount' => 10000.00
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_milestone_bonus_applies_referral_multiplier()
    {
        $this->subscription->update(['bonus_multiplier' => 2.5, 'consecutive_payments_count' => 12]);
        Payment::factory()->count(11)->create(['subscription_id' => $this->subscription->id, 'status' => 'paid']);
        
        $payment = Payment::factory()->create(['subscription_id' => $this->subscription->id, 'amount_paise' => 100000, 'status' => 'paid', 'is_on_time' => true]);

        $this->service->calculateAndAwardBonuses($payment);

        $this->assertDatabaseHas('bonus_transactions', [
            'type' => 'milestone_bonus',
            'amount' => 2500.00,
            'multiplier_applied' => 2.5
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_milestone_bonus_uses_configured_amounts()
    {
        $this->plan->configs()->where('config_key', 'milestone_config')->first()->update([
            'value' => [['month' => 12, 'amount' => 1234.56]]
        ]);
        $this->plan->unsetRelation('configs');

        $this->subscription->update(['consecutive_payments_count' => 12]);
        Payment::factory()->count(11)->create(['subscription_id' => $this->subscription->id, 'status' => 'paid']);
        
        $payment = Payment::factory()->create(['subscription_id' => $this->subscription->id, 'amount_paise' => 100000, 'status' => 'paid', 'is_on_time' => true]);

        $this->service->calculateAndAwardBonuses($payment);

        $this->assertDatabaseHas('bonus_transactions', [
            'type' => 'milestone_bonus',
            'amount' => 1234.56
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_milestone_bonus_supports_custom_milestones()
    {
        $this->plan->configs()->where('config_key', 'milestone_config')->first()->update([
            'value' => [
                ['month' => 6, 'amount' => 500],
                ['month' => 12, 'amount' => 1000]
            ]
        ]);
        $this->plan->unsetRelation('configs');

        Payment::factory()->count(5)->create(['subscription_id' => $this->subscription->id, 'status' => 'paid']);
        $this->subscription->update(['consecutive_payments_count' => 6]);
        
        $payment = Payment::factory()->create(['subscription_id' => $this->subscription->id, 'amount_paise' => 100000, 'status' => 'paid', 'is_on_time' => true]);

        $this->service->calculateAndAwardBonuses($payment);

        $this->assertDatabaseHas('bonus_transactions', [
            'type' => 'milestone_bonus',
            'amount' => 500.00
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_milestone_bonus_respects_global_toggle()
    {
        Setting::updateOrCreate(['key' => 'milestone_bonus_enabled'], ['value' => 'false']);

        $this->subscription->update(['consecutive_payments_count' => 12]);
        Payment::factory()->count(11)->create(['subscription_id' => $this->subscription->id, 'status' => 'paid']);
        
        $payment = Payment::factory()->create(['subscription_id' => $this->subscription->id, 'amount_paise' => 100000, 'status' => 'paid', 'is_on_time' => true]);

        $this->service->calculateAndAwardBonuses($payment);

        $this->assertDatabaseMissing('bonus_transactions', ['type' => 'milestone_bonus']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_milestone_bonus_only_awarded_once_per_milestone()
    {
        Payment::factory()->count(11)->create(['subscription_id' => $this->subscription->id, 'status' => 'paid']);
        $this->subscription->update(['consecutive_payments_count' => 12]);
        $payment12 = Payment::factory()->create(['subscription_id' => $this->subscription->id, 'amount_paise' => 100000, 'status' => 'paid', 'is_on_time' => true]);
        
        $this->service->calculateAndAwardBonuses($payment12);
        
        $this->subscription->update(['consecutive_payments_count' => 13]);
        $payment13 = Payment::factory()->create(['subscription_id' => $this->subscription->id, 'amount_paise' => 100000, 'status' => 'paid', 'is_on_time' => true]);
        
        $this->service->calculateAndAwardBonuses($payment13);
        
        $count = BonusTransaction::where('subscription_id', $this->subscription->id)
                                 ->where('type', 'milestone_bonus')
                                 ->count();
                                 
        $this->assertEquals(1, $count);
    }
}