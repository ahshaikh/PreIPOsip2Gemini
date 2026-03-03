<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Dispute;
use App\Models\DisputeSnapshot;
use App\Models\DisputeTimeline;
use App\Models\User;
use App\Models\Payment;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class UserDisputeFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $investor;
    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->investor = User::factory()->create();
        $this->company = Company::factory()->create();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function investor_can_file_new_dispute()
    {
        Sanctum::actingAs($this->investor, ['*']);

        $payment = Payment::factory()->create([
            'user_id' => $this->investor->id,
        ]);

        $response = $this->postJson('/api/v1/user/disputes', [
            'title' => 'Payment not reflected in account',
            'description' => 'I made a payment but it is not showing in my wallet balance.',
            'type' => 'payment',
            'disputable_type' => 'Payment',
            'disputable_id' => $payment->id,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Dispute filed successfully.',
            ])
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'title',
                    'status',
                    'created_at',
                ],
            ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function dispute_filing_creates_snapshot()
    {
        Sanctum::actingAs($this->investor, ['*']);

        $payment = Payment::factory()->create([
            'user_id' => $this->investor->id,
        ]);

        $response = $this->postJson('/api/v1/user/disputes', [
            'title' => 'Payment issue',
            'description' => 'Payment failed but amount deducted.',
            'type' => 'payment',
            'disputable_type' => 'Payment',
            'disputable_id' => $payment->id,
        ]);

        $response->assertStatus(201);

        $disputeId = $response->json('data.id');
        $dispute = Dispute::find($disputeId);

        $this->assertNotNull($dispute->snapshot);
        $this->assertTrue($dispute->snapshot->verifyIntegrity());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function dispute_filing_creates_timeline_entry()
    {
        Sanctum::actingAs($this->investor, ['*']);

        $payment = Payment::factory()->create([
            'user_id' => $this->investor->id,
        ]);

        $response = $this->postJson('/api/v1/user/disputes', [
            'title' => 'Payment not credited',
            'description' => 'Amount deducted but not credited.',
            'type' => 'payment',
            'disputable_type' => 'Payment',
            'disputable_id' => $payment->id,
        ]);

        $response->assertStatus(201);

        $disputeId = $response->json('data.id');
        $timelineEntry = DisputeTimeline::where('dispute_id', $disputeId)
            ->where('event_type', DisputeTimeline::EVENT_CREATED)
            ->first();

        $this->assertNotNull($timelineEntry);
        $this->assertEquals(DisputeTimeline::ROLE_INVESTOR, $timelineEntry->actor_role);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function investor_can_list_their_disputes()
    {
        Sanctum::actingAs($this->investor, ['*']);

        Dispute::factory()->count(2)->create([
            'user_id' => $this->investor->id,
        ]);

        // Create dispute for another user (should not appear)
        $otherUser = User::factory()->create();
        Dispute::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->getJson('/api/v1/user/disputes');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function investor_can_view_own_dispute_detail()
    {
        Sanctum::actingAs($this->investor, ['*']);

        $dispute = Dispute::factory()->create([
            'user_id' => $this->investor->id,
            'status' => Dispute::STATUS_UNDER_REVIEW,
        ]);

        $response = $this->getJson("/api/v1/user/disputes/{$dispute->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'title',
                    'description',
                    'status',
                    'timeline',
                ],
            ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function investor_cannot_view_other_users_dispute()
    {
        Sanctum::actingAs($this->investor, ['*']);

        $otherUser = User::factory()->create();
        $dispute = Dispute::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->getJson("/api/v1/user/disputes/{$dispute->id}");

        $response->assertStatus(403);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function investor_can_add_evidence_to_own_dispute()
    {
        Sanctum::actingAs($this->investor, ['*']);

        $dispute = Dispute::factory()->create([
            'user_id' => $this->investor->id,
            'status' => Dispute::STATUS_AWAITING_INVESTOR,
        ]);

        $response = $this->postJson("/api/v1/user/disputes/{$dispute->id}/evidence", [
            'evidence' => [
                [
                    'type' => 'text',
                    'value' => 'Transaction reference: TXN12345',
                    'description' => 'Bank statement reference',
                ],
            ],
            'description' => 'Adding bank statement reference.',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Evidence added successfully.',
            ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function adding_evidence_creates_timeline_entry()
    {
        Sanctum::actingAs($this->investor, ['*']);

        $dispute = Dispute::factory()->create([
            'user_id' => $this->investor->id,
            'status' => Dispute::STATUS_AWAITING_INVESTOR,
        ]);

        $initialCount = DisputeTimeline::where('dispute_id', $dispute->id)->count();

        $this->postJson("/api/v1/user/disputes/{$dispute->id}/evidence", [
            'evidence' => [
                [
                    'type' => 'screenshot',
                    'value' => '/uploads/evidence/screenshot1.png',
                ],
            ],
        ]);

        $newCount = DisputeTimeline::where('dispute_id', $dispute->id)->count();
        $this->assertGreaterThan($initialCount, $newCount);

        $evidenceEntry = DisputeTimeline::where('dispute_id', $dispute->id)
            ->where('event_type', DisputeTimeline::EVENT_EVIDENCE_ADDED)
            ->latest('id')
            ->first();

        $this->assertNotNull($evidenceEntry);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function investor_can_add_comment_to_own_dispute()
    {
        Sanctum::actingAs($this->investor, ['*']);

        $dispute = Dispute::factory()->create([
            'user_id' => $this->investor->id,
            'status' => Dispute::STATUS_UNDER_REVIEW,
        ]);

        $response = $this->postJson("/api/v1/user/disputes/{$dispute->id}/comment", [
            'comment' => 'Any update on this dispute?',
        ]);

        $response->assertStatus(201);

        $comment = DisputeTimeline::where('dispute_id', $dispute->id)
            ->where('event_type', DisputeTimeline::EVENT_COMMENT)
            ->where('actor_role', DisputeTimeline::ROLE_INVESTOR)
            ->latest('id')
            ->first();

        $this->assertNotNull($comment);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function investor_sees_only_visible_timeline_entries()
    {
        Sanctum::actingAs($this->investor, ['*']);

        $dispute = Dispute::factory()->create([
            'user_id' => $this->investor->id,
        ]);

        // Public entry
        DisputeTimeline::create([
            'dispute_id' => $dispute->id,
            'event_type' => DisputeTimeline::EVENT_STATUS_CHANGE,
            'actor_role' => DisputeTimeline::ROLE_ADMIN,
            'title' => 'Status updated',
            'visible_to_investor' => true,
            'is_internal_note' => false,
        ]);

        // Internal note (should not be visible)
        DisputeTimeline::create([
            'dispute_id' => $dispute->id,
            'event_type' => DisputeTimeline::EVENT_COMMENT,
            'actor_role' => DisputeTimeline::ROLE_ADMIN,
            'title' => 'Internal review note',
            'visible_to_investor' => false,
            'is_internal_note' => true,
        ]);

        $response = $this->getJson("/api/v1/user/disputes/{$dispute->id}");

        $response->assertStatus(200);

        // Check timeline count - should only include visible entries
        $timeline = $response->json('data.timeline');
        foreach ($timeline as $entry) {
            $this->assertTrue($entry['visible_to_investor'] ?? true);
            $this->assertFalse($entry['is_internal_note'] ?? false);
        }
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function dispute_requires_title()
    {
        Sanctum::actingAs($this->investor, ['*']);

        $response = $this->postJson('/api/v1/user/disputes', [
            'description' => 'Missing title',
            'type' => 'payment',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function dispute_requires_description()
    {
        Sanctum::actingAs($this->investor, ['*']);

        $response = $this->postJson('/api/v1/user/disputes', [
            'title' => 'Missing description',
            'type' => 'payment',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['description']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function dispute_requires_valid_type()
    {
        Sanctum::actingAs($this->investor, ['*']);

        $response = $this->postJson('/api/v1/user/disputes', [
            'title' => 'Test dispute',
            'description' => 'Test description',
            'type' => 'invalid_type',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['type']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function cannot_file_duplicate_dispute_for_same_disputable()
    {
        Sanctum::actingAs($this->investor, ['*']);

        $payment = Payment::factory()->create([
            'user_id' => $this->investor->id,
        ]);

        // File first dispute
        Dispute::factory()->create([
            'user_id' => $this->investor->id,
            'disputable_type' => Payment::class,
            'disputable_id' => $payment->id,
            'status' => Dispute::STATUS_OPEN,
        ]);

        // Try to file second dispute for same payment
        $response = $this->postJson('/api/v1/user/disputes', [
            'title' => 'Duplicate dispute',
            'description' => 'Another dispute for same payment.',
            'type' => 'payment',
            'disputable_type' => 'Payment',
            'disputable_id' => $payment->id,
        ]);

        $response->assertStatus(422);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function new_dispute_status_is_open()
    {
        Sanctum::actingAs($this->investor, ['*']);

        $payment = Payment::factory()->create([
            'user_id' => $this->investor->id,
        ]);

        $response = $this->postJson('/api/v1/user/disputes', [
            'title' => 'New dispute',
            'description' => 'Testing initial status.',
            'type' => 'payment',
            'disputable_type' => 'Payment',
            'disputable_id' => $payment->id,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', Dispute::STATUS_OPEN);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function unauthenticated_user_cannot_file_dispute()
    {
        $response = $this->postJson('/api/v1/user/disputes', [
            'title' => 'Test',
            'description' => 'Test',
            'type' => 'payment',
        ]);

        $response->assertStatus(401);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function evidence_requires_type_and_value()
    {
        Sanctum::actingAs($this->investor, ['*']);

        $dispute = Dispute::factory()->create([
            'user_id' => $this->investor->id,
            'status' => Dispute::STATUS_AWAITING_INVESTOR,
        ]);

        $response = $this->postJson("/api/v1/user/disputes/{$dispute->id}/evidence", [
            'evidence' => [
                [
                    'description' => 'Missing type and value',
                ],
            ],
        ]);

        $response->assertStatus(422);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function evidence_type_must_be_valid()
    {
        Sanctum::actingAs($this->investor, ['*']);

        $dispute = Dispute::factory()->create([
            'user_id' => $this->investor->id,
            'status' => Dispute::STATUS_AWAITING_INVESTOR,
        ]);

        $response = $this->postJson("/api/v1/user/disputes/{$dispute->id}/evidence", [
            'evidence' => [
                [
                    'type' => 'invalid_type',
                    'value' => 'some value',
                ],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['evidence.0.type']);
    }
}
