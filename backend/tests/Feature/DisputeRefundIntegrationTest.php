<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Dispute;
use App\Models\DisputeSnapshot;
use App\Models\DisputeTimeline;
use App\Models\User;
use App\Models\Payment;
use App\Services\DisputeSettlementOrchestrator;
use App\Services\WalletService;
use App\Services\DoubleEntryLedgerService;
use App\Services\SnapshotIntegrityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Mockery;

class DisputeRefundIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $investor;

    protected function setUp(): void
    {
        parent::setUp();

        // Create admin role with both guards to handle auth config variations
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'sanctum']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->investor = User::factory()->create();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function settlement_refund_updates_dispute_settlement_fields()
    {
        Sanctum::actingAs($this->admin, ['*']);

        $dispute = Dispute::factory()->create([
            'user_id' => $this->investor->id,
            'status' => Dispute::STATUS_RESOLVED_APPROVED,
        ]);

        // Create valid snapshot
        DisputeSnapshot::create([
            'dispute_id' => $dispute->id,
            'disputable_snapshot' => ['amount' => 5000],
            'wallet_snapshot' => ['balance' => 0],
            'related_transactions_snapshot' => [],
            'system_state_snapshot' => [],
            'capture_trigger' => DisputeSnapshot::TRIGGER_DISPUTE_FILED,
        ]);

        // Mock dependencies
        $walletServiceMock = Mockery::mock(WalletService::class);
        $walletServiceMock->shouldReceive('deposit')->once();

        $ledgerServiceMock = Mockery::mock(DoubleEntryLedgerService::class);
        $ledgerServiceMock->shouldReceive('recordDisputeSettlement')->once();

        $integrityServiceMock = Mockery::mock(SnapshotIntegrityService::class);
        $integrityServiceMock->shouldReceive('assertResolutionAllowed')->once();

        $orchestrator = new DisputeSettlementOrchestrator(
            $walletServiceMock,
            $ledgerServiceMock,
            $integrityServiceMock
        );

        $orchestrator->executeSettlement($dispute, Dispute::SETTLEMENT_REFUND, 500000, [], $this->admin);

        $dispute->refresh();

        $this->assertEquals(Dispute::SETTLEMENT_REFUND, $dispute->settlement_action);
        $this->assertEquals(500000, $dispute->settlement_amount_paise);
        $this->assertNotNull($dispute->settlement_details);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function settlement_creates_timeline_entry()
    {
        $dispute = Dispute::factory()->create([
            'user_id' => $this->investor->id,
            'status' => Dispute::STATUS_RESOLVED_APPROVED,
        ]);

        DisputeSnapshot::create([
            'dispute_id' => $dispute->id,
            'disputable_snapshot' => [],
            'wallet_snapshot' => [],
            'related_transactions_snapshot' => [],
            'system_state_snapshot' => [],
            'capture_trigger' => DisputeSnapshot::TRIGGER_DISPUTE_FILED,
        ]);

        $walletServiceMock = Mockery::mock(WalletService::class);
        $walletServiceMock->shouldReceive('deposit')->once();

        $ledgerServiceMock = Mockery::mock(DoubleEntryLedgerService::class);
        $ledgerServiceMock->shouldReceive('recordDisputeSettlement')->once();

        $integrityServiceMock = Mockery::mock(SnapshotIntegrityService::class);
        $integrityServiceMock->shouldReceive('assertResolutionAllowed')->once();

        $orchestrator = new DisputeSettlementOrchestrator(
            $walletServiceMock,
            $ledgerServiceMock,
            $integrityServiceMock
        );

        $initialCount = DisputeTimeline::where('dispute_id', $dispute->id)->count();

        $orchestrator->executeSettlement($dispute, Dispute::SETTLEMENT_REFUND, 100000, [], $this->admin);

        $newCount = DisputeTimeline::where('dispute_id', $dispute->id)->count();
        $this->assertGreaterThan($initialCount, $newCount);

        $settlementEntry = DisputeTimeline::where('dispute_id', $dispute->id)
            ->where('event_type', DisputeTimeline::EVENT_SETTLEMENT)
            ->first();

        $this->assertNotNull($settlementEntry);
        $this->assertTrue($settlementEntry->visible_to_investor);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function refund_fails_for_non_resolved_dispute()
    {
        $dispute = Dispute::factory()->create([
            'user_id' => $this->investor->id,
            'status' => Dispute::STATUS_UNDER_REVIEW, // Wrong status
        ]);

        $orchestrator = app(DisputeSettlementOrchestrator::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('RESOLVED_APPROVED');

        $orchestrator->executeSettlement($dispute, Dispute::SETTLEMENT_REFUND, 100000);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function refund_fails_for_integrity_failure()
    {
        $dispute = Dispute::factory()->create([
            'user_id' => $this->investor->id,
            'status' => Dispute::STATUS_RESOLVED_APPROVED,
        ]);

        // No snapshot = integrity failure

        $integrityServiceMock = Mockery::mock(SnapshotIntegrityService::class);
        $integrityServiceMock->shouldReceive('assertResolutionAllowed')
            ->once()
            ->andThrow(new \RuntimeException('Snapshot integrity check failed'));

        $orchestrator = new DisputeSettlementOrchestrator(
            app(WalletService::class),
            app(DoubleEntryLedgerService::class),
            $integrityServiceMock
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('integrity');

        $orchestrator->executeSettlement($dispute, Dispute::SETTLEMENT_REFUND, 100000);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function credit_settlement_marks_as_goodwill()
    {
        $dispute = Dispute::factory()->create([
            'user_id' => $this->investor->id,
            'status' => Dispute::STATUS_RESOLVED_APPROVED,
        ]);

        DisputeSnapshot::create([
            'dispute_id' => $dispute->id,
            'disputable_snapshot' => [],
            'wallet_snapshot' => [],
            'related_transactions_snapshot' => [],
            'system_state_snapshot' => [],
            'capture_trigger' => DisputeSnapshot::TRIGGER_DISPUTE_FILED,
        ]);

        $walletServiceMock = Mockery::mock(WalletService::class);
        $walletServiceMock->shouldReceive('deposit')->once();

        $ledgerServiceMock = Mockery::mock(DoubleEntryLedgerService::class);
        $ledgerServiceMock->shouldReceive('recordDisputeSettlement')
            ->once()
            ->with(
                $dispute->id,
                Mockery::type('float'),
                'goodwill_credit',
                Mockery::type('string')
            );

        $integrityServiceMock = Mockery::mock(SnapshotIntegrityService::class);
        $integrityServiceMock->shouldReceive('assertResolutionAllowed')->once();

        $orchestrator = new DisputeSettlementOrchestrator(
            $walletServiceMock,
            $ledgerServiceMock,
            $integrityServiceMock
        );

        $result = $orchestrator->executeSettlement($dispute, Dispute::SETTLEMENT_CREDIT, 25000);

        $this->assertEquals('credit', $result['type']);
        $this->assertEquals('goodwill', $result['credit_type']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function allocation_correction_returns_pending_status()
    {
        $dispute = Dispute::factory()->create([
            'user_id' => $this->investor->id,
            'status' => Dispute::STATUS_RESOLVED_APPROVED,
        ]);

        DisputeSnapshot::create([
            'dispute_id' => $dispute->id,
            'disputable_snapshot' => [],
            'wallet_snapshot' => [],
            'related_transactions_snapshot' => [],
            'system_state_snapshot' => [],
            'capture_trigger' => DisputeSnapshot::TRIGGER_DISPUTE_FILED,
        ]);

        $integrityServiceMock = Mockery::mock(SnapshotIntegrityService::class);
        $integrityServiceMock->shouldReceive('assertResolutionAllowed')->once();

        $orchestrator = new DisputeSettlementOrchestrator(
            app(WalletService::class),
            app(DoubleEntryLedgerService::class),
            $integrityServiceMock
        );

        $result = $orchestrator->executeSettlement(
            $dispute,
            Dispute::SETTLEMENT_ALLOCATION_CORRECTION,
            null,
            [
                'correction_type' => 'add_units',
                'units' => 5,
                'product_id' => 100,
            ]
        );

        $this->assertEquals('allocation_correction', $result['type']);
        $this->assertEquals('pending_manual_processing', $result['status']);
        $this->assertEquals(5, $result['units_to_add']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function settlement_none_completes_without_financial_action()
    {
        $dispute = Dispute::factory()->create([
            'user_id' => $this->investor->id,
            'status' => Dispute::STATUS_RESOLVED_APPROVED,
        ]);

        DisputeSnapshot::create([
            'dispute_id' => $dispute->id,
            'disputable_snapshot' => [],
            'wallet_snapshot' => [],
            'related_transactions_snapshot' => [],
            'system_state_snapshot' => [],
            'capture_trigger' => DisputeSnapshot::TRIGGER_DISPUTE_FILED,
        ]);

        $integrityServiceMock = Mockery::mock(SnapshotIntegrityService::class);
        $integrityServiceMock->shouldReceive('assertResolutionAllowed')->once();

        $orchestrator = new DisputeSettlementOrchestrator(
            app(WalletService::class),
            app(DoubleEntryLedgerService::class),
            $integrityServiceMock
        );

        $result = $orchestrator->executeSettlement(
            $dispute,
            Dispute::SETTLEMENT_NONE,
            null,
            ['reason' => 'Investor inquiry addressed, no error found.']
        );

        $this->assertEquals('none', $result['type']);
        $this->assertEquals('Investor inquiry addressed, no error found.', $result['reason']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function refund_requires_positive_amount()
    {
        $dispute = Dispute::factory()->create([
            'user_id' => $this->investor->id,
            'status' => Dispute::STATUS_RESOLVED_APPROVED,
        ]);

        $integrityServiceMock = Mockery::mock(SnapshotIntegrityService::class);
        $integrityServiceMock->shouldReceive('assertResolutionAllowed')->once();

        $orchestrator = new DisputeSettlementOrchestrator(
            app(WalletService::class),
            app(DoubleEntryLedgerService::class),
            $integrityServiceMock
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('positive');

        $orchestrator->executeSettlement($dispute, Dispute::SETTLEMENT_REFUND, 0);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function settlement_result_contains_amount_in_rupees()
    {
        $dispute = Dispute::factory()->create([
            'user_id' => $this->investor->id,
            'status' => Dispute::STATUS_RESOLVED_APPROVED,
        ]);

        DisputeSnapshot::create([
            'dispute_id' => $dispute->id,
            'disputable_snapshot' => [],
            'wallet_snapshot' => [],
            'related_transactions_snapshot' => [],
            'system_state_snapshot' => [],
            'capture_trigger' => DisputeSnapshot::TRIGGER_DISPUTE_FILED,
        ]);

        $walletServiceMock = Mockery::mock(WalletService::class);
        $walletServiceMock->shouldReceive('deposit')->once();

        $ledgerServiceMock = Mockery::mock(DoubleEntryLedgerService::class);
        $ledgerServiceMock->shouldReceive('recordDisputeSettlement')->once();

        $integrityServiceMock = Mockery::mock(SnapshotIntegrityService::class);
        $integrityServiceMock->shouldReceive('assertResolutionAllowed')->once();

        $orchestrator = new DisputeSettlementOrchestrator(
            $walletServiceMock,
            $ledgerServiceMock,
            $integrityServiceMock
        );

        // 12345 paise = 123.45 rupees
        $result = $orchestrator->executeSettlement($dispute, Dispute::SETTLEMENT_REFUND, 12345);

        $this->assertEquals(12345, $result['amount_paise']);
        $this->assertEquals(123.45, $result['amount_rupees']);
    }
}
