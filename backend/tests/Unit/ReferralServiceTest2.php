<?php
// V-FINAL-1730-TEST-47 (Created)

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\ReferralService;
use App\Models\User;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Referral;
use App\Models\ReferralCampaign;
use App\Models\BonusTransaction;
use App\Models\Transaction;
use App\Jobs\ProcessReferralJob;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\DB;

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
        $this->referrer->wallet()->create(['balance_paise' => 0, 'locked_balance_paise' => 0]); // Ensure wallet exists
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
    public function test_calculate_multiplier_based_on_count()
    {
        // 0 referrals
        $this->service->updateReferrerMultiplier($this->referrer);
        $this->assertEquals(1.0, $this->referrer->subscription->fresh()->bonus_multiplier);

        // 3 referrals
        $this->createReferrals(3);
        $this->service->updateReferrerMultiplier($this->referrer);
        $this->assertEquals(1.5, $this->referrer->subscription->fresh()->bonus_multiplier);

        // 5 referrals
        $this->createReferrals(2); // (Total 5)
        $this->service->updateReferrerMultiplier($this->referrer);
        $this->assertEquals(2.0, $this->referrer->subscription->fresh()->bonus_multiplier);

        // 10 referrals
        $this->createReferrals(5); // (Total 10)
        $this->service->updateReferrerMultiplier($this->referrer);
        $this->assertEquals(2.5, $this->referrer->subscription->fresh()->bonus_multiplier);

        // 20 referrals
        $this->createReferrals(10); // (Total 20)
        $this->service->updateReferrerMultiplier($this->referrer);
        $this->assertEquals(3.0, $this->referrer->subscription->fresh()->bonus_multiplier);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_update_multiplier_on_referral_activation()
    {
        // This test simulates the ProcessReferralJob, which *calls* the service
        Queue::fake(); // We don't need to run other jobs

        // 1. Create a "pending" referral
        $referee = User::factory()->create();
        Referral::create([
            'referrer_id' => $this->referrer->id,
            'referred_id' => $referee->id,
            'status' => 'pending'
        ]);
        
        $this->assertEquals(1.0, $this->referrer->subscription->fresh()->bonus_multiplier);

        // 2. Run the Job
        $job = new ProcessReferralJob($referee);
        $this->service = new ReferralService(); // Need a fresh instance for injection
        $job->handle($this->service);
        
        // 3. Assert
        // The user now has 1 referral, which is < 3, so multiplier stays 1.0
        $this->assertEquals(1.0, $this->referrer->subscription->fresh()->bonus_multiplier);
        
        // 4. Now, test the upgrade
        // Create 2 more completed referrals
        $this->createReferrals(2);
        
        // Run the Job for the first referee again (it won't run, but we re-run the service logic)
        $this->service->updateReferrerMultiplier($this->referrer);
        
        // Total referrals = 1 + 2 = 3. Multiplier should be 1.5x
        $this->assertEquals(1.5, $this->referrer->subscription->fresh()->bonus_multiplier);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_award_campaign_bonus_if_applicable()
    {
        // 1. Create an active campaign
        $campaign = ReferralCampaign::factory()->create([
            'start_date' => now()->subDay(),
            'end_date' => now()->addDay(),
            'multiplier' => 5.0, // 5x multiplier
            'bonus_amount' => 1000 // Extra ₹1000
        ]);
        
        // 2. Create a pending referral
        $referee = User::factory()->create();
        Referral::create([
            'referrer_id' => $this->referrer->id,
            'referred_id' => $referee->id,
            'status' => 'pending'
        ]);

        // 3. Run the Job
        $job = new ProcessReferralJob($referee);
        $this->service = new ReferralService();
        $job->handle($this->service);
        
        // 4. Assert
        // A. Bonus Amount: 500 (base) + 1000 (campaign) = ₹1500 = 150000 paise
        $this->assertDatabaseHas('wallets', [
            'user_id' => $this->referrer->id,
            'balance_paise' => 150000
        ]);
        $this->assertDatabaseHas('bonus_transactions', [
            'user_id' => $this->referrer->id,
            'amount' => 1500,
            'description' => "Referral Bonus: {$referee->username} (Campaign: {$campaign->name})"
        ]);

        // B. Multiplier: Should be 5.0x (from campaign), not 1.0x (from tiers)
        $this->assertEquals(5.0, $this->referrer->subscription->fresh()->bonus_multiplier);
    }
}