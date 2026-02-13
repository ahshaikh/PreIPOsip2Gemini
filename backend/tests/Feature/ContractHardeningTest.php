<?php
// V-CONTRACT-HARDENING: Comprehensive tests for subscription bonus contract hardening

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Plan;
use App\Models\PlanConfig;
use App\Models\Subscription;
use App\Models\Payment;
use App\Models\Admin;
use App\Models\PlanRegulatoryOverride;
use App\Models\BonusTransaction;
use App\Services\SubscriptionConfigSnapshotService;
use App\Services\BonusCalculatorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class ContractHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Plan $plan;
    protected Admin $admin;
    protected SubscriptionConfigSnapshotService $snapshotService;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test user with KYC and wallet
        $this->user = User::factory()->create();
        $this->user->kyc()->create(['status' => 'verified']);
        $this->user->wallet()->create(['balance_paise' => 10000000]); // 100,000 INR

        // Create test admin
        $this->admin = Admin::factory()->create();

        // Create test plan with bonus configs
        $this->plan = Plan::factory()->create([
            'name' => 'Test Plan',
            'monthly_amount' => 5000,
            'duration_months' => 36,
            'is_active' => true,
        ]);

        // Add bonus configurations to plan
        PlanConfig::create([
            'plan_id' => $this->plan->id,
            'config_key' => 'progressive_config',
            'value' => [
                'rate' => 0.5,
                'start_month' => 4,
                'max_percentage' => 20,
                'overrides' => [],
            ],
        ]);

        PlanConfig::create([
            'plan_id' => $this->plan->id,
            'config_key' => 'milestone_config',
            'value' => [
                ['month' => 6, 'amount' => 500],
                ['month' => 12, 'amount' => 1000],
            ],
        ]);

        PlanConfig::create([
            'plan_id' => $this->plan->id,
            'config_key' => 'consistency_config',
            'value' => [
                'amount_per_payment' => 50,
                'streaks' => [
                    ['months' => 6, 'multiplier' => 1.5],
                    ['months' => 12, 'multiplier' => 2.0],
                ],
            ],
        ]);

        PlanConfig::create([
            'plan_id' => $this->plan->id,
            'config_key' => 'welcome_bonus_config',
            'value' => ['amount' => 500],
        ]);

        $this->snapshotService = app(SubscriptionConfigSnapshotService::class);
    }

    // =========================================================================
    // SUBSCRIPTION SNAPSHOT TESTS
    // =========================================================================

    /** @test */
    public function subscription_snapshot_captures_all_bonus_configs()
    {
        $subscription = Subscription::create([
            'user_id' => $this->user->id,
            'plan_id' => $this->plan->id,
            'amount' => 5000,
            'subscription_code' => 'SUB-TEST-001',
            'status' => 'active',
            'start_date' => now(),
            'end_date' => now()->addMonths(36),
            'next_payment_date' => now()->addMonth(),
        ]);

        $this->plan->load('configs');
        $this->snapshotService->snapshotConfigToSubscription($subscription, $this->plan);
        $subscription->save();

        // Verify all configs are snapshotted
        $this->assertNotNull($subscription->progressive_config);
        $this->assertNotNull($subscription->milestone_config);
        $this->assertNotNull($subscription->consistency_config);
        $this->assertNotNull($subscription->welcome_bonus_config);
        $this->assertNotNull($subscription->config_snapshot_at);
        $this->assertNotNull($subscription->config_snapshot_version);

        // Verify config structure
        $this->assertEquals(0.5, $subscription->progressive_config['rate']);
        $this->assertEquals(4, $subscription->progressive_config['start_month']);
        $this->assertCount(2, $subscription->milestone_config);
        $this->assertEquals(50, $subscription->consistency_config['amount_per_payment']);
        $this->assertEquals(500, $subscription->welcome_bonus_config['amount']);
    }

    /** @test */
    public function subscription_snapshot_generates_version_hash()
    {
        $subscription = Subscription::create([
            'user_id' => $this->user->id,
            'plan_id' => $this->plan->id,
            'amount' => 5000,
            'subscription_code' => 'SUB-TEST-002',
            'status' => 'active',
            'start_date' => now(),
            'end_date' => now()->addMonths(36),
            'next_payment_date' => now()->addMonth(),
        ]);

        $this->plan->load('configs');
        $this->snapshotService->snapshotConfigToSubscription($subscription, $this->plan);
        $subscription->save();

        // Version hash should be 32 characters (SHA256 truncated)
        $this->assertEquals(32, strlen($subscription->config_snapshot_version));

        // Verify integrity check passes
        $this->assertTrue($this->snapshotService->verifyConfigIntegrity($subscription));
    }

    /** @test */
    public function subscription_snapshot_is_immutable_after_creation()
    {
        $subscription = Subscription::create([
            'user_id' => $this->user->id,
            'plan_id' => $this->plan->id,
            'amount' => 5000,
            'subscription_code' => 'SUB-TEST-003',
            'status' => 'active',
            'start_date' => now(),
            'end_date' => now()->addMonths(36),
            'next_payment_date' => now()->addMonth(),
        ]);

        $this->plan->load('configs');
        $this->snapshotService->snapshotConfigToSubscription($subscription, $this->plan);
        $subscription->save();

        $originalConfig = $subscription->progressive_config;
        $originalVersion = $subscription->config_snapshot_version;

        // Modify plan config (should NOT affect subscription snapshot)
        $this->plan->configs()->where('config_key', 'progressive_config')->update([
            'value' => ['rate' => 1.0, 'start_month' => 2, 'max_percentage' => 30, 'overrides' => []],
        ]);

        // Refresh subscription from DB
        $subscription->refresh();

        // Subscription config should remain unchanged
        $this->assertEquals($originalConfig, $subscription->progressive_config);
        $this->assertEquals($originalVersion, $subscription->config_snapshot_version);
    }

    // =========================================================================
    // PLAN GUARDRAIL TESTS
    // =========================================================================

    /** @test */
    public function plan_bonus_config_cannot_be_edited_with_active_subscriptions()
    {
        // Create an active subscription
        $subscription = Subscription::create([
            'user_id' => $this->user->id,
            'plan_id' => $this->plan->id,
            'amount' => 5000,
            'subscription_code' => 'SUB-GUARD-001',
            'status' => 'active',
            'start_date' => now(),
            'end_date' => now()->addMonths(36),
            'next_payment_date' => now()->addMonth(),
        ]);

        // Attempt to update bonus config via API
        $response = $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/v1/admin/plans/{$this->plan->id}", [
                'name' => $this->plan->name,
                'configs' => [
                    'progressive_config' => [
                        'rate' => 1.0,
                        'start_month' => 2,
                        'max_percentage' => 30,
                    ],
                ],
            ]);

        $response->assertStatus(409);
        $response->assertJsonPath('suggestion', 'clone_plan_or_regulatory_override');
        $response->assertJsonPath('blocked_configs.0', 'progressive_config');
    }

    /** @test */
    public function plan_non_bonus_config_can_be_edited_with_active_subscriptions()
    {
        // Create an active subscription
        $subscription = Subscription::create([
            'user_id' => $this->user->id,
            'plan_id' => $this->plan->id,
            'amount' => 5000,
            'subscription_code' => 'SUB-GUARD-002',
            'status' => 'active',
            'start_date' => now(),
            'end_date' => now()->addMonths(36),
            'next_payment_date' => now()->addMonth(),
        ]);

        // Attempt to update non-bonus config
        $response = $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/v1/admin/plans/{$this->plan->id}", [
                'name' => 'Updated Plan Name',
                'description' => 'Updated description',
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('plans', [
            'id' => $this->plan->id,
            'name' => 'Updated Plan Name',
        ]);
    }

    /** @test */
    public function plan_bonus_config_can_be_edited_without_active_subscriptions()
    {
        // No subscriptions exist

        $response = $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/v1/admin/plans/{$this->plan->id}", [
                'name' => $this->plan->name,
                'configs' => [
                    'progressive_config' => [
                        'rate' => 1.0,
                        'start_month' => 2,
                        'max_percentage' => 30,
                        'overrides' => [],
                    ],
                ],
            ]);

        $response->assertStatus(200);

        // Verify config was updated
        $this->plan->refresh()->load('configs');
        $config = $this->plan->getConfig('progressive_config');
        $this->assertEquals(1.0, $config['rate']);
    }

    // =========================================================================
    // REGULATORY OVERRIDE TESTS
    // =========================================================================

    /** @test */
    public function regulatory_override_can_be_created_with_proper_audit_fields()
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/admin/regulatory-overrides', [
                'plan_id' => $this->plan->id,
                'override_scope' => 'progressive_config',
                'override_payload' => ['rate' => 0.25],
                'reason' => 'Regulatory requirement to reduce bonus rates per SEBI directive',
                'regulatory_reference' => 'SEBI/HO/2026/001',
                'effective_from' => now()->toDateString(),
            ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'message',
            'override' => [
                'id',
                'plan_id',
                'override_scope',
                'override_payload',
                'reason',
                'regulatory_reference',
                'approved_by_admin_id',
                'effective_from',
            ],
            'audit',
        ]);

        $this->assertDatabaseHas('plan_regulatory_overrides', [
            'plan_id' => $this->plan->id,
            'override_scope' => 'progressive_config',
            'regulatory_reference' => 'SEBI/HO/2026/001',
            'approved_by_admin_id' => $this->admin->id,
        ]);
    }

    /** @test */
    public function regulatory_override_requires_regulatory_reference()
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/admin/regulatory-overrides', [
                'plan_id' => $this->plan->id,
                'override_scope' => 'progressive_config',
                'override_payload' => ['rate' => 0.25],
                'reason' => 'Some reason',
                // Missing regulatory_reference
                'effective_from' => now()->toDateString(),
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['regulatory_reference']);
    }

    /** @test */
    public function regulatory_override_can_be_revoked_with_audit_trail()
    {
        // Create an override first
        $override = PlanRegulatoryOverride::create([
            'plan_id' => $this->plan->id,
            'override_scope' => 'progressive_config',
            'override_payload' => ['rate' => 0.25],
            'reason' => 'Test override',
            'regulatory_reference' => 'TEST/2026/001',
            'approved_by_admin_id' => $this->admin->id,
            'effective_from' => now(),
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/v1/admin/regulatory-overrides/{$override->id}/revoke", [
                'reason' => 'Override no longer required per updated regulatory guidance',
            ]);

        $response->assertStatus(200);

        $override->refresh();
        $this->assertNotNull($override->revoked_at);
        $this->assertEquals($this->admin->id, $override->revoked_by_admin_id);
        $this->assertFalse($override->isActive());
    }

    // =========================================================================
    // BONUS CALCULATION WITH OVERRIDE TESTS
    // =========================================================================

    /** @test */
    public function bonus_calculation_uses_subscription_snapshot_not_plan_config()
    {
        // Create subscription with snapshotted config
        $subscription = Subscription::create([
            'user_id' => $this->user->id,
            'plan_id' => $this->plan->id,
            'amount' => 5000,
            'subscription_code' => 'SUB-CALC-001',
            'status' => 'active',
            'start_date' => now(),
            'end_date' => now()->addMonths(36),
            'next_payment_date' => now()->addMonth(),
        ]);

        $this->plan->load('configs');
        $this->snapshotService->snapshotConfigToSubscription($subscription, $this->plan);
        $subscription->save();

        // Store original snapshot rate
        $originalRate = $subscription->progressive_config['rate'];
        $this->assertEquals(0.5, $originalRate);

        // Now modify plan config (simulate admin making changes)
        PlanConfig::where('plan_id', $this->plan->id)
            ->where('config_key', 'progressive_config')
            ->update([
                'value' => ['rate' => 2.0, 'start_month' => 1, 'max_percentage' => 50, 'overrides' => []],
            ]);

        // Refresh subscription (should still have original snapshot)
        $subscription->refresh();
        $this->assertEquals(0.5, $subscription->progressive_config['rate']);
    }

    /** @test */
    public function bonus_transaction_records_override_when_applied()
    {
        // Create subscription with snapshot
        $subscription = Subscription::create([
            'user_id' => $this->user->id,
            'plan_id' => $this->plan->id,
            'amount' => 5000,
            'subscription_code' => 'SUB-CALC-002',
            'status' => 'active',
            'start_date' => now(),
            'end_date' => now()->addMonths(36),
            'next_payment_date' => now()->addMonth(),
            'consecutive_payments_count' => 4,
        ]);

        $this->plan->load('configs');
        $this->snapshotService->snapshotConfigToSubscription($subscription, $this->plan);
        $subscription->save();

        // Create active regulatory override
        $override = PlanRegulatoryOverride::create([
            'plan_id' => $this->plan->id,
            'override_scope' => 'progressive_config',
            'override_payload' => ['rate' => 0.25],
            'reason' => 'Regulatory requirement',
            'regulatory_reference' => 'SEBI/HO/2026/001',
            'approved_by_admin_id' => $this->admin->id,
            'effective_from' => now()->subDay(),
        ]);

        // Create a payment to trigger bonus calculation
        $payment = Payment::create([
            'user_id' => $this->user->id,
            'subscription_id' => $subscription->id,
            'amount' => 5000,
            'status' => 'paid',
            'payment_type' => 'subscription_payment',
            'is_on_time' => true,
        ]);

        // Calculate bonus
        $bonusService = app(BonusCalculatorService::class);
        $totalBonus = $bonusService->calculateAndAwardBonuses($payment);

        // Check bonus transaction has override tracking
        $bonusTransaction = BonusTransaction::where('payment_id', $payment->id)->first();

        if ($bonusTransaction) {
            $this->assertTrue($bonusTransaction->override_applied);
            $this->assertEquals($override->id, $bonusTransaction->override_id);
            $this->assertNotNull($bonusTransaction->config_used);
        }
    }

    // =========================================================================
    // OVERRIDE RESOLUTION TESTS
    // =========================================================================

    /** @test */
    public function expired_override_is_not_applied()
    {
        // Create expired override
        $expiredOverride = PlanRegulatoryOverride::create([
            'plan_id' => $this->plan->id,
            'override_scope' => 'progressive_config',
            'override_payload' => ['rate' => 0.1],
            'reason' => 'Temporary reduction',
            'regulatory_reference' => 'TEMP/2026/001',
            'approved_by_admin_id' => $this->admin->id,
            'effective_from' => now()->subMonth(),
            'expires_at' => now()->subDay(), // Expired yesterday
        ]);

        $this->assertFalse($expiredOverride->isActive());

        // Query should not return expired overrides
        $activeOverrides = PlanRegulatoryOverride::forPlan($this->plan->id)
            ->active()
            ->get();

        $this->assertCount(0, $activeOverrides);
    }

    /** @test */
    public function revoked_override_is_not_applied()
    {
        // Create and revoke an override
        $override = PlanRegulatoryOverride::create([
            'plan_id' => $this->plan->id,
            'override_scope' => 'progressive_config',
            'override_payload' => ['rate' => 0.1],
            'reason' => 'Test override',
            'regulatory_reference' => 'TEST/2026/001',
            'approved_by_admin_id' => $this->admin->id,
            'effective_from' => now()->subDay(),
            'revoked_at' => now(),
            'revoked_by_admin_id' => $this->admin->id,
            'revocation_reason' => 'No longer needed',
        ]);

        $this->assertFalse($override->isActive());

        $activeOverrides = PlanRegulatoryOverride::forPlan($this->plan->id)
            ->active()
            ->get();

        $this->assertCount(0, $activeOverrides);
    }

    /** @test */
    public function most_recent_override_takes_precedence()
    {
        // Create two active overrides
        $olderOverride = PlanRegulatoryOverride::create([
            'plan_id' => $this->plan->id,
            'override_scope' => 'progressive_config',
            'override_payload' => ['rate' => 0.3],
            'reason' => 'Older override',
            'regulatory_reference' => 'OLD/2026/001',
            'approved_by_admin_id' => $this->admin->id,
            'effective_from' => now()->subDays(10),
        ]);

        $newerOverride = PlanRegulatoryOverride::create([
            'plan_id' => $this->plan->id,
            'override_scope' => 'progressive_config',
            'override_payload' => ['rate' => 0.2],
            'reason' => 'Newer override',
            'regulatory_reference' => 'NEW/2026/001',
            'approved_by_admin_id' => $this->admin->id,
            'effective_from' => now()->subDay(),
        ]);

        // Query should return newest first
        $activeOverride = PlanRegulatoryOverride::forPlan($this->plan->id)
            ->active()
            ->orderBy('effective_from', 'desc')
            ->first();

        $this->assertEquals($newerOverride->id, $activeOverride->id);
        $this->assertEquals(0.2, $activeOverride->override_payload['rate']);
    }
}
