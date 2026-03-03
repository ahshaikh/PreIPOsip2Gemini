<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Dispute;
use App\Models\DisputeSnapshot;
use App\Models\User;
use App\Services\SnapshotIntegrityService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SnapshotIntegrityServiceTest extends TestCase
{
    use RefreshDatabase;

    private SnapshotIntegrityService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SnapshotIntegrityService();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function verify_returns_valid_for_untampered_snapshot()
    {
        $user = User::factory()->create();
        $dispute = Dispute::factory()->create(['user_id' => $user->id]);

        // Create snapshot - the boot method computes hash automatically
        $snapshot = DisputeSnapshot::create([
            'dispute_id' => $dispute->id,
            'disputable_snapshot' => ['amount' => 10000, 'status' => 'completed'],
            'wallet_snapshot' => ['balance' => 5000],
            'related_transactions_snapshot' => [],
            'system_state_snapshot' => ['settings' => []],
            'capture_trigger' => DisputeSnapshot::TRIGGER_DISPUTE_FILED,
        ]);

        $result = $this->service->verify($snapshot);

        $this->assertTrue($result['valid']);
        $this->assertNull($result['error']);
        $this->assertEquals($snapshot->integrity_hash, $result['stored_hash']);
        $this->assertEquals($result['stored_hash'], $result['computed_hash']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function verify_returns_invalid_when_hash_mismatch_detected()
    {
        $user = User::factory()->create();
        $dispute = Dispute::factory()->create(['user_id' => $user->id]);

        $snapshot = DisputeSnapshot::create([
            'dispute_id' => $dispute->id,
            'disputable_snapshot' => ['amount' => 10000],
            'wallet_snapshot' => ['balance' => 5000],
            'related_transactions_snapshot' => [],
            'system_state_snapshot' => [],
            'capture_trigger' => DisputeSnapshot::TRIGGER_DISPUTE_FILED,
        ]);

        // Directly manipulate hash to simulate tampering (bypassing trigger for test)
        // We'll create a mock that returns a different computed hash
        $tamperedSnapshot = $this->createMock(DisputeSnapshot::class);
        $tamperedSnapshot->method('computeIntegrityHash')->willReturn('tampered_hash_value');
        $tamperedSnapshot->integrity_hash = $snapshot->integrity_hash;
        $tamperedSnapshot->id = $snapshot->id;
        $tamperedSnapshot->dispute_id = $snapshot->dispute_id;

        $result = $this->service->verify($tamperedSnapshot);

        $this->assertFalse($result['valid']);
        $this->assertNotNull($result['error']);
        $this->assertStringContainsString('Hash mismatch', $result['error']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function verify_for_dispute_returns_valid_when_snapshot_exists()
    {
        $user = User::factory()->create();
        $dispute = Dispute::factory()->create(['user_id' => $user->id]);

        $snapshot = DisputeSnapshot::create([
            'dispute_id' => $dispute->id,
            'disputable_snapshot' => ['test' => 'data'],
            'wallet_snapshot' => [],
            'related_transactions_snapshot' => [],
            'system_state_snapshot' => [],
            'capture_trigger' => DisputeSnapshot::TRIGGER_DISPUTE_FILED,
        ]);

        $dispute->update(['snapshot_id' => $snapshot->id]);
        $dispute->load('snapshot');

        $result = $this->service->verifyForDispute($dispute);

        $this->assertTrue($result['valid']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function verify_for_dispute_returns_error_when_no_snapshot()
    {
        $user = User::factory()->create();
        $dispute = Dispute::factory()->create(['user_id' => $user->id]);
        // No snapshot created

        $result = $this->service->verifyForDispute($dispute);

        $this->assertFalse($result['valid']);
        $this->assertNull($result['computed_hash']);
        $this->assertNull($result['stored_hash']);
        $this->assertStringContainsString('No snapshot exists', $result['error']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function assert_resolution_allowed_passes_for_valid_snapshot()
    {
        $user = User::factory()->create();
        $dispute = Dispute::factory()->create(['user_id' => $user->id]);

        $snapshot = DisputeSnapshot::create([
            'dispute_id' => $dispute->id,
            'disputable_snapshot' => ['amount' => 5000],
            'wallet_snapshot' => ['balance' => 2500],
            'related_transactions_snapshot' => [],
            'system_state_snapshot' => [],
            'capture_trigger' => DisputeSnapshot::TRIGGER_DISPUTE_FILED,
        ]);

        $dispute->update(['snapshot_id' => $snapshot->id]);
        $dispute->load('snapshot');

        // Should not throw
        $this->service->assertResolutionAllowed($dispute);
        $this->assertTrue(true); // If we reach here, no exception thrown
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function assert_resolution_allowed_throws_for_invalid_snapshot()
    {
        $user = User::factory()->create();
        $dispute = Dispute::factory()->create(['user_id' => $user->id]);
        // No snapshot - should fail

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot resolve dispute');

        $this->service->assertResolutionAllowed($dispute);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function verify_all_counts_valid_and_invalid_snapshots()
    {
        $user = User::factory()->create();

        // Create disputes with snapshots
        $dispute1 = Dispute::factory()->create([
            'user_id' => $user->id,
            'disputable_type' => 'App\Models\Payment',
            'disputable_id' => 1,
        ]);

        $snapshot1 = DisputeSnapshot::create([
            'dispute_id' => $dispute1->id,
            'disputable_snapshot' => ['test' => 1],
            'wallet_snapshot' => [],
            'related_transactions_snapshot' => [],
            'system_state_snapshot' => [],
            'capture_trigger' => DisputeSnapshot::TRIGGER_DISPUTE_FILED,
        ]);
        $dispute1->update(['snapshot_id' => $snapshot1->id]);

        $dispute2 = Dispute::factory()->create([
            'user_id' => $user->id,
            'disputable_type' => 'App\Models\Payment',
            'disputable_id' => 2,
        ]);

        $snapshot2 = DisputeSnapshot::create([
            'dispute_id' => $dispute2->id,
            'disputable_snapshot' => ['test' => 2],
            'wallet_snapshot' => [],
            'related_transactions_snapshot' => [],
            'system_state_snapshot' => [],
            'capture_trigger' => DisputeSnapshot::TRIGGER_DISPUTE_FILED,
        ]);
        $dispute2->update(['snapshot_id' => $snapshot2->id]);

        // Create a dispute without snapshot (missing)
        $dispute3 = Dispute::factory()->create([
            'user_id' => $user->id,
            'disputable_type' => 'App\Models\Payment',
            'disputable_id' => 3,
        ]);

        $results = $this->service->verifyAll();

        $this->assertEquals(3, $results['total']);
        $this->assertEquals(2, $results['valid']);
        $this->assertEquals(0, $results['invalid']);
        $this->assertEquals(1, $results['missing']);
        $this->assertCount(1, $results['failures']);
        $this->assertEquals('missing', $results['failures'][0]['type']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function get_dashboard_status_returns_expected_structure()
    {
        $user = User::factory()->create();

        // Create a few disputes and snapshots
        $dispute = Dispute::factory()->create([
            'user_id' => $user->id,
            'disputable_type' => 'App\Models\Payment',
            'disputable_id' => 1,
        ]);

        $snapshot = DisputeSnapshot::create([
            'dispute_id' => $dispute->id,
            'disputable_snapshot' => ['data' => 'test'],
            'wallet_snapshot' => [],
            'related_transactions_snapshot' => [],
            'system_state_snapshot' => [],
            'capture_trigger' => DisputeSnapshot::TRIGGER_DISPUTE_FILED,
        ]);
        $dispute->update(['snapshot_id' => $snapshot->id]);

        $status = $this->service->getDashboardStatus();

        $this->assertArrayHasKey('total_disputes', $status);
        $this->assertArrayHasKey('disputes_with_snapshot', $status);
        $this->assertArrayHasKey('disputes_missing_snapshot', $status);
        $this->assertArrayHasKey('recent_snapshots_checked', $status);
        $this->assertArrayHasKey('recent_snapshots_valid', $status);
        $this->assertArrayHasKey('recent_snapshots_invalid', $status);
        $this->assertArrayHasKey('integrity_percentage', $status);
        $this->assertArrayHasKey('status', $status);

        $this->assertGreaterThanOrEqual(1, $status['total_disputes']);
        $this->assertEquals('healthy', $status['status']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function find_integrity_issues_returns_empty_when_all_valid()
    {
        $user = User::factory()->create();
        $dispute = Dispute::factory()->create(['user_id' => $user->id]);

        DisputeSnapshot::create([
            'dispute_id' => $dispute->id,
            'disputable_snapshot' => ['valid' => true],
            'wallet_snapshot' => [],
            'related_transactions_snapshot' => [],
            'system_state_snapshot' => [],
            'capture_trigger' => DisputeSnapshot::TRIGGER_DISPUTE_FILED,
        ]);

        $issues = $this->service->findIntegrityIssues();

        $this->assertCount(0, $issues);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function snapshot_hash_is_computed_on_creation()
    {
        $user = User::factory()->create();
        $dispute = Dispute::factory()->create(['user_id' => $user->id]);

        $snapshot = DisputeSnapshot::create([
            'dispute_id' => $dispute->id,
            'disputable_snapshot' => ['key' => 'value'],
            'wallet_snapshot' => ['balance' => 100],
            'related_transactions_snapshot' => [],
            'system_state_snapshot' => [],
            'capture_trigger' => DisputeSnapshot::TRIGGER_ADMIN_REQUEST,
        ]);

        $this->assertNotNull($snapshot->integrity_hash);
        $this->assertEquals(64, strlen($snapshot->integrity_hash)); // SHA256 produces 64 hex chars
        $this->assertTrue($snapshot->verifyIntegrity());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function different_data_produces_different_hash()
    {
        $user = User::factory()->create();
        $dispute1 = Dispute::factory()->create(['user_id' => $user->id]);
        $dispute2 = Dispute::factory()->create(['user_id' => $user->id]);

        $snapshot1 = DisputeSnapshot::create([
            'dispute_id' => $dispute1->id,
            'disputable_snapshot' => ['amount' => 1000],
            'wallet_snapshot' => [],
            'related_transactions_snapshot' => [],
            'system_state_snapshot' => [],
            'capture_trigger' => DisputeSnapshot::TRIGGER_DISPUTE_FILED,
        ]);

        $snapshot2 = DisputeSnapshot::create([
            'dispute_id' => $dispute2->id,
            'disputable_snapshot' => ['amount' => 2000],
            'wallet_snapshot' => [],
            'related_transactions_snapshot' => [],
            'system_state_snapshot' => [],
            'capture_trigger' => DisputeSnapshot::TRIGGER_DISPUTE_FILED,
        ]);

        $this->assertNotEquals($snapshot1->integrity_hash, $snapshot2->integrity_hash);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function capture_triggers_are_defined()
    {
        $triggers = DisputeSnapshot::getCaptureTriggers();

        $this->assertContains(DisputeSnapshot::TRIGGER_DISPUTE_FILED, $triggers);
        $this->assertContains(DisputeSnapshot::TRIGGER_ADMIN_REQUEST, $triggers);
        $this->assertContains(DisputeSnapshot::TRIGGER_AUTO_ESCALATION, $triggers);
        $this->assertCount(3, $triggers);
    }
}
