<?php
// V-TEST-SUITE-003 (ReferralCampaignController Feature Tests)

namespace Tests\Feature;

use Tests\FeatureTestCase;
use App\Models\User;
use App\Models\ReferralCampaign;
class ReferralCampaignControllerTest extends FeatureTestCase
{
    protected User $admin;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this->seed(\Database\Seeders\SettingsSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->user = User::factory()->create();
        $this->user->assignRole('user');
    }

    // ==================== INDEX TESTS ====================
    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_list_referral_campaigns()
    {
        ReferralCampaign::factory()->count(3)->create();

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/referral-campaigns');

        $response->assertStatus(200)
            ->assertJsonCount(3);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function campaigns_are_returned_in_latest_order()
    {
        $old = ReferralCampaign::factory()->create(['name' => 'Old Campaign', 'created_at' => now()->subDays(10)]);
        $new = ReferralCampaign::factory()->create(['name' => 'New Campaign', 'created_at' => now()]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/referral-campaigns');

        $response->assertStatus(200);
        $this->assertEquals('New Campaign', $response->json()[0]['name']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function regular_user_cannot_list_campaigns()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/admin/referral-campaigns');

        $response->assertStatus(403);
    }

    // ==================== STORE TESTS ====================
    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_create_referral_campaign()
    {
        $campaignData = [
            'name' => 'Diwali Bonus Campaign',
            'start_date' => now()->format('Y-m-d'),
            'end_date' => now()->addMonth()->format('Y-m-d'),
            'multiplier' => 2.0,
            'bonus_amount' => 500,
            'is_active' => true
        ];

        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/referral-campaigns', $campaignData);

        $response->assertStatus(201)
            ->assertJsonFragment(['name' => 'Diwali Bonus Campaign']);

        $this->assertDatabaseHas('referral_campaigns', [
            'name' => 'Diwali Bonus Campaign',
            'multiplier' => 2.0
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function store_validates_required_fields()
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/referral-campaigns', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'start_date', 'end_date', 'multiplier', 'bonus_amount']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function store_validates_end_date_after_start_date()
    {
        $campaignData = [
            'name' => 'Invalid Campaign',
            'start_date' => now()->addMonth()->format('Y-m-d'),
            'end_date' => now()->format('Y-m-d'), // Before start_date
            'multiplier' => 1.5,
            'bonus_amount' => 100
        ];

        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/referral-campaigns', $campaignData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['end_date']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function store_enforces_maximum_multiplier_cap()
    {
        $campaignData = [
            'name' => 'High Multiplier Campaign',
            'start_date' => now()->format('Y-m-d'),
            'end_date' => now()->addMonth()->format('Y-m-d'),
            'multiplier' => 10.0, // Exceeds default max of 5.0
            'bonus_amount' => 100
        ];

        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/referral-campaigns', $campaignData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['multiplier']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function store_enforces_maximum_bonus_amount_cap()
    {
        $campaignData = [
            'name' => 'High Bonus Campaign',
            'start_date' => now()->format('Y-m-d'),
            'end_date' => now()->addMonth()->format('Y-m-d'),
            'multiplier' => 1.5,
            'bonus_amount' => 50000 // Exceeds default max of 10000
        ];

        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/referral-campaigns', $campaignData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['bonus_amount']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function store_allows_multiplier_at_maximum_cap()
    {
        $campaignData = [
            'name' => 'Max Multiplier Campaign',
            'start_date' => now()->format('Y-m-d'),
            'end_date' => now()->addMonth()->format('Y-m-d'),
            'multiplier' => 5.0, // Exactly at max
            'bonus_amount' => 100
        ];

        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/referral-campaigns', $campaignData);

        $response->assertStatus(201);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function store_allows_bonus_at_maximum_cap()
    {
        $campaignData = [
            'name' => 'Max Bonus Campaign',
            'start_date' => now()->format('Y-m-d'),
            'end_date' => now()->addMonth()->format('Y-m-d'),
            'multiplier' => 1.0,
            'bonus_amount' => 10000 // Exactly at max
        ];

        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/referral-campaigns', $campaignData);

        $response->assertStatus(201);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function store_validates_minimum_multiplier()
    {
        $campaignData = [
            'name' => 'Low Multiplier Campaign',
            'start_date' => now()->format('Y-m-d'),
            'end_date' => now()->addMonth()->format('Y-m-d'),
            'multiplier' => 0.5, // Below min of 1
            'bonus_amount' => 100
        ];

        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/referral-campaigns', $campaignData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['multiplier']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function store_validates_non_negative_bonus()
    {
        $campaignData = [
            'name' => 'Negative Bonus Campaign',
            'start_date' => now()->format('Y-m-d'),
            'end_date' => now()->addMonth()->format('Y-m-d'),
            'multiplier' => 1.5,
            'bonus_amount' => -100 // Negative
        ];

        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/referral-campaigns', $campaignData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['bonus_amount']);
    }

    // ==================== UPDATE TESTS ====================
    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_update_referral_campaign()
    {
        $campaign = ReferralCampaign::factory()->create([
            'name' => 'Original Name',
            'multiplier' => 1.0
        ]);

        $response = $this->actingAs($this->admin)
            ->putJson("/api/v1/admin/referral-campaigns/{$campaign->id}", [
                'name' => 'Updated Name',
                'multiplier' => 2.0
            ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'Updated Name']);

        $this->assertDatabaseHas('referral_campaigns', [
            'id' => $campaign->id,
            'name' => 'Updated Name',
            'multiplier' => 2.0
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function update_allows_partial_updates()
    {
        $campaign = ReferralCampaign::factory()->create([
            'name' => 'Original Name',
            'multiplier' => 1.5,
            'bonus_amount' => 200
        ]);

        $response = $this->actingAs($this->admin)
            ->putJson("/api/v1/admin/referral-campaigns/{$campaign->id}", [
                'name' => 'Only Name Updated'
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('referral_campaigns', [
            'id' => $campaign->id,
            'name' => 'Only Name Updated',
            'multiplier' => 1.5, // Unchanged
            'bonus_amount' => 200 // Unchanged
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function update_enforces_multiplier_cap()
    {
        $campaign = ReferralCampaign::factory()->create(['multiplier' => 1.0]);

        $response = $this->actingAs($this->admin)
            ->putJson("/api/v1/admin/referral-campaigns/{$campaign->id}", [
                'multiplier' => 15.0 // Exceeds cap
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['multiplier']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function update_enforces_bonus_cap()
    {
        $campaign = ReferralCampaign::factory()->create(['bonus_amount' => 100]);

        $response = $this->actingAs($this->admin)
            ->putJson("/api/v1/admin/referral-campaigns/{$campaign->id}", [
                'bonus_amount' => 99999 // Exceeds cap
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['bonus_amount']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function update_validates_date_consistency()
    {
        $campaign = ReferralCampaign::factory()->create([
            'start_date' => now(),
            'end_date' => now()->addMonth()
        ]);

        $response = $this->actingAs($this->admin)
            ->putJson("/api/v1/admin/referral-campaigns/{$campaign->id}", [
                'start_date' => now()->addMonths(2)->format('Y-m-d'),
                'end_date' => now()->addMonth()->format('Y-m-d') // Before new start_date
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['end_date']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_toggle_campaign_active_status()
    {
        $campaign = ReferralCampaign::factory()->create(['is_active' => true]);

        $response = $this->actingAs($this->admin)
            ->putJson("/api/v1/admin/referral-campaigns/{$campaign->id}", [
                'is_active' => false
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('referral_campaigns', [
            'id' => $campaign->id,
            'is_active' => false
        ]);
    }

    // ==================== DESTROY TESTS ====================
    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_delete_referral_campaign()
    {
        $campaign = ReferralCampaign::factory()->create();

        $response = $this->actingAs($this->admin)
            ->deleteJson("/api/v1/admin/referral-campaigns/{$campaign->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Campaign deleted']);

        $this->assertDatabaseMissing('referral_campaigns', [
            'id' => $campaign->id
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function delete_returns_404_for_nonexistent_campaign()
    {
        $response = $this->actingAs($this->admin)
            ->deleteJson('/api/v1/admin/referral-campaigns/99999');

        $response->assertStatus(404);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function regular_user_cannot_delete_campaign()
    {
        $campaign = ReferralCampaign::factory()->create();

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/admin/referral-campaigns/{$campaign->id}");

        $response->assertStatus(403);
    }

    // ==================== FRAUD PREVENTION TESTS ====================
    #[\PHPUnit\Framework\Attributes\Test]
    public function cannot_create_campaign_with_unreasonable_multiplier()
    {
        // Even at max cap, the values should be reasonable
        $campaignData = [
            'name' => 'Suspicious Campaign',
            'start_date' => now()->format('Y-m-d'),
            'end_date' => now()->addMonth()->format('Y-m-d'),
            'multiplier' => 5.0,
            'bonus_amount' => 10000
        ];

        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/referral-campaigns', $campaignData);

        // Should succeed at max values
        $response->assertStatus(201);

        // But one more should fail
        $campaignData['multiplier'] = 5.01;
        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/referral-campaigns', $campaignData);

        $response->assertStatus(422);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function campaign_multiplier_precision_is_respected()
    {
        $campaignData = [
            'name' => 'Precise Campaign',
            'start_date' => now()->format('Y-m-d'),
            'end_date' => now()->addMonth()->format('Y-m-d'),
            'multiplier' => 1.25,
            'bonus_amount' => 100
        ];

        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/referral-campaigns', $campaignData);

        $response->assertStatus(201);
        $this->assertDatabaseHas('referral_campaigns', [
            'multiplier' => 1.25
        ]);
    }
}
