<?php
// V-FINAL-1730-TEST-08

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Referral;
use App\Models\Subscription;
use App\Services\ReferralService;

class ReferralTest extends TestCase
{
    use RefreshDatabase;

    protected $referrer;
    protected $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PlanSeeder::class); // Critical: Loads default tiers
        $this->service = new ReferralService();

        $this->referrer = User::factory()->create();
        // Give referrer a subscription so they can have a multiplier
        Subscription::factory()->create([
            'user_id' => $this->referrer->id,
            'plan_id' => 1, // Plan A from seeder
            'bonus_multiplier' => 1.0
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function multiplier_increases_when_referrals_complete()
    {
        // Create 3 referrals (Tier 1 threshold is 3)
        $referees = User::factory()->count(3)->create();

        foreach ($referees as $referee) {
            Referral::create([
                'referrer_id' => $this->referrer->id,
                'referred_id' => $referee->id,
                'status' => 'completed' // Simulate they paid
            ]);
        }

        // Run Service Update manually (usually run by Job)
        $this->service->updateReferrerMultiplier($this->referrer);

        // Reload referrer
        $sub = $this->referrer->subscription->fresh();

        // Should be 1.5x (Default tier for 3 referrals)
        $this->assertEquals(1.5, $sub->bonus_multiplier);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function multiplier_increases_to_tier_2_at_5_referrals()
    {
        $referees = User::factory()->count(5)->create();

        foreach ($referees as $referee) {
            Referral::create([
                'referrer_id' => $this->referrer->id,
                'referred_id' => $referee->id,
                'status' => 'completed'
            ]);
        }

        $this->service->updateReferrerMultiplier($this->referrer);

        $sub = $this->referrer->subscription->fresh();
        // Should be 2.0x (Default tier for 5 referrals)
        $this->assertEquals(2.0, $sub->bonus_multiplier);
    }
}