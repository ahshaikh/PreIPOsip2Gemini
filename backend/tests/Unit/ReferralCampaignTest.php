<?php
// V-FINAL-1730-TEST-46 (Created)

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Referral;
use App\Models\ReferralCampaign;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class ReferralCampaignTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_campaign_validates_date_range()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("End date cannot be before start date");

        ReferralCampaign::create([
            'name' => 'Bad Campaign',
            'start_date' => now(),
            'end_date' => now()->subDay(), // End date is in the past
            'multiplier' => 2,
            'bonus_amount' => 100
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_campaign_validates_bonus_amount_positive()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Bonus amount cannot be negative");

        ReferralCampaign::create([
            'name' => 'Bad Campaign 2',
            'start_date' => now(),
            'end_date' => now()->addDay(),
            'multiplier' => 2,
            'bonus_amount' => -100 // Invalid
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_campaign_checks_if_active()
    {
        // 1. Past Campaign
        ReferralCampaign::factory()->create([
            'start_date' => now()->subDays(10),
            'end_date' => now()->subDays(5)
        ]);
        
        // 2. Future Campaign
        ReferralCampaign::factory()->create([
            'start_date' => now()->addDays(5),
            'end_date' => now()->addDays(10)
        ]);
        
        // 3. Active Campaign
        $active = ReferralCampaign::factory()->create([
            'start_date' => now()->subDay(),
            'end_date' => now()->addDay()
        ]);
        
        // 4. Inactive Campaign
        ReferralCampaign::factory()->create([
            'start_date' => now()->subDay(),
            'end_date' => now()->addDay(),
            'is_active' => false
        ]);

        $running = ReferralCampaign::running()->get();
        
        $this->assertEquals(1, $running->count());
        $this->assertEquals($active->id, $running->first()->id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_campaign_tracks_total_referrals()
    {
        $campaign = ReferralCampaign::factory()->create();
        
        // Create 3 referrals and link them to this campaign
        $referrer = User::factory()->create();
        $referees = User::factory()->count(3)->create();

        foreach ($referees as $referee) {
            Referral::create([
                'referrer_id' => $referrer->id,
                'referred_id' => $referee->id,
                'status' => 'completed',
                'referral_campaign_id' => $campaign->id // Link
            ]);
        }

        // Test the relationship
        $this->assertEquals(3, $campaign->referrals()->count());
    }
}