<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Enums\DisputeStatus;
use App\Enums\DisputeType;
use App\Models\Dispute;
use App\Models\User;
use App\Models\DisputeTimeline;
use App\Services\DisputeStateMachine;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DisputeStateMachineTest extends TestCase
{
    use RefreshDatabase;

    protected DisputeStateMachine $stateMachine;
    protected User $admin;
    protected User $investor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->stateMachine = new DisputeStateMachine();

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->investor = User::factory()->create();
        $this->investor->assignRole('user');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_allows_valid_transition_from_open_to_under_review()
    {
        $dispute = Dispute::factory()->create([
            'status' => DisputeStatus::OPEN->value,
            'user_id' => $this->investor->id,
        ]);

        $result = $this->stateMachine->transition(
            $dispute,
            DisputeStatus::UNDER_REVIEW,
            $this->admin,
            'Starting review'
        );

        $this->assertEquals(DisputeStatus::UNDER_REVIEW->value, $result->status);
        $this->assertNotNull($result->investigation_started_at);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_prevents_invalid_transition()
    {
        $dispute = Dispute::factory()->create([
            'status' => DisputeStatus::OPEN->value,
            'user_id' => $this->investor->id,
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Transition from 'open' to 'resolved_approved' is not allowed");

        $this->stateMachine->transition(
            $dispute,
            DisputeStatus::RESOLVED_APPROVED,
            $this->admin,
            'Invalid transition'
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_creates_timeline_entry_on_transition()
    {
        $dispute = Dispute::factory()->create([
            'status' => DisputeStatus::OPEN->value,
            'user_id' => $this->investor->id,
        ]);

        $this->stateMachine->transition(
            $dispute,
            DisputeStatus::UNDER_REVIEW,
            $this->admin,
            'Review comment'
        );

        $this->assertDatabaseHas('dispute_timelines', [
            'dispute_id' => $dispute->id,
            'event_type' => DisputeTimeline::EVENT_STATUS_CHANGE,
            'old_status' => DisputeStatus::OPEN->value,
            'new_status' => DisputeStatus::UNDER_REVIEW->value,
            'actor_user_id' => $this->admin->id,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_escalates_dispute_correctly()
    {
        $dispute = Dispute::factory()->create([
            'status' => DisputeStatus::UNDER_REVIEW->value,
            'user_id' => $this->investor->id,
            'risk_score' => 2,
        ]);

        $result = $this->stateMachine->escalate(
            $dispute,
            $this->admin,
            'High priority issue'
        );

        $this->assertEquals(DisputeStatus::ESCALATED->value, $result->status);
        $this->assertNotNull($result->escalated_at);
        $this->assertEquals(3, $result->risk_score); // Increased by 1
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_resolves_dispute_as_approved()
    {
        $dispute = Dispute::factory()->create([
            'status' => DisputeStatus::UNDER_REVIEW->value,
            'user_id' => $this->investor->id,
        ]);

        $result = $this->stateMachine->resolveApproved(
            $dispute,
            $this->admin,
            'Approved after investigation',
            ['amount' => 1000]
        );

        $this->assertEquals(DisputeStatus::RESOLVED_APPROVED->value, $result->status);
        $this->assertNotNull($result->resolved_at);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_resolves_dispute_as_rejected()
    {
        $dispute = Dispute::factory()->create([
            'status' => DisputeStatus::UNDER_REVIEW->value,
            'user_id' => $this->investor->id,
        ]);

        $result = $this->stateMachine->resolveRejected(
            $dispute,
            $this->admin,
            'No valid claim'
        );

        $this->assertEquals(DisputeStatus::RESOLVED_REJECTED->value, $result->status);
        $this->assertNotNull($result->resolved_at);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_closes_dispute_correctly()
    {
        $dispute = Dispute::factory()->create([
            'status' => DisputeStatus::RESOLVED_APPROVED->value,
            'user_id' => $this->investor->id,
        ]);

        $result = $this->stateMachine->close(
            $dispute,
            $this->admin,
            'Final closure'
        );

        $this->assertEquals(DisputeStatus::CLOSED->value, $result->status);
        $this->assertNotNull($result->closed_at);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function closed_is_terminal_state()
    {
        $dispute = Dispute::factory()->create([
            'status' => DisputeStatus::CLOSED->value,
            'user_id' => $this->investor->id,
        ]);

        $this->expectException(\InvalidArgumentException::class);

        $this->stateMachine->transition(
            $dispute,
            DisputeStatus::OPEN,
            $this->admin,
            'Trying to reopen'
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function investor_can_respond_from_awaiting_investor()
    {
        $dispute = Dispute::factory()->create([
            'status' => DisputeStatus::AWAITING_INVESTOR->value,
            'user_id' => $this->investor->id,
        ]);

        $result = $this->stateMachine->transition(
            $dispute,
            DisputeStatus::UNDER_REVIEW,
            $this->investor,
            'My response'
        );

        $this->assertEquals(DisputeStatus::UNDER_REVIEW->value, $result->status);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function investor_can_withdraw_from_awaiting_investor()
    {
        $dispute = Dispute::factory()->create([
            'status' => DisputeStatus::AWAITING_INVESTOR->value,
            'user_id' => $this->investor->id,
        ]);

        $result = $this->stateMachine->transition(
            $dispute,
            DisputeStatus::CLOSED,
            $this->investor,
            'Withdrawing dispute'
        );

        $this->assertEquals(DisputeStatus::CLOSED->value, $result->status);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function investor_can_appeal_from_rejected()
    {
        $dispute = Dispute::factory()->create([
            'status' => DisputeStatus::RESOLVED_REJECTED->value,
            'user_id' => $this->investor->id,
        ]);

        $result = $this->stateMachine->transition(
            $dispute,
            DisputeStatus::ESCALATED,
            $this->investor,
            'I want to appeal'
        );

        $this->assertEquals(DisputeStatus::ESCALATED->value, $result->status);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function get_available_transitions_returns_correct_options()
    {
        $dispute = Dispute::factory()->create([
            'status' => DisputeStatus::UNDER_REVIEW->value,
            'user_id' => $this->investor->id,
        ]);

        $transitions = $this->stateMachine->getAvailableTransitions($dispute, $this->admin);

        $transitionValues = array_map(fn($t) => $t->value, $transitions);

        $this->assertContains(DisputeStatus::AWAITING_INVESTOR->value, $transitionValues);
        $this->assertContains(DisputeStatus::ESCALATED->value, $transitionValues);
        $this->assertContains(DisputeStatus::RESOLVED_APPROVED->value, $transitionValues);
        $this->assertContains(DisputeStatus::RESOLVED_REJECTED->value, $transitionValues);
    }
}
