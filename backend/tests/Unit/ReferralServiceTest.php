<?php
// V-FINAL-1730-TEST-30

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\ReferralService;
use App\Models\User;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Referral;
class ReferralServiceTest extends TestCase
{
    protected $service;
    protected $referrer;
    protected $planA;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ReferralService();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this->seed(\Database\Seeders\PlanSeeder::class); // Seeds 5-tier system

        // Create main user (referrer) and their subscription
        $this->referrer = User::factory()->create();
        $this->planA = Plan::first();
        Subscription::factory()->create([
            'user_id' => $this->referrer->id,
            'plan_id' => $this->planA->id,
            'bonus_multiplier' => 1.0 // Default
        ]);
    }

    /**
     * Helper to create N completed referrals
     */
    private function createReferrals(int $count)
    {
        $referees = User::factory()->count($count)->create();
        foreach ($referees as $referee) {
            Referral::create([
                'referrer_id' => $this->referrer->id,
                'referred_id' => $referee->id,
                'status' => 'completed'
            ]);
        }
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_referral_multiplier_1x_for_0_to_2_referrals()
    {
        // 0 referrals
        $this->service->updateReferrerMultiplier($this->referrer);
        $this->assertEquals(1.0, $this->referrer->subscription->fresh()->bonus_multiplier);

        // 2 referrals
        $this->createReferrals(2);
        $this->service->updateReferrerMultiplier($this->referrer);
        $this->assertEquals(1.0, $this->referrer->subscription->fresh()->bonus_multiplier);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_referral_multiplier_1_5x_for_3_to_4_referrals()
    {
        // 3 referrals
        $this->createReferrals(3);
        $this->service->updateReferrerMultiplier($this->referrer);
        $this->assertEquals(1.5, $this->referrer->subscription->fresh()->bonus_multiplier);
        
        // 4 referrals
        $this->createReferrals(1); // Add one more
        $this->service->updateReferrerMultiplier($this->referrer);
        $this->assertEquals(1.5, $this->referrer->subscription->fresh()->bonus_multiplier);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_referral_multiplier_2x_for_5_to_9_referrals()
    {
        $this->createReferrals(5);
        $this->service->updateReferrerMultiplier($this->referrer);
        $this->assertEquals(2.0, $this->referrer->subscription->fresh()->bonus_multiplier);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_referral_multiplier_2_5x_for_10_to_19_referrals()
    {
        $this->createReferrals(10);
        $this->service->updateReferrerMultiplier($this->referrer);
        $this->assertEquals(2.5, $this->referrer->subscription->fresh()->bonus_multiplier);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_referral_multiplier_3x_for_20_plus_referrals()
    {
        $this->createReferrals(20);
        $this->service->updateReferrerMultiplier($this->referrer);
        $this->assertEquals(3.0, $this->referrer->subscription->fresh()->bonus_multiplier);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_referral_multiplier_updates_on_referral_count_change()
    {
        // 4 referrals
        $this->createReferrals(4);
        $this->service->updateReferrerMultiplier($this->referrer);
        $this->assertEquals(1.5, $this->referrer->subscription->fresh()->bonus_multiplier);

        // 5th referral joins
        $this->createReferrals(1);
        $this->service->updateReferrerMultiplier($this->referrer);
        
        // Tier should upgrade
        $this->assertEquals(2.0, $this->referrer->subscription->fresh()->bonus_multiplier);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_referral_multiplier_uses_configured_tiers()
    {
        // Create a custom plan with "aggressive" tiers
        $customPlan = Plan::factory()->create();
        $customTiers = [
            ['count' => 0, 'multiplier' => 1.0],
            ['count' => 2, 'multiplier' => 9.9] // 2 refs = 9.9x
        ];
        $customPlan->configs()->create([
            'config_key' => 'referral_tiers', 
            'value' => $customTiers
        ]);
        
        // Assign this user to the custom plan
        $this->referrer->subscription->update(['plan_id' => $customPlan->id]);

        // Give them 2 referrals
        $this->createReferrals(2);
        $this->service->updateReferrerMultiplier($this->referrer);

        // Should use the custom plan's 9.9x, not the default 1.0x
        $this->assertEquals(9.9, $this->referrer->subscription->fresh()->bonus_multiplier);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_referral_multiplier_varies_by_plan()
    {
        // User A (on default plan)
        $this->createReferrals(3);
        $this->service->updateReferrerMultiplier($this->referrer);
        $this->assertEquals(1.5, $this->referrer->subscription->fresh()->bonus_multiplier);

        // User B (on custom plan)
        $userB = User::factory()->create();
        $customPlan = Plan::factory()->create();
        $customTiers = [['count' => 3, 'multiplier' => 5.0]];
        $customPlan->configs()->create(['config_key' => 'referral_tiers', 'value' => $customTiers]);
        Subscription::factory()->create(['user_id' => $userB->id, 'plan_id' => $customPlan->id]);
        
        // Give User B 3 referrals
        $referees = User::factory()->count(3)->create();
        foreach ($referees as $referee) {
            Referral::create(['referrer_id' => $userB->id, 'referred_id' => $referee->id, 'status' => 'completed']);
        }
        
        $this->service->updateReferrerMultiplier($userB);
        
        // User B has a different multiplier for the same referral count
        $this->assertEquals(5.0, $userB->subscription->fresh()->bonus_multiplier);
    }
}