<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Enums\DisputeStatus;
use App\Enums\DisputeType;
use App\Models\Dispute;
use App\Models\DisputeSnapshot;
use App\Models\DisputeTimeline;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class AdminDisputeManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $investor;

    protected function setUp(): void
    {
        parent::setUp();

        // Create admin role with both guards to handle auth config variations
        $adminRoleSanctum = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'sanctum']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'manage disputes', 'guard_name' => 'sanctum']);
        $adminRoleSanctum->givePermissionTo('manage disputes');

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->investor = User::factory()->create();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_list_disputes()
    {
        Sanctum::actingAs($this->admin, ['*']);

        Dispute::factory()->count(3)->create([
            'user_id' => $this->investor->id,
            'status' => Dispute::STATUS_OPEN,
        ]);

        $response = $this->getJson('/api/v1/admin/dispute-management');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_filter_disputes_by_status()
    {
        Sanctum::actingAs($this->admin, ['*']);

        Dispute::factory()->create([
            'user_id' => $this->investor->id,
            'status' => Dispute::STATUS_OPEN,
        ]);

        Dispute::factory()->create([
            'user_id' => $this->investor->id,
            'status' => Dispute::STATUS_ESCALATED,
        ]);

        $response = $this->getJson('/api/v1/admin/dispute-management?status=escalated');

        $response->assertStatus(200);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_view_single_dispute()
    {
        Sanctum::actingAs($this->admin, ['*']);

        $dispute = Dispute::factory()->create([
            'user_id' => $this->investor->id,
            'status' => Dispute::STATUS_OPEN,
        ]);

        $response = $this->getJson("/api/v1/admin/dispute-management/{$dispute->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'dispute',
                    'permissions',
                    'integrity',
                    'available_transitions',
                ],
            ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function dispute_detail_includes_permission_flags()
    {
        Sanctum::actingAs($this->admin, ['*']);

        $dispute = Dispute::factory()->create([
            'user_id' => $this->investor->id,
            'status' => Dispute::STATUS_OPEN,
        ]);

        $response = $this->getJson("/api/v1/admin/dispute-management/{$dispute->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'permissions' => [
                        'can_transition',
                        'can_escalate',
                        'can_resolve',
                        'can_override_defensibility',
                        'can_refund',
                        'can_close',
                        'available_transitions',
                    ],
                ],
            ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_transition_dispute_status()
    {
        Sanctum::actingAs($this->admin, ['*']);

        $dispute = Dispute::factory()->create([
            'user_id' => $this->investor->id,
            'status' => Dispute::STATUS_OPEN,
        ]);

        $response = $this->postJson("/api/v1/admin/dispute-management/{$dispute->id}/transition", [
            'target_status' => 'under_review',
            'comment' => 'Starting investigation',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Dispute status updated.',
            ]);

        $this->assertEquals(Dispute::STATUS_UNDER_REVIEW, $dispute->fresh()->status);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function invalid_transition_returns_error()
    {
        Sanctum::actingAs($this->admin, ['*']);

        $dispute = Dispute::factory()->create([
            'user_id' => $this->investor->id,
            'status' => Dispute::STATUS_CLOSED, // Terminal state
        ]);

        $response = $this->postJson("/api/v1/admin/dispute-management/{$dispute->id}/transition", [
            'target_status' => 'under_review',
        ]);

        // 403 Forbidden is returned for invalid state transitions
        // (e.g., trying to transition from a terminal state)
        $response->assertStatus(403);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_escalate_dispute()
    {
        Sanctum::actingAs($this->admin, ['*']);

        // Escalation is only allowed from UNDER_REVIEW status per the state machine
        $dispute = Dispute::factory()->create([
            'user_id' => $this->investor->id,
            'status' => Dispute::STATUS_UNDER_REVIEW,
        ]);

        $response = $this->postJson("/api/v1/admin/dispute-management/{$dispute->id}/escalate", [
            'reason' => 'Requires senior review due to high value',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Dispute escalated.',
            ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_resolve_dispute_approved()
    {
        Sanctum::actingAs($this->admin, ['*']);

        $dispute = Dispute::factory()->create([
            'user_id' => $this->investor->id,
            'status' => Dispute::STATUS_UNDER_REVIEW,
        ]);

        // Create snapshot for integrity check
        $snapshot = DisputeSnapshot::create([
            'dispute_id' => $dispute->id,
            'disputable_snapshot' => [],
            'wallet_snapshot' => [],
            'related_transactions_snapshot' => [],
            'system_state_snapshot' => [],
            'capture_trigger' => DisputeSnapshot::TRIGGER_DISPUTE_FILED,
        ]);

        $response = $this->postJson("/api/v1/admin/dispute-management/{$dispute->id}/resolve", [
            'outcome' => 'approved',
            'resolution' => 'Verified payment issue, refund approved.',
            'settlement_action' => 'refund',
            'settlement_amount' => 100.00,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Dispute resolved.',
            ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_resolve_dispute_rejected()
    {
        Sanctum::actingAs($this->admin, ['*']);

        $dispute = Dispute::factory()->create([
            'user_id' => $this->investor->id,
            'status' => Dispute::STATUS_UNDER_REVIEW,
        ]);

        // Create snapshot
        DisputeSnapshot::create([
            'dispute_id' => $dispute->id,
            'disputable_snapshot' => [],
            'wallet_snapshot' => [],
            'related_transactions_snapshot' => [],
            'system_state_snapshot' => [],
            'capture_trigger' => DisputeSnapshot::TRIGGER_DISPUTE_FILED,
        ]);

        $response = $this->postJson("/api/v1/admin/dispute-management/{$dispute->id}/resolve", [
            'outcome' => 'rejected',
            'resolution' => 'Investigation found no error in allocation.',
        ]);

        $response->assertStatus(200);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_override_defensibility()
    {
        Sanctum::actingAs($this->admin, ['*']);

        $dispute = Dispute::factory()->create([
            'user_id' => $this->investor->id,
            'status' => Dispute::STATUS_UNDER_REVIEW,
        ]);

        $response = $this->postJson("/api/v1/admin/dispute-management/{$dispute->id}/override-defensibility", [
            'override_type' => 'integrity_confirmed',
            'reason' => 'Manual verification confirms data integrity despite system warning.',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Defensibility override recorded.',
            ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function override_defensibility_requires_minimum_reason_length()
    {
        Sanctum::actingAs($this->admin, ['*']);

        $dispute = Dispute::factory()->create([
            'user_id' => $this->investor->id,
            'status' => Dispute::STATUS_UNDER_REVIEW,
        ]);

        $response = $this->postJson("/api/v1/admin/dispute-management/{$dispute->id}/override-defensibility", [
            'override_type' => 'integrity_confirmed',
            'reason' => 'Too short', // Less than 20 chars
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['reason']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_close_resolved_dispute()
    {
        Sanctum::actingAs($this->admin, ['*']);

        $dispute = Dispute::factory()->create([
            'user_id' => $this->investor->id,
            'status' => Dispute::STATUS_RESOLVED_APPROVED,
        ]);

        $response = $this->postJson("/api/v1/admin/dispute-management/{$dispute->id}/close", [
            'notes' => 'All settlement actions completed.',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Dispute closed.',
            ]);

        $this->assertEquals(Dispute::STATUS_CLOSED, $dispute->fresh()->status);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function cannot_close_unresolved_dispute()
    {
        Sanctum::actingAs($this->admin, ['*']);

        $dispute = Dispute::factory()->create([
            'user_id' => $this->investor->id,
            'status' => Dispute::STATUS_OPEN,
        ]);

        $response = $this->postJson("/api/v1/admin/dispute-management/{$dispute->id}/close");

        $response->assertStatus(403);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function transition_creates_timeline_entry()
    {
        Sanctum::actingAs($this->admin, ['*']);

        $dispute = Dispute::factory()->create([
            'user_id' => $this->investor->id,
            'status' => Dispute::STATUS_OPEN,
        ]);

        $initialCount = DisputeTimeline::where('dispute_id', $dispute->id)->count();

        $this->postJson("/api/v1/admin/dispute-management/{$dispute->id}/transition", [
            'target_status' => 'under_review',
            'comment' => 'Starting investigation',
        ]);

        $newCount = DisputeTimeline::where('dispute_id', $dispute->id)->count();
        $this->assertGreaterThan($initialCount, $newCount);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function unauthenticated_user_cannot_access_admin_disputes()
    {
        $dispute = Dispute::factory()->create([
            'user_id' => $this->investor->id,
        ]);

        $response = $this->getJson('/api/v1/admin/dispute-management');

        $response->assertStatus(401);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function non_admin_cannot_access_admin_disputes()
    {
        Sanctum::actingAs($this->investor, ['*']);

        $response = $this->getJson('/api/v1/admin/dispute-management');

        $response->assertStatus(403);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function dispute_detail_shows_recommended_settlement()
    {
        Sanctum::actingAs($this->admin, ['*']);

        $dispute = Dispute::factory()->create([
            'user_id' => $this->investor->id,
            'status' => Dispute::STATUS_UNDER_REVIEW,
            'type' => 'payment',
        ]);

        $response = $this->getJson("/api/v1/admin/dispute-management/{$dispute->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'recommended_settlement',
                ],
            ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function dispute_detail_shows_integrity_status()
    {
        Sanctum::actingAs($this->admin, ['*']);

        $dispute = Dispute::factory()->create([
            'user_id' => $this->investor->id,
            'status' => Dispute::STATUS_UNDER_REVIEW,
        ]);

        // Create valid snapshot
        DisputeSnapshot::create([
            'dispute_id' => $dispute->id,
            'disputable_snapshot' => ['test' => true],
            'wallet_snapshot' => [],
            'related_transactions_snapshot' => [],
            'system_state_snapshot' => [],
            'capture_trigger' => DisputeSnapshot::TRIGGER_DISPUTE_FILED,
        ]);

        $response = $this->getJson("/api/v1/admin/dispute-management/{$dispute->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'integrity' => ['valid', 'stored_hash', 'computed_hash'],
                ],
            ]);
    }
}
