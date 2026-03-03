<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Dispute;
use App\Models\DisputeSnapshot;
use App\Models\User;
use App\Models\Payment;
use App\Models\Wallet;
use App\Services\DisputeSnapshotService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DisputeSnapshotServiceTest extends TestCase
{
    use RefreshDatabase;

    protected DisputeSnapshotService $snapshotService;
    protected User $user;
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->snapshotService = new DisputeSnapshotService();

        $this->user = User::factory()->create();
        $this->user->assignRole('user');

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_captures_snapshot_at_filing()
    {
        $payment = Payment::factory()->create(['user_id' => $this->user->id]);

        $dispute = Dispute::factory()->create([
            'user_id' => $this->user->id,
            'disputable_type' => Payment::class,
            'disputable_id' => $payment->id,
        ]);

        $snapshot = $this->snapshotService->captureAtFiling($dispute);

        $this->assertInstanceOf(DisputeSnapshot::class, $snapshot);
        $this->assertEquals($dispute->id, $snapshot->dispute_id);
        $this->assertEquals(DisputeSnapshot::TRIGGER_DISPUTE_FILED, $snapshot->capture_trigger);
        $this->assertNotNull($snapshot->integrity_hash);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_captures_disputable_state()
    {
        $payment = Payment::factory()->create([
            'user_id' => $this->user->id,
            'amount' => 5000,
            'status' => 'paid',
        ]);

        $dispute = Dispute::factory()->create([
            'user_id' => $this->user->id,
            'disputable_type' => Payment::class,
            'disputable_id' => $payment->id,
        ]);

        $snapshot = $this->snapshotService->captureAtFiling($dispute);

        $disputableSnapshot = $snapshot->disputable_snapshot;
        $this->assertEquals(Payment::class, $disputableSnapshot['type']);
        $this->assertEquals($payment->id, $disputableSnapshot['id']);
        $this->assertEquals(5000, $disputableSnapshot['data']['amount']);
        $this->assertEquals('paid', $disputableSnapshot['data']['status']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_captures_wallet_state()
    {
        // Ensure wallet exists with known balance
        $wallet = $this->user->wallet;
        $wallet->update(['balance_paise' => 100000]); // 1000 rupees

        $dispute = Dispute::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $snapshot = $this->snapshotService->captureAtFiling($dispute);

        $walletSnapshot = $snapshot->wallet_snapshot;
        $this->assertEquals($this->user->id, $walletSnapshot['user_id']);
        $this->assertEquals(100000, $walletSnapshot['balance_paise']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_prevents_duplicate_snapshots()
    {
        $dispute = Dispute::factory()->create([
            'user_id' => $this->user->id,
        ]);

        // First snapshot
        $this->snapshotService->captureAtFiling($dispute);

        // Second snapshot should fail
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('already has a snapshot');

        $this->snapshotService->captureAtFiling($dispute);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_captures_admin_request_snapshot()
    {
        $dispute = Dispute::factory()->create([
            'user_id' => $this->user->id,
        ]);

        // First capture at filing
        $this->snapshotService->captureAtFiling($dispute);

        // Admin request creates additional snapshot (but for this test, we need separate dispute)
        $dispute2 = Dispute::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $snapshot = $this->snapshotService->captureForAdminRequest($dispute2, $this->admin);

        $this->assertEquals(DisputeSnapshot::TRIGGER_ADMIN_REQUEST, $snapshot->capture_trigger);
        $this->assertEquals($this->admin->id, $snapshot->captured_by_user_id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function snapshot_integrity_hash_is_computed_correctly()
    {
        $dispute = Dispute::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $snapshot = $this->snapshotService->captureAtFiling($dispute);

        // Verify integrity
        $this->assertTrue($snapshot->verifyIntegrity());
        $this->assertEquals($snapshot->integrity_hash, $snapshot->computeIntegrityHash());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_captures_system_state()
    {
        $dispute = Dispute::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $snapshot = $this->snapshotService->captureAtFiling($dispute);

        $systemState = $snapshot->system_state_snapshot;
        $this->assertArrayHasKey('platform_version', $systemState);
        $this->assertArrayHasKey('env', $systemState);
        $this->assertArrayHasKey('captured_at', $systemState);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_handles_dispute_without_disputable()
    {
        $dispute = Dispute::factory()->create([
            'user_id' => $this->user->id,
            'disputable_type' => null,
            'disputable_id' => null,
        ]);

        $snapshot = $this->snapshotService->captureAtFiling($dispute);

        $disputableSnapshot = $snapshot->disputable_snapshot;
        $this->assertNull($disputableSnapshot['type']);
        $this->assertNull($disputableSnapshot['id']);
    }
}
