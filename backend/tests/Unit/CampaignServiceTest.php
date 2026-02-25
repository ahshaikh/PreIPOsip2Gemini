<?php

namespace Tests\Unit;

use Tests\UnitTestCase;
use App\Models\Campaign;
use App\Models\CampaignUsage;
use App\Models\User;
use App\Models\Investment;
use App\Services\CampaignService;
use Illuminate\Support\Facades\DB;

class CampaignServiceTest extends UnitTestCase
{
    protected CampaignService $campaignService;
    protected User $user;
    protected Campaign $campaign;

    protected function setUp(): void
    {
        parent::setUp();
        $this->campaignService = new CampaignService();

        // Create a test user
        $this->user = User::factory()->create();

        // Create a base campaign for testing
        $this->campaign = Campaign::create([
            'title' => 'Test Campaign',
            'code' => 'TEST100',
            'description' => 'Test campaign for unit tests',
            'discount_type' => 'fixed_amount',
            'discount_amount' => 100.00,
            'min_investment' => 1000.00,
            'usage_limit' => 10,
            'user_usage_limit' => 1,
            'start_at' => now()->subDay(),
            'end_at' => now()->addDays(7),
            'is_active' => true,
            'approved_by' => $this->user->id,
            'approved_at' => now(),
            'created_by' => $this->user->id,
        ]);
    }

    public function it_validates_campaign_code_successfully()
    {
        $campaign = $this->campaignService->validateCampaignCode('TEST100');

        $this->assertNotNull($campaign);
        $this->assertEquals('TEST100', $campaign->code);
    }

    public function it_returns_null_for_invalid_campaign_code()
    {
        $campaign = $this->campaignService->validateCampaignCode('INVALID_CODE');

        $this->assertNull($campaign);
    }

    public function it_checks_campaign_is_applicable_for_valid_conditions()
    {
        $result = $this->campaignService->isApplicable($this->campaign, $this->user, 1500.00);

        $this->assertTrue($result['applicable']);
        $this->assertNull($result['reason']);
    }

    public function it_rejects_unapproved_campaign()
    {
        $this->campaign->update(['approved_at' => null]);

        $result = $this->campaignService->isApplicable($this->campaign, $this->user, 1500.00);

        $this->assertFalse($result['applicable']);
        $this->assertStringContainsString('not yet approved', $result['reason']);
    }

    public function it_rejects_inactive_campaign()
    {
        $this->campaign->update(['is_active' => false]);

        $result = $this->campaignService->isApplicable($this->campaign, $this->user, 1500.00);

        $this->assertFalse($result['applicable']);
        $this->assertStringContainsString('paused', $result['reason']);
    }

    public function it_rejects_campaign_that_has_not_started()
    {
        $this->campaign->update(['start_at' => now()->addDay()]);

        $result = $this->campaignService->isApplicable($this->campaign, $this->user, 1500.00);

        $this->assertFalse($result['applicable']);
        $this->assertStringContainsString('not started yet', $result['reason']);
    }

    public function it_rejects_expired_campaign()
    {
        $this->campaign->update(['end_at' => now()->subDay()]);

        $result = $this->campaignService->isApplicable($this->campaign, $this->user, 1500.00);

        $this->assertFalse($result['applicable']);
        $this->assertStringContainsString('expired', $result['reason']);
    }

    public function it_rejects_campaign_when_global_usage_limit_reached()
    {
        $this->campaign->update(['usage_count' => 10, 'usage_limit' => 10]);

        $result = $this->campaignService->isApplicable($this->campaign, $this->user, 1500.00);

        $this->assertFalse($result['applicable']);
        $this->assertStringContainsString('usage limit has been reached', $result['reason']);
    }

    public function it_rejects_campaign_when_user_usage_limit_reached()
    {
        // Create a usage record for this user
        CampaignUsage::create([
            'campaign_id' => $this->campaign->id,
            'user_id' => $this->user->id,
            'applicable_type' => Investment::class,
            'applicable_id' => 1,
            'original_amount' => 1500.00,
            'discount_applied' => 100.00,
            'final_amount' => 1400.00,
            'campaign_code' => 'TEST100',
            'used_at' => now(),
        ]);

        $result = $this->campaignService->isApplicable($this->campaign, $this->user, 1500.00);

        $this->assertFalse($result['applicable']);
        $this->assertStringContainsString('already used this campaign', $result['reason']);
    }

    public function it_rejects_campaign_when_minimum_investment_not_met()
    {
        $result = $this->campaignService->isApplicable($this->campaign, $this->user, 500.00);

        $this->assertFalse($result['applicable']);
        $this->assertStringContainsString('Minimum investment', $result['reason']);
    }

    public function it_calculates_fixed_amount_discount_correctly()
    {
        $discount = $this->campaignService->calculateDiscount($this->campaign, 1500.00);

        $this->assertEquals(100.00, $discount);
    }

    public function it_calculates_percentage_discount_correctly()
    {
        $this->campaign->update([
            'discount_type' => 'percentage',
            'discount_percent' => 10.00,
            'discount_amount' => null,
        ]);

        $discount = $this->campaignService->calculateDiscount($this->campaign, 1000.00);

        $this->assertEquals(100.00, $discount);
    }

    public function it_applies_maximum_discount_cap_for_percentage_discount()
    {
        $this->campaign->update([
            'discount_type' => 'percentage',
            'discount_percent' => 20.00,
            'discount_amount' => null,
            'max_discount' => 150.00,
        ]);

        $discount = $this->campaignService->calculateDiscount($this->campaign, 1000.00);

        // 20% of 1000 = 200, but capped at 150
        $this->assertEquals(150.00, $discount);
    }

    public function it_ensures_discount_does_not_exceed_amount()
    {
        $this->campaign->update([
            'discount_amount' => 500.00,
        ]);

        $discount = $this->campaignService->calculateDiscount($this->campaign, 300.00);

        // Discount capped at amount
        $this->assertEquals(300.00, $discount);
    }

    public function it_applies_campaign_successfully()
    {
        $investment = Investment::factory()->create([
            'user_id' => $this->user->id,
            'total_amount' => 1500.00,
        ]);

        $result = $this->campaignService->applyCampaign(
            $this->campaign,
            $this->user,
            $investment,
            1500.00
        );

        $this->assertTrue($result['success']);
        $this->assertEquals(100.00, $result['discount']);
        $this->assertInstanceOf(CampaignUsage::class, $result['usage']);

        // Verify usage record was created
        $this->assertDatabaseHas('campaign_usages', [
            'campaign_id' => $this->campaign->id,
            'user_id' => $this->user->id,
            'applicable_type' => Investment::class,
            'applicable_id' => $investment->id,
            'discount_applied' => 100.00,
        ]);

        // Verify usage count was incremented
        $this->assertEquals(1, $this->campaign->fresh()->usage_count);
    }

    public function it_prevents_duplicate_campaign_application()
    {
        $investment = Investment::factory()->create([
            'user_id' => $this->user->id,
            'total_amount' => 1500.00,
        ]);

        // Apply campaign first time
        $this->campaignService->applyCampaign(
            $this->campaign,
            $this->user,
            $investment,
            1500.00
        );

        // Try to apply again
        $result = $this->campaignService->applyCampaign(
            $this->campaign,
            $this->user,
            $investment,
            1500.00
        );

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('already been applied', $result['message']);

        // Verify only one usage record exists
        $this->assertEquals(1, CampaignUsage::where('campaign_id', $this->campaign->id)->count());
    }

    public function it_stores_campaign_snapshot_on_application()
    {
        $investment = Investment::factory()->create([
            'user_id' => $this->user->id,
            'total_amount' => 1500.00,
        ]);

        $result = $this->campaignService->applyCampaign(
            $this->campaign,
            $this->user,
            $investment,
            1500.00
        );

        $usage = $result['usage'];
        $snapshot = $usage->campaign_snapshot;

        $this->assertNotNull($snapshot);
        $this->assertEquals('TEST100', $snapshot['code']);
        $this->assertEquals('Test Campaign', $snapshot['title']);
    }

    public function it_gets_user_usage_count()
    {
        // Create usage records
        for ($i = 0; $i < 3; $i++) {
            CampaignUsage::create([
                'campaign_id' => $this->campaign->id,
                'user_id' => $this->user->id,
                'applicable_type' => Investment::class,
                'applicable_id' => $i,
                'original_amount' => 1500.00,
                'discount_applied' => 100.00,
                'final_amount' => 1400.00,
                'campaign_code' => 'TEST100',
                'used_at' => now(),
            ]);
        }

        $count = $this->campaignService->getUserUsageCount($this->campaign, $this->user);

        $this->assertEquals(3, $count);
    }

    public function it_gets_campaign_statistics()
    {
        // Create multiple usage records
        for ($i = 0; $i < 3; $i++) {
            CampaignUsage::create([
                'campaign_id' => $this->campaign->id,
                'user_id' => $this->user->id,
                'applicable_type' => Investment::class,
                'applicable_id' => $i,
                'original_amount' => 1500.00,
                'discount_applied' => 100.00,
                'final_amount' => 1400.00,
                'campaign_code' => 'TEST100',
                'used_at' => now(),
            ]);
        }

        $stats = $this->campaignService->getCampaignStats($this->campaign);

        $this->assertEquals(3, $stats['total_usage_count']);
        $this->assertEquals(1, $stats['unique_users_count']);
        $this->assertEquals(300.00, $stats['total_discount_given']);
        $this->assertEquals(100.00, $stats['average_discount']);
    }

    public function it_approves_campaign_successfully()
    {
        $draftCampaign = Campaign::create([
            'title' => 'Draft Campaign',
            'code' => 'DRAFT100',
            'description' => 'Draft campaign',
            'discount_type' => 'fixed_amount',
            'discount_amount' => 100.00,
            'created_by' => $this->user->id,
        ]);

        $approver = User::factory()->create();
        $result = $this->campaignService->approveCampaign($draftCampaign, $approver);

        $this->assertTrue($result);
        $this->assertNotNull($draftCampaign->fresh()->approved_at);
        $this->assertEquals($approver->id, $draftCampaign->fresh()->approved_by);
    }

    public function it_activates_campaign_successfully()
    {
        $this->campaign->update(['is_active' => false]);

        $result = $this->campaignService->activateCampaign($this->campaign);

        $this->assertTrue($result);
        $this->assertTrue($this->campaign->fresh()->is_active);
    }

    public function it_pauses_campaign_successfully()
    {
        $result = $this->campaignService->pauseCampaign($this->campaign);

        $this->assertTrue($result);
        $this->assertFalse($this->campaign->fresh()->is_active);
    }

    public function it_gets_applicable_campaigns_for_user()
    {
        // Create multiple campaigns
        Campaign::create([
            'title' => 'Campaign 2',
            'code' => 'TEST200',
            'description' => 'Second campaign',
            'discount_type' => 'fixed_amount',
            'discount_amount' => 200.00,
            'min_investment' => 2000.00,
            'start_at' => now()->subDay(),
            'end_at' => now()->addDays(7),
            'is_active' => true,
            'approved_by' => $this->user->id,
            'approved_at' => now(),
            'created_by' => $this->user->id,
        ]);

        $campaigns = $this->campaignService->getApplicableCampaigns($this->user, 2500.00);

        // Both campaigns should be applicable for 2500
        $this->assertEquals(2, $campaigns->count());
    }
}
