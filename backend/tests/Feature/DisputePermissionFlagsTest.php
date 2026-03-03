<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Enums\DisputeStatus;
use App\Models\Dispute;
use App\Models\User;
use App\Services\DisputeStateMachine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;

/**
 * DisputePermissionFlagsTest - Verifies backend-computed permission flags
 *
 * Permission flags are computed server-side based on:
 * - Current dispute status
 * - State machine transition rules
 * - Admin role/capabilities
 *
 * Frontend must NOT derive permissions from status - it must use the flags
 * returned by the API.
 */
class DisputePermissionFlagsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $investor;
    private DisputeStateMachine $stateMachine;

    protected function setUp(): void
    {
        parent::setUp();

        // Create admin role with both guards to handle auth config variations
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'sanctum']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->investor = User::factory()->create();
        $this->stateMachine = app(DisputeStateMachine::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function open_dispute_can_transition()
    {
        Sanctum::actingAs($this->admin, ['*']);

        $dispute = Dispute::factory()->create([
            'user_id' => $this->investor->id,
            'status' => Dispute::STATUS_OPEN,
        ]);

        $response = $this->getJson("/api/v1/admin/dispute-management/{$dispute->id}");

        $response->assertStatus(200);
        $permissions = $response->json('data.permissions');

        $this->assertTrue($permissions['can_transition']);
        $this->assertFalse($permissions['can_resolve']);
        $this->assertFalse($permissions['can_close']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function under_review_dispute_can_escalate()
    {
        Sanctum::actingAs($this->admin, ['*']);

        // Per state machine: OPEN -> UNDER_REVIEW -> ESCALATED
        // Escalation is only available from UNDER_REVIEW or AWAITING_INVESTOR
        $dispute = Dispute::factory()->create([
            'user_id' => $this->investor->id,
            'status' => Dispute::STATUS_UNDER_REVIEW,
        ]);

        $response = $this->getJson("/api/v1/admin/dispute-management/{$dispute->id}");

        $permissions = $response->json('data.permissions');

        $this->assertTrue($permissions['can_escalate']);
        $this->assertContains('escalated', $permissions['available_transitions']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function under_review_dispute_can_resolve()
    {
        Sanctum::actingAs($this->admin, ['*']);

        $dispute = Dispute::factory()->create([
            'user_id' => $this->investor->id,
            'status' => Dispute::STATUS_UNDER_REVIEW,
        ]);

        $response = $this->getJson("/api/v1/admin/dispute-management/{$dispute->id}");

        $permissions = $response->json('data.permissions');

        $this->assertTrue($permissions['can_resolve']);
        $this->assertTrue($permissions['can_transition']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function escalated_dispute_can_resolve()
    {
        Sanctum::actingAs($this->admin, ['*']);

        $dispute = Dispute::factory()->create([
            'user_id' => $this->investor->id,
            'status' => Dispute::STATUS_ESCALATED,
        ]);

        $response = $this->getJson("/api/v1/admin/dispute-management/{$dispute->id}");

        $permissions = $response->json('data.permissions');

        $this->assertTrue($permissions['can_resolve']);
        $this->assertFalse($permissions['can_escalate']); // Already escalated
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function escalated_dispute_cannot_downgrade()
    {
        Sanctum::actingAs($this->admin, ['*']);

        $dispute = Dispute::factory()->create([
            'user_id' => $this->investor->id,
            'status' => Dispute::STATUS_ESCALATED,
        ]);

        $response = $this->getJson("/api/v1/admin/dispute-management/{$dispute->id}");

        $transitions = $response->json('data.permissions.available_transitions');

        // Should not include downgrade states
        $this->assertNotContains('open', $transitions);
        $this->assertNotContains('under_review', $transitions);
        $this->assertNotContains('awaiting_investor', $transitions);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function resolved_approved_dispute_can_close_and_refund()
    {
        Sanctum::actingAs($this->admin, ['*']);

        $dispute = Dispute::factory()->create([
            'user_id' => $this->investor->id,
            'status' => Dispute::STATUS_RESOLVED_APPROVED,
            'settlement_action' => null, // No settlement yet
        ]);

        $response = $this->getJson("/api/v1/admin/dispute-management/{$dispute->id}");

        $permissions = $response->json('data.permissions');

        $this->assertTrue($permissions['can_close']);
        $this->assertTrue($permissions['can_refund']);
        $this->assertFalse($permissions['can_resolve']); // Already resolved
        $this->assertFalse($permissions['can_escalate']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function resolved_approved_with_settlement_cannot_refund_again()
    {
        Sanctum::actingAs($this->admin, ['*']);

        $dispute = Dispute::factory()->create([
            'user_id' => $this->investor->id,
            'status' => Dispute::STATUS_RESOLVED_APPROVED,
            'settlement_action' => Dispute::SETTLEMENT_REFUND, // Already settled
        ]);

        $response = $this->getJson("/api/v1/admin/dispute-management/{$dispute->id}");

        $permissions = $response->json('data.permissions');

        $this->assertFalse($permissions['can_refund']); // Already refunded
        $this->assertTrue($permissions['can_close']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function resolved_rejected_dispute_can_close()
    {
        Sanctum::actingAs($this->admin, ['*']);

        $dispute = Dispute::factory()->create([
            'user_id' => $this->investor->id,
            'status' => Dispute::STATUS_RESOLVED_REJECTED,
        ]);

        $response = $this->getJson("/api/v1/admin/dispute-management/{$dispute->id}");

        $permissions = $response->json('data.permissions');

        $this->assertTrue($permissions['can_close']);
        $this->assertFalse($permissions['can_refund']); // Rejected disputes don't get refunds
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function closed_dispute_has_no_actions()
    {
        Sanctum::actingAs($this->admin, ['*']);

        $dispute = Dispute::factory()->create([
            'user_id' => $this->investor->id,
            'status' => Dispute::STATUS_CLOSED,
        ]);

        $response = $this->getJson("/api/v1/admin/dispute-management/{$dispute->id}");

        $permissions = $response->json('data.permissions');

        $this->assertFalse($permissions['can_transition']);
        $this->assertFalse($permissions['can_escalate']);
        $this->assertFalse($permissions['can_resolve']);
        $this->assertFalse($permissions['can_refund']);
        $this->assertFalse($permissions['can_close']);
        $this->assertFalse($permissions['can_override_defensibility']);
        $this->assertEmpty($permissions['available_transitions']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function defensibility_override_available_for_active_disputes()
    {
        Sanctum::actingAs($this->admin, ['*']);

        $dispute = Dispute::factory()->create([
            'user_id' => $this->investor->id,
            'status' => Dispute::STATUS_UNDER_REVIEW,
        ]);

        $response = $this->getJson("/api/v1/admin/dispute-management/{$dispute->id}");

        $permissions = $response->json('data.permissions');

        $this->assertTrue($permissions['can_override_defensibility']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function defensibility_override_not_available_for_resolved()
    {
        Sanctum::actingAs($this->admin, ['*']);

        $dispute = Dispute::factory()->create([
            'user_id' => $this->investor->id,
            'status' => Dispute::STATUS_RESOLVED_APPROVED,
        ]);

        $response = $this->getJson("/api/v1/admin/dispute-management/{$dispute->id}");

        $permissions = $response->json('data.permissions');

        $this->assertFalse($permissions['can_override_defensibility']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function available_transitions_match_state_machine()
    {
        Sanctum::actingAs($this->admin, ['*']);

        $dispute = Dispute::factory()->create([
            'user_id' => $this->investor->id,
            'status' => Dispute::STATUS_OPEN,
        ]);

        $response = $this->getJson("/api/v1/admin/dispute-management/{$dispute->id}");

        $apiTransitions = $response->json('data.permissions.available_transitions');

        // Get transitions from state machine
        $stateMachineTransitions = $this->stateMachine->getAvailableTransitions($dispute, $this->admin);
        $stateMachineValues = array_map(fn($s) => $s->value, $stateMachineTransitions);

        // API should return same transitions as state machine
        $this->assertEquals(sort($stateMachineValues), sort($apiTransitions));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function permissions_included_in_list_response()
    {
        Sanctum::actingAs($this->admin, ['*']);

        Dispute::factory()->create([
            'user_id' => $this->investor->id,
            'status' => Dispute::STATUS_OPEN,
        ]);

        $response = $this->getJson('/api/v1/admin/dispute-management');

        $response->assertStatus(200);

        $firstDispute = $response->json('data.0');
        $this->assertArrayHasKey('permissions', $firstDispute);
        $this->assertArrayHasKey('can_transition', $firstDispute['permissions']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function status_enum_methods_work_correctly()
    {
        // Test isTerminal
        $this->assertTrue(DisputeStatus::CLOSED->isTerminal());
        $this->assertFalse(DisputeStatus::OPEN->isTerminal());
        $this->assertFalse(DisputeStatus::ESCALATED->isTerminal());

        // Test isResolved
        $this->assertTrue(DisputeStatus::RESOLVED_APPROVED->isResolved());
        $this->assertTrue(DisputeStatus::RESOLVED_REJECTED->isResolved());
        $this->assertFalse(DisputeStatus::ESCALATED->isResolved());
        $this->assertFalse(DisputeStatus::CLOSED->isResolved());

        // Test allowsRefund
        $this->assertTrue(DisputeStatus::RESOLVED_APPROVED->allowsRefund());
        $this->assertFalse(DisputeStatus::RESOLVED_REJECTED->allowsRefund());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function active_states_do_not_include_terminal()
    {
        $activeStates = DisputeStatus::activeStates();

        $this->assertContains(DisputeStatus::OPEN, $activeStates);
        $this->assertContains(DisputeStatus::UNDER_REVIEW, $activeStates);
        $this->assertContains(DisputeStatus::AWAITING_INVESTOR, $activeStates);
        $this->assertContains(DisputeStatus::ESCALATED, $activeStates);

        $this->assertNotContains(DisputeStatus::RESOLVED_APPROVED, $activeStates);
        $this->assertNotContains(DisputeStatus::RESOLVED_REJECTED, $activeStates);
        $this->assertNotContains(DisputeStatus::CLOSED, $activeStates);
    }

    // =========================================================================
    // INVESTOR PERMISSION FLAGS
    // Frontend MUST use these flags - NOT derive behavior from status
    // =========================================================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function investor_can_add_evidence_only_when_awaiting_investor()
    {
        Sanctum::actingAs($this->investor, ['*']);

        // Awaiting investor - can add evidence
        $dispute = Dispute::factory()->create([
            'user_id' => $this->investor->id,
            'status' => Dispute::STATUS_AWAITING_INVESTOR,
        ]);

        $response = $this->getJson("/api/v1/user/disputes/{$dispute->id}");
        $response->assertStatus(200);

        $permissions = $response->json('data.permissions');
        $this->assertNotNull($permissions, 'permissions key must exist in response');
        $this->assertTrue($permissions['can_add_evidence']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function investor_cannot_add_evidence_when_open()
    {
        Sanctum::actingAs($this->investor, ['*']);

        $dispute = Dispute::factory()->create([
            'user_id' => $this->investor->id,
            'status' => Dispute::STATUS_OPEN,
        ]);

        $response = $this->getJson("/api/v1/user/disputes/{$dispute->id}");
        $response->assertStatus(200);

        $permissions = $response->json('data.permissions');
        $this->assertFalse($permissions['can_add_evidence']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function investor_cannot_add_evidence_when_under_review()
    {
        Sanctum::actingAs($this->investor, ['*']);

        $dispute = Dispute::factory()->create([
            'user_id' => $this->investor->id,
            'status' => Dispute::STATUS_UNDER_REVIEW,
        ]);

        $response = $this->getJson("/api/v1/user/disputes/{$dispute->id}");
        $response->assertStatus(200);

        $permissions = $response->json('data.permissions');
        $this->assertFalse($permissions['can_add_evidence']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function investor_cannot_add_evidence_when_closed()
    {
        Sanctum::actingAs($this->investor, ['*']);

        $dispute = Dispute::factory()->create([
            'user_id' => $this->investor->id,
            'status' => Dispute::STATUS_CLOSED,
        ]);

        $response = $this->getJson("/api/v1/user/disputes/{$dispute->id}");
        $response->assertStatus(200);

        $permissions = $response->json('data.permissions');
        $this->assertFalse($permissions['can_add_evidence']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function investor_can_add_comment_on_active_disputes()
    {
        Sanctum::actingAs($this->investor, ['*']);

        $activeStatuses = [
            Dispute::STATUS_OPEN,
            Dispute::STATUS_UNDER_REVIEW,
            Dispute::STATUS_AWAITING_INVESTOR,
            Dispute::STATUS_ESCALATED,
        ];

        foreach ($activeStatuses as $status) {
            $dispute = Dispute::factory()->create([
                'user_id' => $this->investor->id,
                'status' => $status,
            ]);

            $response = $this->getJson("/api/v1/user/disputes/{$dispute->id}");
            $response->assertStatus(200);

            $permissions = $response->json('data.permissions');
            $this->assertTrue(
                $permissions['can_add_comment'],
                "can_add_comment should be true for status: {$status}"
            );
        }
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function investor_cannot_add_comment_on_closed_dispute()
    {
        Sanctum::actingAs($this->investor, ['*']);

        $dispute = Dispute::factory()->create([
            'user_id' => $this->investor->id,
            'status' => Dispute::STATUS_CLOSED,
        ]);

        $response = $this->getJson("/api/v1/user/disputes/{$dispute->id}");
        $response->assertStatus(200);

        $permissions = $response->json('data.permissions');
        $this->assertFalse($permissions['can_add_comment']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function investor_permission_flags_always_present_in_response()
    {
        Sanctum::actingAs($this->investor, ['*']);

        $dispute = Dispute::factory()->create([
            'user_id' => $this->investor->id,
            'status' => Dispute::STATUS_OPEN,
        ]);

        $response = $this->getJson("/api/v1/user/disputes/{$dispute->id}");
        $response->assertStatus(200);

        $response->assertJsonStructure([
            'data' => [
                'permissions' => [
                    'can_add_evidence',
                    'can_add_comment',
                ],
            ],
        ]);
    }
}
