<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Enums\DisputeType;
use App\Models\Dispute;
use App\Models\DisputeSnapshot;
use App\Models\DisputeTimeline;
use App\Models\User;
use App\Services\DisputeSettlementOrchestrator;
use App\Services\WalletService;
use App\Services\DoubleEntryLedgerService;
use App\Services\SnapshotIntegrityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class DisputeSettlementOrchestratorTest extends TestCase
{
    use RefreshDatabase;

    private DisputeSettlementOrchestrator $orchestrator;
    private $walletServiceMock;
    private $ledgerServiceMock;
    private $integrityServiceMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->walletServiceMock = Mockery::mock(WalletService::class);
        $this->ledgerServiceMock = Mockery::mock(DoubleEntryLedgerService::class);
        $this->integrityServiceMock = Mockery::mock(SnapshotIntegrityService::class);

        $this->orchestrator = new DisputeSettlementOrchestrator(
            $this->walletServiceMock,
            $this->ledgerServiceMock,
            $this->integrityServiceMock
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function execute_settlement_requires_resolved_approved_status()
    {
        $user = User::factory()->create();
        $dispute = Dispute::factory()->create([
            'user_id' => $user->id,
            'status' => Dispute::STATUS_OPEN, // Wrong status
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Settlement can only be executed for RESOLVED_APPROVED disputes');

        $this->orchestrator->executeSettlement($dispute, Dispute::SETTLEMENT_REFUND, 10000);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function execute_settlement_validates_integrity()
    {
        $user = User::factory()->create();
        $dispute = Dispute::factory()->create([
            'user_id' => $user->id,
            'status' => Dispute::STATUS_RESOLVED_APPROVED,
        ]);

        $this->integrityServiceMock
            ->shouldReceive('assertResolutionAllowed')
            ->once()
            ->with($dispute)
            ->andThrow(new \RuntimeException('Integrity check failed'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Integrity check failed');

        $this->orchestrator->executeSettlement($dispute, Dispute::SETTLEMENT_REFUND, 10000);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function execute_settlement_rejects_invalid_action()
    {
        $user = User::factory()->create();
        $dispute = Dispute::factory()->create([
            'user_id' => $user->id,
            'status' => Dispute::STATUS_RESOLVED_APPROVED,
        ]);

        $this->integrityServiceMock
            ->shouldReceive('assertResolutionAllowed')
            ->once();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid settlement action');

        $this->orchestrator->executeSettlement($dispute, 'invalid_action', 10000);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function execute_refund_credits_wallet_and_creates_ledger_entry()
    {
        $user = User::factory()->create();
        $dispute = Dispute::factory()->create([
            'user_id' => $user->id,
            'status' => Dispute::STATUS_RESOLVED_APPROVED,
        ]);

        // Create snapshot for the dispute
        $snapshot = DisputeSnapshot::create([
            'dispute_id' => $dispute->id,
            'disputable_snapshot' => [],
            'wallet_snapshot' => [],
            'related_transactions_snapshot' => [],
            'system_state_snapshot' => [],
            'capture_trigger' => DisputeSnapshot::TRIGGER_DISPUTE_FILED,
        ]);

        $this->integrityServiceMock
            ->shouldReceive('assertResolutionAllowed')
            ->once();

        $this->walletServiceMock
            ->shouldReceive('deposit')
            ->once(); // deposit(User, amount, TransactionType, description, reference)

        $this->ledgerServiceMock
            ->shouldReceive('recordDisputeSettlement')
            ->once()
            ->with($dispute->id, 100.00, 'refund', Mockery::type('string'));

        $result = $this->orchestrator->executeSettlement($dispute, Dispute::SETTLEMENT_REFUND, 10000);

        $this->assertEquals('refund', $result['type']);
        $this->assertEquals(10000, $result['amount_paise']);
        $this->assertEquals(100.00, $result['amount_rupees']);
        $this->assertTrue($result['wallet_credited']);
        $this->assertTrue($result['ledger_entry_created']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function execute_refund_requires_positive_amount()
    {
        $user = User::factory()->create();
        $dispute = Dispute::factory()->create([
            'user_id' => $user->id,
            'status' => Dispute::STATUS_RESOLVED_APPROVED,
        ]);

        $this->integrityServiceMock
            ->shouldReceive('assertResolutionAllowed')
            ->once();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Refund amount must be positive');

        $this->orchestrator->executeSettlement($dispute, Dispute::SETTLEMENT_REFUND, 0);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function execute_credit_issues_goodwill_credit()
    {
        $user = User::factory()->create();
        $dispute = Dispute::factory()->create([
            'user_id' => $user->id,
            'status' => Dispute::STATUS_RESOLVED_APPROVED,
        ]);

        $this->integrityServiceMock
            ->shouldReceive('assertResolutionAllowed')
            ->once();

        $this->walletServiceMock
            ->shouldReceive('deposit')
            ->once(); // deposit(User, amount, TransactionType, description, reference)

        $this->ledgerServiceMock
            ->shouldReceive('recordDisputeSettlement')
            ->once()
            ->with($dispute->id, 50.00, 'goodwill_credit', Mockery::type('string'));

        $result = $this->orchestrator->executeSettlement($dispute, Dispute::SETTLEMENT_CREDIT, 5000);

        $this->assertEquals('credit', $result['type']);
        $this->assertEquals('goodwill', $result['credit_type']);
        $this->assertEquals(5000, $result['amount_paise']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function execute_allocation_correction_requires_correction_type()
    {
        $user = User::factory()->create();
        $dispute = Dispute::factory()->create([
            'user_id' => $user->id,
            'status' => Dispute::STATUS_RESOLVED_APPROVED,
        ]);

        $this->integrityServiceMock
            ->shouldReceive('assertResolutionAllowed')
            ->once();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('correction_type');

        $this->orchestrator->executeSettlement(
            $dispute,
            Dispute::SETTLEMENT_ALLOCATION_CORRECTION,
            null,
            [] // Missing correction_type
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function execute_allocation_correction_add_units_requires_units_and_product()
    {
        $user = User::factory()->create();
        $dispute = Dispute::factory()->create([
            'user_id' => $user->id,
            'status' => Dispute::STATUS_RESOLVED_APPROVED,
        ]);

        $this->integrityServiceMock
            ->shouldReceive('assertResolutionAllowed')
            ->once();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('units and product_id');

        $this->orchestrator->executeSettlement(
            $dispute,
            Dispute::SETTLEMENT_ALLOCATION_CORRECTION,
            null,
            ['correction_type' => 'add_units']
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function execute_allocation_correction_records_pending_status()
    {
        $user = User::factory()->create();
        $dispute = Dispute::factory()->create([
            'user_id' => $user->id,
            'status' => Dispute::STATUS_RESOLVED_APPROVED,
        ]);

        $this->integrityServiceMock
            ->shouldReceive('assertResolutionAllowed')
            ->once();

        $result = $this->orchestrator->executeSettlement(
            $dispute,
            Dispute::SETTLEMENT_ALLOCATION_CORRECTION,
            null,
            [
                'correction_type' => 'add_units',
                'units' => 10,
                'product_id' => 123,
            ]
        );

        $this->assertEquals('allocation_correction', $result['type']);
        $this->assertEquals('add_units', $result['correction_type']);
        $this->assertEquals(10, $result['units_to_add']);
        $this->assertEquals(123, $result['product_id']);
        $this->assertEquals('pending_manual_processing', $result['status']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function execute_no_action_returns_informational_result()
    {
        $user = User::factory()->create();
        $dispute = Dispute::factory()->create([
            'user_id' => $user->id,
            'status' => Dispute::STATUS_RESOLVED_APPROVED,
        ]);

        $this->integrityServiceMock
            ->shouldReceive('assertResolutionAllowed')
            ->once();

        $result = $this->orchestrator->executeSettlement(
            $dispute,
            Dispute::SETTLEMENT_NONE,
            null,
            ['reason' => 'Customer educated on correct usage']
        );

        $this->assertEquals('none', $result['type']);
        $this->assertEquals('Customer educated on correct usage', $result['reason']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function execute_settlement_creates_timeline_entry()
    {
        $user = User::factory()->create();
        $dispute = Dispute::factory()->create([
            'user_id' => $user->id,
            'status' => Dispute::STATUS_RESOLVED_APPROVED,
        ]);

        $this->integrityServiceMock
            ->shouldReceive('assertResolutionAllowed')
            ->once();

        $this->walletServiceMock
            ->shouldReceive('deposit')
            ->once();

        $this->ledgerServiceMock
            ->shouldReceive('recordDisputeSettlement')
            ->once();

        $initialTimelineCount = DisputeTimeline::where('dispute_id', $dispute->id)->count();

        $this->orchestrator->executeSettlement($dispute, Dispute::SETTLEMENT_REFUND, 5000);

        $newTimelineCount = DisputeTimeline::where('dispute_id', $dispute->id)->count();
        $this->assertEquals($initialTimelineCount + 1, $newTimelineCount);

        $timelineEntry = DisputeTimeline::where('dispute_id', $dispute->id)->latest('id')->first();
        $this->assertEquals(DisputeTimeline::EVENT_SETTLEMENT, $timelineEntry->event_type);
        $this->assertTrue($timelineEntry->visible_to_investor);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function execute_settlement_updates_dispute_with_settlement_info()
    {
        $user = User::factory()->create();
        $dispute = Dispute::factory()->create([
            'user_id' => $user->id,
            'status' => Dispute::STATUS_RESOLVED_APPROVED,
        ]);

        $this->integrityServiceMock
            ->shouldReceive('assertResolutionAllowed')
            ->once();

        $this->walletServiceMock
            ->shouldReceive('deposit')
            ->once();

        $this->ledgerServiceMock
            ->shouldReceive('recordDisputeSettlement')
            ->once();

        $this->orchestrator->executeSettlement($dispute, Dispute::SETTLEMENT_REFUND, 7500);

        $dispute->refresh();

        $this->assertEquals(Dispute::SETTLEMENT_REFUND, $dispute->settlement_action);
        $this->assertEquals(7500, $dispute->settlement_amount_paise);
        $this->assertIsArray($dispute->settlement_details);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function get_recommended_action_returns_none_for_confusion()
    {
        $user = User::factory()->create();
        $dispute = Dispute::factory()->create([
            'user_id' => $user->id,
            'type' => 'confusion',
        ]);

        $recommendation = $this->orchestrator->getRecommendedAction($dispute);

        $this->assertEquals(Dispute::SETTLEMENT_NONE, $recommendation['action']);
        $this->assertStringContainsString('explanation only', $recommendation['reason']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function get_recommended_action_returns_refund_for_payment()
    {
        $user = User::factory()->create();
        $dispute = Dispute::factory()->create([
            'user_id' => $user->id,
            'type' => 'payment',
        ]);

        $recommendation = $this->orchestrator->getRecommendedAction($dispute);

        $this->assertEquals(Dispute::SETTLEMENT_REFUND, $recommendation['action']);
        $this->assertArrayHasKey('suggested_amount', $recommendation);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function get_recommended_action_returns_allocation_correction_for_allocation()
    {
        $user = User::factory()->create();
        $dispute = Dispute::factory()->create([
            'user_id' => $user->id,
            'type' => 'allocation',
        ]);

        $recommendation = $this->orchestrator->getRecommendedAction($dispute);

        $this->assertEquals(Dispute::SETTLEMENT_ALLOCATION_CORRECTION, $recommendation['action']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function get_recommended_action_returns_refund_with_note_for_fraud()
    {
        $user = User::factory()->create();
        $dispute = Dispute::factory()->create([
            'user_id' => $user->id,
            'type' => 'fraud',
        ]);

        $recommendation = $this->orchestrator->getRecommendedAction($dispute);

        $this->assertEquals(Dispute::SETTLEMENT_REFUND, $recommendation['action']);
        $this->assertArrayHasKey('note', $recommendation);
        $this->assertStringContainsString('fraud', strtolower($recommendation['note']));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function get_recommended_action_handles_unknown_type()
    {
        $user = User::factory()->create();
        // Use an invalid type value that won't match any DisputeType enum
        $dispute = Dispute::factory()->create([
            'user_id' => $user->id,
            'type' => 'nonexistent_invalid_type',
        ]);

        $recommendation = $this->orchestrator->getRecommendedAction($dispute);

        $this->assertEquals(Dispute::SETTLEMENT_NONE, $recommendation['action']);
        $this->assertStringContainsString('Unknown', $recommendation['reason']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function settlement_actions_are_defined()
    {
        $actions = Dispute::getSettlementActions();

        $this->assertContains(Dispute::SETTLEMENT_REFUND, $actions);
        $this->assertContains(Dispute::SETTLEMENT_CREDIT, $actions);
        $this->assertContains(Dispute::SETTLEMENT_ALLOCATION_CORRECTION, $actions);
        $this->assertContains(Dispute::SETTLEMENT_NONE, $actions);
        $this->assertCount(4, $actions);
    }
}
