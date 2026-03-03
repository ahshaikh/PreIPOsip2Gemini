<?php

namespace Tests\Feature;

use Tests\FeatureTestCase;
use App\Enums\DisputeStatus;
use App\Enums\DisputeType;
use App\Models\Dispute;
use App\Models\DisputeSnapshot;
use App\Models\DisputeTimeline;
use App\Models\User;
use App\Models\Payment;
use App\Models\Setting;

class DisputeLifecycleTest extends FeatureTestCase
{
    protected User $admin;
    protected User $investor;
    protected Payment $payment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this->seed(\Database\Seeders\SettingsSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
        $this->admin->givePermissionTo('compliance.view_legal');

        $this->investor = User::factory()->create();
        $this->investor->assignRole('user');

        $this->payment = Payment::factory()->create([
            'user_id' => $this->investor->id,
            'amount' => 5000,
            'status' => 'paid',
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function investor_can_file_dispute()
    {
        $response = $this->actingAs($this->investor)->postJson('/api/v1/user/disputes', [
            'type' => DisputeType::B_PAYMENT->value,
            'disputable_type' => 'Payment',
            'disputable_id' => $this->payment->id,
            'title' => 'Payment not reflected',
            'description' => 'I paid but my wallet was not credited',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('message', 'Dispute filed successfully.');

        $this->assertDatabaseHas('disputes', [
            'user_id' => $this->investor->id,
            'type' => DisputeType::B_PAYMENT->value,
            'status' => DisputeStatus::OPEN->value,
            'title' => 'Payment not reflected',
        ]);

        // Snapshot should be created
        $dispute = Dispute::where('user_id', $this->investor->id)->first();
        $this->assertNotNull($dispute->snapshot);
        $this->assertTrue($dispute->snapshot->verifyIntegrity());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_start_review()
    {
        $dispute = Dispute::factory()->create([
            'user_id' => $this->investor->id,
            'status' => DisputeStatus::OPEN->value,
        ]);
        DisputeSnapshot::factory()->create(['dispute_id' => $dispute->id]);

        $response = $this->actingAs($this->admin, 'sanctum')->postJson("/api/v1/admin/disputes/{$dispute->id}/transition", [
            'target_status' => DisputeStatus::UNDER_REVIEW->value,
            'comment' => 'Starting investigation',
        ]);

        $response->assertStatus(200);

        $dispute->refresh();
        $this->assertEquals(DisputeStatus::UNDER_REVIEW->value, $dispute->status);
        $this->assertNotNull($dispute->investigation_started_at);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_request_investor_response()
    {
        $dispute = Dispute::factory()->create([
            'user_id' => $this->investor->id,
            'status' => DisputeStatus::UNDER_REVIEW->value,
        ]);
        DisputeSnapshot::factory()->create(['dispute_id' => $dispute->id]);

        $response = $this->actingAs($this->admin, 'sanctum')->postJson("/api/v1/admin/disputes/{$dispute->id}/request-response", [
            'question' => 'Can you provide the transaction reference?',
        ]);

        $response->assertStatus(200);

        $dispute->refresh();
        $this->assertEquals(DisputeStatus::AWAITING_INVESTOR->value, $dispute->status);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function investor_can_respond_to_request()
    {
        $dispute = Dispute::factory()->create([
            'user_id' => $this->investor->id,
            'status' => DisputeStatus::AWAITING_INVESTOR->value,
        ]);
        DisputeSnapshot::factory()->create(['dispute_id' => $dispute->id]);

        $response = $this->actingAs($this->investor)->postJson("/api/v1/user/disputes/{$dispute->id}/respond", [
            'response' => 'Here is the transaction reference: TXN123456',
        ]);

        $response->assertStatus(200);

        $dispute->refresh();
        $this->assertEquals(DisputeStatus::UNDER_REVIEW->value, $dispute->status);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_resolve_dispute_approved()
    {
        $dispute = Dispute::factory()->create([
            'user_id' => $this->investor->id,
            'status' => DisputeStatus::UNDER_REVIEW->value,
        ]);
        DisputeSnapshot::factory()->create(['dispute_id' => $dispute->id]);

        $response = $this->actingAs($this->admin, 'sanctum')->postJson("/api/v1/admin/disputes/{$dispute->id}/resolve", [
            'outcome' => 'approved',
            'resolution' => 'Investigation confirmed the issue. Refund approved.',
            'settlement_action' => 'none',
        ]);

        $response->assertStatus(200);

        $dispute->refresh();
        $this->assertEquals(DisputeStatus::RESOLVED_APPROVED->value, $dispute->status);
        $this->assertNotNull($dispute->resolved_at);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_resolve_dispute_rejected()
    {
        $dispute = Dispute::factory()->create([
            'user_id' => $this->investor->id,
            'status' => DisputeStatus::UNDER_REVIEW->value,
        ]);
        DisputeSnapshot::factory()->create(['dispute_id' => $dispute->id]);

        $response = $this->actingAs($this->admin, 'sanctum')->postJson("/api/v1/admin/disputes/{$dispute->id}/resolve", [
            'outcome' => 'rejected',
            'resolution' => 'Investigation found no merit to the claim.',
        ]);

        $response->assertStatus(200);

        $dispute->refresh();
        $this->assertEquals(DisputeStatus::RESOLVED_REJECTED->value, $dispute->status);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function investor_can_appeal_rejected_resolution()
    {
        $dispute = Dispute::factory()->create([
            'user_id' => $this->investor->id,
            'status' => DisputeStatus::RESOLVED_REJECTED->value,
        ]);
        DisputeSnapshot::factory()->create(['dispute_id' => $dispute->id]);

        $response = $this->actingAs($this->investor)->postJson("/api/v1/user/disputes/{$dispute->id}/appeal", [
            'reason' => 'I have new evidence to submit',
        ]);

        $response->assertStatus(200);

        $dispute->refresh();
        $this->assertEquals(DisputeStatus::ESCALATED->value, $dispute->status);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_close_resolved_dispute()
    {
        $dispute = Dispute::factory()->create([
            'user_id' => $this->investor->id,
            'status' => DisputeStatus::RESOLVED_APPROVED->value,
        ]);
        DisputeSnapshot::factory()->create(['dispute_id' => $dispute->id]);

        $response = $this->actingAs($this->admin, 'sanctum')->postJson("/api/v1/admin/disputes/{$dispute->id}/close", [
            'notes' => 'All actions completed',
        ]);

        $response->assertStatus(200);

        $dispute->refresh();
        $this->assertEquals(DisputeStatus::CLOSED->value, $dispute->status);
        $this->assertNotNull($dispute->closed_at);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function investor_can_withdraw_dispute()
    {
        $dispute = Dispute::factory()->create([
            'user_id' => $this->investor->id,
            'status' => DisputeStatus::AWAITING_INVESTOR->value,
        ]);
        DisputeSnapshot::factory()->create(['dispute_id' => $dispute->id]);

        $response = $this->actingAs($this->investor)->postJson("/api/v1/user/disputes/{$dispute->id}/withdraw", [
            'reason' => 'Issue resolved on my end',
        ]);

        $response->assertStatus(200);

        $dispute->refresh();
        $this->assertEquals(DisputeStatus::CLOSED->value, $dispute->status);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function investor_can_add_comment()
    {
        $dispute = Dispute::factory()->create([
            'user_id' => $this->investor->id,
            'status' => DisputeStatus::UNDER_REVIEW->value,
        ]);
        DisputeSnapshot::factory()->create(['dispute_id' => $dispute->id]);

        $response = $this->actingAs($this->investor)->postJson("/api/v1/user/disputes/{$dispute->id}/comment", [
            'comment' => 'Additional information about my issue',
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('dispute_timelines', [
            'dispute_id' => $dispute->id,
            'event_type' => DisputeTimeline::EVENT_COMMENT,
            'actor_user_id' => $this->investor->id,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_add_internal_note()
    {
        $dispute = Dispute::factory()->create([
            'user_id' => $this->investor->id,
            'status' => DisputeStatus::UNDER_REVIEW->value,
        ]);
        DisputeSnapshot::factory()->create(['dispute_id' => $dispute->id]);

        $response = $this->actingAs($this->admin, 'sanctum')->postJson("/api/v1/admin/disputes/{$dispute->id}/comment", [
            'comment' => 'Internal: User has history of similar complaints',
            'is_internal' => true,
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('dispute_timelines', [
            'dispute_id' => $dispute->id,
            'is_internal_note' => true,
            'visible_to_investor' => false,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function investor_cannot_see_internal_notes()
    {
        $dispute = Dispute::factory()->create([
            'user_id' => $this->investor->id,
            'status' => DisputeStatus::UNDER_REVIEW->value,
        ]);
        DisputeSnapshot::factory()->create(['dispute_id' => $dispute->id]);

        // Admin adds internal note
        DisputeTimeline::create([
            'dispute_id' => $dispute->id,
            'event_type' => DisputeTimeline::EVENT_COMMENT,
            'actor_user_id' => $this->admin->id,
            'actor_role' => 'admin',
            'title' => 'Internal note',
            'description' => 'Secret admin note',
            'is_internal_note' => true,
            'visible_to_investor' => false,
        ]);

        $response = $this->actingAs($this->investor)->getJson("/api/v1/user/disputes/{$dispute->id}");

        $response->assertStatus(200);

        // Internal notes should not be in the timeline
        $timeline = $response->json('data.timeline');
        foreach ($timeline as $entry) {
            $this->assertNotEquals('Secret admin note', $entry['description']);
        }
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function investor_can_view_their_disputes()
    {
        Dispute::factory()->count(3)->create([
            'user_id' => $this->investor->id,
        ]);

        // Another user's dispute
        Dispute::factory()->create([
            'user_id' => User::factory()->create()->id,
        ]);

        $response = $this->actingAs($this->investor)->getJson('/api/v1/user/disputes');

        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function fraud_dispute_auto_escalates()
    {
        $response = $this->actingAs($this->investor)->postJson('/api/v1/user/disputes', [
            'type' => DisputeType::D_FRAUD->value,
            'title' => 'Unauthorized transaction',
            'description' => 'I did not authorize this transaction',
        ]);

        $response->assertStatus(201);

        $dispute = Dispute::where('user_id', $this->investor->id)->first();
        $this->assertEquals(DisputeStatus::ESCALATED->value, $dispute->status);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function invalid_disputable_type_combination_fails()
    {
        $response = $this->actingAs($this->investor)->postJson('/api/v1/user/disputes', [
            'type' => DisputeType::C_ALLOCATION->value, // Allocation disputes
            'disputable_type' => 'Payment', // But attached to Payment (invalid)
            'disputable_id' => $this->payment->id,
            'title' => 'Invalid combination',
            'description' => 'This should fail validation',
        ]);

        // Should fail because allocation disputes cannot be attached to payments
        $response->assertStatus(422);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_escalate_dispute()
    {
        $dispute = Dispute::factory()->create([
            'user_id' => $this->investor->id,
            'status' => DisputeStatus::UNDER_REVIEW->value,
            'risk_score' => 2,
        ]);
        DisputeSnapshot::factory()->create(['dispute_id' => $dispute->id]);

        $response = $this->actingAs($this->admin, 'sanctum')->postJson("/api/v1/admin/disputes/{$dispute->id}/escalate", [
            'reason' => 'Needs senior review',
        ]);

        $response->assertStatus(200);

        $dispute->refresh();
        $this->assertEquals(DisputeStatus::ESCALATED->value, $dispute->status);
        $this->assertEquals(3, $dispute->risk_score); // Increased
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_verify_snapshot_integrity()
    {
        $dispute = Dispute::factory()->create([
            'user_id' => $this->investor->id,
        ]);
        DisputeSnapshot::factory()->create(['dispute_id' => $dispute->id]);

        $response = $this->actingAs($this->admin, 'sanctum')->getJson("/api/v1/admin/disputes/{$dispute->id}/verify-integrity");

        $response->assertStatus(200);
        $response->assertJsonPath('integrity.valid', true);
    }
}
