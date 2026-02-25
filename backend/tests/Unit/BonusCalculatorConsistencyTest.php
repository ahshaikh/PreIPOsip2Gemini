<?php
// V-FINAL-1730-TEST-29

namespace Tests\Unit;

use Tests\UnitTestCase;
use App\Services\BonusCalculatorService;
use App\Models\User;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Payment;
use App\Models\Setting;
class BonusCalculatorConsistencyTest extends UnitTestCase
{
    protected $service;
    protected $plan;
    protected $subscription;

    public function setUp(): void
    {
        parent::setUp();
        $this->service = new BonusCalculatorService();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this->seed(\Database\Seeders\SettingsSeeder::class); // For global toggle

        // Setup Plan with Consistency Config
        $this->plan = Plan::factory()->create(['monthly_amount' => 1000]);
        $this->plan->configs()->create([
            'config_key' => 'consistency_config',
            'value' => [
                'amount_per_payment' => 100, // ₹100 base
                'streaks' => [
                    ['months' => 6, 'multiplier' => 3], // 6 months = ₹300
                    ['months' => 12, 'multiplier' => 5] // 12 months = ₹500
                ]
            ]
        ]);

        $user = User::factory()->create();
        $this->subscription = Subscription::factory()->create([
            'user_id' => $user->id,
            'plan_id' => $this->plan->id,
            'consecutive_payments_count' => 0
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_consistency_bonus_awarded_for_on_time_payment()
    {
        $payment = Payment::factory()->create([
            'subscription_id' => $this->subscription->id,
            'is_on_time' => true,
            'amount' => 1000
        ]);

        $this->service->calculateAndAwardBonuses($payment);

        $this->assertDatabaseHas('bonus_transactions', [
            'type' => 'consistency',
            'amount' => 100.00
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_consistency_bonus_not_awarded_for_late_payment()
    {
        $payment = Payment::factory()->create([
            'subscription_id' => $this->subscription->id,
            'is_on_time' => false, // LATE
            'amount' => 1000
        ]);

        $this->service->calculateAndAwardBonuses($payment);

        $this->assertDatabaseMissing('bonus_transactions', ['type' => 'consistency']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_consistency_bonus_amount_varies_by_plan()
    {
        // Plan B gives ₹500
        $planB = Plan::factory()->create();
        $planB->configs()->create([
            'config_key' => 'consistency_config',
            'value' => ['amount_per_payment' => 500]
        ]);
        $subB = Subscription::factory()->create(['plan_id' => $planB->id]);

        $payment = Payment::factory()->create([
            'subscription_id' => $subB->id,
            'is_on_time' => true,
            'amount' => 5000
        ]);

        $this->service->calculateAndAwardBonuses($payment);

        $this->assertDatabaseHas('bonus_transactions', [
            'type' => 'consistency',
            'amount' => 500.00
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_consistency_bonus_multiplies_for_streak()
    {
        // Set streak to 6 months
        $this->subscription->update(['consecutive_payments_count' => 6]);
        
        $payment = Payment::factory()->create([
            'subscription_id' => $this->subscription->id,
            'is_on_time' => true,
            'amount' => 1000
        ]);

        $this->service->calculateAndAwardBonuses($payment);

        // Base (100) * Streak (3x) = 300
        $this->assertDatabaseHas('bonus_transactions', [
            'type' => 'consistency',
            'amount' => 300.00
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_consistency_bonus_streak_resets_on_late_payment()
    {
        // This test confirms the *result* of a reset streak
        // A late payment (in PaymentWebhookService) would set streak to 0
        
        // This is the 6th payment, but the streak was reset
        $this->subscription->update(['consecutive_payments_count' => 0]); 
        
        $payment = Payment::factory()->create([
            'subscription_id' => $this->subscription->id,
            'is_on_time' => true, // User pays on time *this* month
            'amount' => 1000
        ]);

        $this->service->calculateAndAwardBonuses($payment);

        // Should only get base amount (100), not streak amount (300)
        $this->assertDatabaseHas('bonus_transactions', [
            'type' => 'consistency',
            'amount' => 100.00
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_consistency_bonus_uses_configured_amounts()
    {
        // V-WAVE3-FIX: Create a plan with custom consistency config, then create subscription
        // Per CONTRACT-HARDENING, subscription snapshots are immutable and captured at creation
        $customPlan = Plan::factory()->create();
        $customPlan->configs()->create([
            'config_key' => 'consistency_config',
            'value' => ['amount_per_payment' => 123.45, 'streaks' => []]
        ]);

        // Create NEW subscription - factory will snapshot the plan's config
        $customSub = Subscription::factory()->create([
            'user_id' => $this->subscription->user_id,
            'plan_id' => $customPlan->id,
        ]);

        $payment = Payment::factory()->create([
            'subscription_id' => $customSub->id,
            'is_on_time' => true,
            'amount' => 1000
        ]);

        $this->service->calculateAndAwardBonuses($payment);

        $this->assertDatabaseHas('bonus_transactions', [
            'type' => 'consistency',
            'amount' => 123.45
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_consistency_bonus_respects_grace_period()
    {
        // The service *itself* only checks the `is_on_time` boolean.
        // This test confirms that if `is_on_time` is true (even if late),
        // the bonus is still awarded. The grace period logic is in PaymentWebhookService.
        
        $payment = Payment::factory()->create([
            'subscription_id' => $this->subscription->id,
            'is_on_time' => true, // Simulates a payment made within the grace period
            'paid_at' => now()->addDays(1), // Technically late
            'amount' => 1000
        ]);

        $this->service->calculateAndAwardBonuses($payment);

        $this->assertDatabaseHas('bonus_transactions', ['type' => 'consistency']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_consistency_bonus_respects_global_toggle()
    {
        // Turn off this bonus type
        Setting::updateOrCreate(['key' => 'consistency_bonus_enabled'], ['value' => 'false']);
        
        $payment = Payment::factory()->create([
            'subscription_id' => $this->subscription->id,
            'is_on_time' => true,
            'amount' => 1000
        ]);

        $this->service->calculateAndAwardBonuses($payment);

        $this->assertDatabaseMissing('bonus_transactions', ['type' => 'consistency']);
    }
}
