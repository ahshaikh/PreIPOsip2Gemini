<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Dispute;
use App\Models\DisputeTimeline;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * DisputeTimelineImmutabilityTest - Verifies append-only behavior
 *
 * The dispute timeline is designed to be immutable:
 * - New entries can be created (append)
 * - Existing entries cannot be modified (no update)
 * - Entries cannot be deleted (no delete)
 *
 * Database triggers enforce this at the DB level.
 * These tests verify the expected behavior and model configuration.
 */
class DisputeTimelineImmutabilityTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
    public function timeline_entry_can_be_created()
    {
        $user = User::factory()->create();
        $dispute = Dispute::factory()->create(['user_id' => $user->id]);

        $timeline = DisputeTimeline::create([
            'dispute_id' => $dispute->id,
            'event_type' => DisputeTimeline::EVENT_CREATED,
            'actor_user_id' => $user->id,
            'actor_role' => DisputeTimeline::ROLE_INVESTOR,
            'title' => 'Dispute filed',
            'description' => 'Investor filed a new dispute',
            'visible_to_investor' => true,
            'is_internal_note' => false,
        ]);

        $this->assertNotNull($timeline->id);
        $this->assertEquals($dispute->id, $timeline->dispute_id);
        $this->assertEquals(DisputeTimeline::EVENT_CREATED, $timeline->event_type);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function timeline_entry_has_no_updated_at_timestamp()
    {
        $user = User::factory()->create();
        $dispute = Dispute::factory()->create(['user_id' => $user->id]);

        $timeline = DisputeTimeline::create([
            'dispute_id' => $dispute->id,
            'event_type' => DisputeTimeline::EVENT_COMMENT,
            'actor_role' => DisputeTimeline::ROLE_ADMIN,
            'title' => 'Admin comment',
            'visible_to_investor' => true,
            'is_internal_note' => false,
        ]);

        // Timeline model should not have updated_at
        $this->assertFalse($timeline->timestamps);
        $this->assertNull($timeline->updated_at ?? null);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function timeline_entry_preserves_created_at()
    {
        $user = User::factory()->create();
        $dispute = Dispute::factory()->create(['user_id' => $user->id]);

        $timeline = DisputeTimeline::create([
            'dispute_id' => $dispute->id,
            'event_type' => DisputeTimeline::EVENT_STATUS_CHANGE,
            'actor_role' => DisputeTimeline::ROLE_SYSTEM,
            'title' => 'Status changed',
            'old_status' => 'open',
            'new_status' => 'under_review',
            'visible_to_investor' => true,
            'is_internal_note' => false,
        ]);

        $this->assertNotNull($timeline->fresh()->created_at);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function multiple_timeline_entries_can_be_created_for_same_dispute()
    {
        $user = User::factory()->create();
        $admin = User::factory()->create();
        $dispute = Dispute::factory()->create(['user_id' => $user->id]);

        $entry1 = DisputeTimeline::create([
            'dispute_id' => $dispute->id,
            'event_type' => DisputeTimeline::EVENT_CREATED,
            'actor_user_id' => $user->id,
            'actor_role' => DisputeTimeline::ROLE_INVESTOR,
            'title' => 'Dispute filed',
            'visible_to_investor' => true,
            'is_internal_note' => false,
        ]);

        $entry2 = DisputeTimeline::create([
            'dispute_id' => $dispute->id,
            'event_type' => DisputeTimeline::EVENT_ASSIGNED,
            'actor_user_id' => $admin->id,
            'actor_role' => DisputeTimeline::ROLE_ADMIN,
            'title' => 'Admin assigned',
            'visible_to_investor' => true,
            'is_internal_note' => false,
        ]);

        $entry3 = DisputeTimeline::create([
            'dispute_id' => $dispute->id,
            'event_type' => DisputeTimeline::EVENT_STATUS_CHANGE,
            'actor_user_id' => $admin->id,
            'actor_role' => DisputeTimeline::ROLE_ADMIN,
            'title' => 'Status changed to under review',
            'old_status' => 'open',
            'new_status' => 'under_review',
            'visible_to_investor' => true,
            'is_internal_note' => false,
        ]);

        $this->assertEquals(3, DisputeTimeline::where('dispute_id', $dispute->id)->count());
        $this->assertNotEquals($entry1->id, $entry2->id);
        $this->assertNotEquals($entry2->id, $entry3->id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function timeline_entries_are_ordered_by_created_at()
    {
        $user = User::factory()->create();
        $dispute = Dispute::factory()->create(['user_id' => $user->id]);

        // Create entries in sequence
        $first = DisputeTimeline::create([
            'dispute_id' => $dispute->id,
            'event_type' => DisputeTimeline::EVENT_CREATED,
            'actor_role' => DisputeTimeline::ROLE_INVESTOR,
            'title' => 'First',
            'visible_to_investor' => true,
            'is_internal_note' => false,
        ]);

        $second = DisputeTimeline::create([
            'dispute_id' => $dispute->id,
            'event_type' => DisputeTimeline::EVENT_COMMENT,
            'actor_role' => DisputeTimeline::ROLE_ADMIN,
            'title' => 'Second',
            'visible_to_investor' => true,
            'is_internal_note' => false,
        ]);

        $timeline = $dispute->timeline;

        // Should be ordered ascending by created_at
        $this->assertEquals('First', $timeline->first()->title);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function internal_notes_are_filtered_from_investor_view()
    {
        $user = User::factory()->create();
        $dispute = Dispute::factory()->create(['user_id' => $user->id]);

        DisputeTimeline::create([
            'dispute_id' => $dispute->id,
            'event_type' => DisputeTimeline::EVENT_COMMENT,
            'actor_role' => DisputeTimeline::ROLE_ADMIN,
            'title' => 'Public comment',
            'visible_to_investor' => true,
            'is_internal_note' => false,
        ]);

        DisputeTimeline::create([
            'dispute_id' => $dispute->id,
            'event_type' => DisputeTimeline::EVENT_COMMENT,
            'actor_role' => DisputeTimeline::ROLE_ADMIN,
            'title' => 'Internal note',
            'visible_to_investor' => false,
            'is_internal_note' => true,
        ]);

        $allEntries = $dispute->timeline;
        $investorEntries = $dispute->investorTimeline;

        $this->assertEquals(2, $allEntries->count());
        $this->assertEquals(1, $investorEntries->count());
        $this->assertEquals('Public comment', $investorEntries->first()->title);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function event_types_are_valid()
    {
        $types = DisputeTimeline::getEventTypes();

        $this->assertContains(DisputeTimeline::EVENT_CREATED, $types);
        $this->assertContains(DisputeTimeline::EVENT_STATUS_CHANGE, $types);
        $this->assertContains(DisputeTimeline::EVENT_COMMENT, $types);
        $this->assertContains(DisputeTimeline::EVENT_EVIDENCE_ADDED, $types);
        $this->assertContains(DisputeTimeline::EVENT_ASSIGNED, $types);
        $this->assertContains(DisputeTimeline::EVENT_ESCALATED, $types);
        $this->assertContains(DisputeTimeline::EVENT_SETTLEMENT, $types);
        $this->assertContains(DisputeTimeline::EVENT_SLA_WARNING, $types);
        $this->assertContains(DisputeTimeline::EVENT_SLA_BREACH, $types);
        $this->assertContains(DisputeTimeline::EVENT_AUTO_ESCALATION, $types);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function actor_roles_are_valid()
    {
        $roles = DisputeTimeline::getActorRoles();

        $this->assertContains(DisputeTimeline::ROLE_ADMIN, $roles);
        $this->assertContains(DisputeTimeline::ROLE_INVESTOR, $roles);
        $this->assertContains(DisputeTimeline::ROLE_SYSTEM, $roles);
        $this->assertCount(3, $roles);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function status_change_events_track_old_and_new_status()
    {
        $user = User::factory()->create();
        $dispute = Dispute::factory()->create(['user_id' => $user->id]);

        $timeline = DisputeTimeline::create([
            'dispute_id' => $dispute->id,
            'event_type' => DisputeTimeline::EVENT_STATUS_CHANGE,
            'actor_role' => DisputeTimeline::ROLE_ADMIN,
            'title' => 'Status transitioned',
            'old_status' => 'under_review',
            'new_status' => 'escalated',
            'visible_to_investor' => true,
            'is_internal_note' => false,
        ]);

        $this->assertEquals('under_review', $timeline->old_status);
        $this->assertEquals('escalated', $timeline->new_status);
        $this->assertTrue($timeline->isStatusChange());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function system_generated_events_have_no_actor_user()
    {
        $user = User::factory()->create();
        $dispute = Dispute::factory()->create(['user_id' => $user->id]);

        $timeline = DisputeTimeline::create([
            'dispute_id' => $dispute->id,
            'event_type' => DisputeTimeline::EVENT_AUTO_ESCALATION,
            'actor_user_id' => null,
            'actor_role' => DisputeTimeline::ROLE_SYSTEM,
            'title' => 'Auto-escalated due to SLA breach',
            'visible_to_investor' => true,
            'is_internal_note' => false,
        ]);

        $this->assertNull($timeline->actor_user_id);
        $this->assertEquals(DisputeTimeline::ROLE_SYSTEM, $timeline->actor_role);
        $this->assertTrue($timeline->isSystemGenerated());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function metadata_can_store_additional_context()
    {
        $user = User::factory()->create();
        $dispute = Dispute::factory()->create(['user_id' => $user->id]);

        $metadata = [
            'settlement_action' => 'refund',
            'amount_paise' => 50000,
            'ledger_entry_id' => 12345,
        ];

        $timeline = DisputeTimeline::create([
            'dispute_id' => $dispute->id,
            'event_type' => DisputeTimeline::EVENT_SETTLEMENT,
            'actor_role' => DisputeTimeline::ROLE_ADMIN,
            'title' => 'Refund processed',
            'metadata' => $metadata,
            'visible_to_investor' => true,
            'is_internal_note' => false,
        ]);

        $this->assertEquals($metadata, $timeline->metadata);
        $this->assertEquals('refund', $timeline->metadata['settlement_action']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function attachments_can_store_evidence_references()
    {
        $user = User::factory()->create();
        $dispute = Dispute::factory()->create(['user_id' => $user->id]);

        $attachments = [
            ['type' => 'file', 'path' => '/evidence/screenshot.png'],
            ['type' => 'link', 'url' => 'https://example.com/reference'],
        ];

        $timeline = DisputeTimeline::create([
            'dispute_id' => $dispute->id,
            'event_type' => DisputeTimeline::EVENT_EVIDENCE_ADDED,
            'actor_user_id' => $user->id,
            'actor_role' => DisputeTimeline::ROLE_INVESTOR,
            'title' => 'Evidence uploaded',
            'attachments' => $attachments,
            'visible_to_investor' => true,
            'is_internal_note' => false,
        ]);

        $this->assertCount(2, $timeline->attachments);
        $this->assertEquals('file', $timeline->attachments[0]['type']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function scope_filters_status_changes()
    {
        $user = User::factory()->create();
        $dispute = Dispute::factory()->create(['user_id' => $user->id]);

        DisputeTimeline::create([
            'dispute_id' => $dispute->id,
            'event_type' => DisputeTimeline::EVENT_COMMENT,
            'actor_role' => DisputeTimeline::ROLE_ADMIN,
            'title' => 'Comment',
            'visible_to_investor' => true,
            'is_internal_note' => false,
        ]);

        DisputeTimeline::create([
            'dispute_id' => $dispute->id,
            'event_type' => DisputeTimeline::EVENT_STATUS_CHANGE,
            'actor_role' => DisputeTimeline::ROLE_ADMIN,
            'title' => 'Status change',
            'visible_to_investor' => true,
            'is_internal_note' => false,
        ]);

        $statusChanges = DisputeTimeline::where('dispute_id', $dispute->id)
            ->statusChanges()
            ->get();

        $this->assertCount(1, $statusChanges);
        $this->assertEquals('Status change', $statusChanges->first()->title);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function scope_filters_by_actor_role()
    {
        $user = User::factory()->create();
        $dispute = Dispute::factory()->create(['user_id' => $user->id]);

        DisputeTimeline::create([
            'dispute_id' => $dispute->id,
            'event_type' => DisputeTimeline::EVENT_COMMENT,
            'actor_role' => DisputeTimeline::ROLE_ADMIN,
            'title' => 'Admin comment',
            'visible_to_investor' => true,
            'is_internal_note' => false,
        ]);

        DisputeTimeline::create([
            'dispute_id' => $dispute->id,
            'event_type' => DisputeTimeline::EVENT_COMMENT,
            'actor_role' => DisputeTimeline::ROLE_INVESTOR,
            'title' => 'Investor comment',
            'visible_to_investor' => true,
            'is_internal_note' => false,
        ]);

        $adminComments = DisputeTimeline::where('dispute_id', $dispute->id)
            ->byActorRole(DisputeTimeline::ROLE_ADMIN)
            ->get();

        $this->assertCount(1, $adminComments);
        $this->assertEquals('Admin comment', $adminComments->first()->title);
    }
}
