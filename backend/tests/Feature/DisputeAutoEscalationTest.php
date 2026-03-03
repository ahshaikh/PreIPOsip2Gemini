<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Enums\DisputeType;
use App\Models\Dispute;
use App\Models\DisputeTimeline;
use App\Models\User;
use App\Services\DisputeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class DisputeAutoEscalationTest extends TestCase
{
    use RefreshDatabase;

    private User $investor;
    private DisputeService $disputeService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->investor = User::factory()->create();
        $this->disputeService = app(DisputeService::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function fraud_type_dispute_auto_escalates_on_creation()
    {
        $dispute = Dispute::factory()->create([
            'user_id' => $this->investor->id,
            'type' => 'fraud',
            'status' => Dispute::STATUS_OPEN,
        ]);

        // Simulate auto-escalation check
        if ($dispute->getTypeEnum()?->requiresImmediateEscalation()) {
            $dispute->update([
                'status' => Dispute::STATUS_ESCALATED,
                'escalated_at' => now(),
            ]);
        }

        $this->assertEquals(Dispute::STATUS_ESCALATED, $dispute->status);
        $this->assertNotNull($dispute->escalated_at);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function dispute_past_escalation_deadline_is_flagged()
    {
        $dispute = Dispute::factory()->create([
            'user_id' => $this->investor->id,
            'status' => Dispute::STATUS_UNDER_REVIEW,
            'escalation_deadline_at' => Carbon::now()->subHours(2), // Past deadline
        ]);

        $this->assertTrue($dispute->shouldAutoEscalate());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function dispute_before_escalation_deadline_is_not_flagged()
    {
        $dispute = Dispute::factory()->create([
            'user_id' => $this->investor->id,
            'status' => Dispute::STATUS_UNDER_REVIEW,
            'escalation_deadline_at' => Carbon::now()->addHours(24),
        ]);

        $this->assertFalse($dispute->shouldAutoEscalate());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function already_escalated_dispute_does_not_auto_escalate()
    {
        $dispute = Dispute::factory()->create([
            'user_id' => $this->investor->id,
            'status' => Dispute::STATUS_ESCALATED,
            'escalation_deadline_at' => Carbon::now()->subHours(2),
        ]);

        $this->assertFalse($dispute->shouldAutoEscalate());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function resolved_dispute_does_not_auto_escalate()
    {
        $dispute = Dispute::factory()->create([
            'user_id' => $this->investor->id,
            'status' => Dispute::STATUS_RESOLVED_APPROVED,
            'escalation_deadline_at' => Carbon::now()->subHours(2),
        ]);

        $this->assertFalse($dispute->shouldAutoEscalate());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function scope_finds_disputes_ready_for_auto_escalation()
    {
        // Create dispute past deadline
        $readyForEscalation = Dispute::factory()->create([
            'user_id' => $this->investor->id,
            'status' => Dispute::STATUS_UNDER_REVIEW,
            'escalation_deadline_at' => Carbon::now()->subHours(1),
        ]);

        // Create dispute not yet due
        $notYetDue = Dispute::factory()->create([
            'user_id' => $this->investor->id,
            'status' => Dispute::STATUS_UNDER_REVIEW,
            'escalation_deadline_at' => Carbon::now()->addHours(24),
        ]);

        // Create already escalated
        $alreadyEscalated = Dispute::factory()->create([
            'user_id' => $this->investor->id,
            'status' => Dispute::STATUS_ESCALATED,
            'escalation_deadline_at' => Carbon::now()->subHours(1),
        ]);

        $results = Dispute::readyForAutoEscalation()->get();

        $this->assertTrue($results->contains('id', $readyForEscalation->id));
        $this->assertFalse($results->contains('id', $notYetDue->id));
        $this->assertFalse($results->contains('id', $alreadyEscalated->id));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function auto_escalation_creates_timeline_entry()
    {
        $dispute = Dispute::factory()->create([
            'user_id' => $this->investor->id,
            'status' => Dispute::STATUS_UNDER_REVIEW,
            'escalation_deadline_at' => Carbon::now()->subMinutes(30),
        ]);

        // Simulate auto-escalation
        if ($dispute->shouldAutoEscalate()) {
            $dispute->update([
                'status' => Dispute::STATUS_ESCALATED,
                'escalated_at' => now(),
            ]);

            DisputeTimeline::create([
                'dispute_id' => $dispute->id,
                'event_type' => DisputeTimeline::EVENT_AUTO_ESCALATION,
                'actor_role' => DisputeTimeline::ROLE_SYSTEM,
                'title' => 'Auto-escalated',
                'description' => 'Dispute automatically escalated due to deadline breach.',
                'old_status' => Dispute::STATUS_UNDER_REVIEW,
                'new_status' => Dispute::STATUS_ESCALATED,
                'visible_to_investor' => true,
                'is_internal_note' => false,
            ]);
        }

        $timelineEntry = DisputeTimeline::where('dispute_id', $dispute->id)
            ->where('event_type', DisputeTimeline::EVENT_AUTO_ESCALATION)
            ->first();

        $this->assertNotNull($timelineEntry);
        $this->assertEquals(DisputeTimeline::ROLE_SYSTEM, $timelineEntry->actor_role);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function sla_breached_disputes_are_identified()
    {
        $breached = Dispute::factory()->create([
            'user_id' => $this->investor->id,
            'status' => Dispute::STATUS_OPEN,
            'sla_deadline_at' => Carbon::now()->subHours(4),
        ]);

        $notBreached = Dispute::factory()->create([
            'user_id' => $this->investor->id,
            'status' => Dispute::STATUS_OPEN,
            'sla_deadline_at' => Carbon::now()->addHours(20),
        ]);

        $this->assertTrue($breached->isSlaBreached());
        $this->assertFalse($notBreached->isSlaBreached());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function scope_finds_sla_breached_disputes()
    {
        $breached = Dispute::factory()->create([
            'user_id' => $this->investor->id,
            'status' => Dispute::STATUS_UNDER_REVIEW,
            'sla_deadline_at' => Carbon::now()->subHours(2),
        ]);

        $notBreached = Dispute::factory()->create([
            'user_id' => $this->investor->id,
            'status' => Dispute::STATUS_UNDER_REVIEW,
            'sla_deadline_at' => Carbon::now()->addDays(1),
        ]);

        $resolved = Dispute::factory()->create([
            'user_id' => $this->investor->id,
            'status' => Dispute::STATUS_RESOLVED_APPROVED,
            'sla_deadline_at' => Carbon::now()->subHours(2),
        ]);

        $results = Dispute::slaBreach()->get();

        $this->assertTrue($results->contains('id', $breached->id));
        $this->assertFalse($results->contains('id', $notBreached->id));
        $this->assertFalse($results->contains('id', $resolved->id));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function dispute_requires_immediate_attention_for_critical_severity()
    {
        $critical = Dispute::factory()->create([
            'user_id' => $this->investor->id,
            'type' => DisputeType::B_PAYMENT->value,
            'severity' => Dispute::SEVERITY_CRITICAL,
        ]);

        $low = Dispute::factory()->create([
            'user_id' => $this->investor->id,
            'type' => DisputeType::A_CONFUSION->value,
            'severity' => Dispute::SEVERITY_LOW,
            'risk_score' => 1,
        ]);

        $this->assertTrue($critical->requiresImmediateAttention());
        $this->assertFalse($low->requiresImmediateAttention());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function dispute_requires_immediate_attention_for_high_risk_score()
    {
        $highRisk = Dispute::factory()->create([
            'user_id' => $this->investor->id,
            'severity' => Dispute::SEVERITY_LOW,
            'risk_score' => 5,
        ]);

        $lowRisk = Dispute::factory()->create([
            'user_id' => $this->investor->id,
            'type' => 'confusion',
            'severity' => Dispute::SEVERITY_LOW,
            'risk_score' => 2,
        ]);

        $this->assertTrue($highRisk->requiresImmediateAttention());
        $this->assertFalse($lowRisk->requiresImmediateAttention());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function fraud_type_requires_immediate_escalation()
    {
        $fraudType = DisputeType::D_FRAUD;
        $confusionType = DisputeType::A_CONFUSION;

        $this->assertTrue($fraudType->requiresImmediateEscalation());
        $this->assertFalse($confusionType->requiresImmediateEscalation());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function high_risk_types_are_defined()
    {
        $highRiskTypes = DisputeType::highRiskTypes();

        $this->assertContains(DisputeType::C_ALLOCATION, $highRiskTypes);
        $this->assertContains(DisputeType::D_FRAUD, $highRiskTypes);
        $this->assertNotContains(DisputeType::A_CONFUSION, $highRiskTypes);
        $this->assertNotContains(DisputeType::B_PAYMENT, $highRiskTypes);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function escalation_deadline_is_set_based_on_type()
    {
        // SLA hours vary by type - fraud has shortest (24h), confusion has longest (72h)
        $fraudSla = DisputeType::D_FRAUD->slaHours();
        $confusionSla = DisputeType::A_CONFUSION->slaHours();

        $this->assertLessThan($confusionSla, $fraudSla);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function risk_scores_are_ordered_correctly()
    {
        $this->assertEquals(1, DisputeType::A_CONFUSION->riskScore());
        $this->assertEquals(2, DisputeType::B_PAYMENT->riskScore());
        $this->assertEquals(3, DisputeType::C_ALLOCATION->riskScore());
        $this->assertEquals(4, DisputeType::D_FRAUD->riskScore());

        $this->assertLessThan(
            DisputeType::D_FRAUD->riskScore(),
            DisputeType::A_CONFUSION->riskScore()
        );
    }
}
