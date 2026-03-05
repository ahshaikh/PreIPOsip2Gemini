<?php

namespace App\Services;

use App\Enums\DisputeType;
use App\Enums\TransactionType;
use App\Models\Dispute;
use App\Models\DisputeTimeline;
use App\Models\User;
use App\Models\Payment;
use App\Models\Investment;
use App\Models\Withdrawal;
use App\Models\BonusTransaction;
use App\Models\Allocation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * DisputeSettlementOrchestrator - Routes settlement by type through appropriate services
 *
 * When a dispute is resolved in favor of the investor (RESOLVED_APPROVED),
 * this service determines and executes the appropriate settlement action:
 *
 * - REFUND: Credit wallet (for payment issues)
 * - CREDIT: Add bonus/goodwill credit
 * - ALLOCATION_CORRECTION: Fix share allocation
 * - NONE: No financial action (informational resolution)
 *
 * All settlements create ledger entries and timeline records.
 */
class DisputeSettlementOrchestrator
{
    public function __construct(
        private WalletService $walletService,
        private DoubleEntryLedgerService $ledgerService,
        private SnapshotIntegrityService $integrityService,
    ) {}

    /**
     * Execute settlement for a resolved dispute.
     *
     * @throws \RuntimeException If settlement fails or integrity check fails
     */
    public function executeSettlement(
        Dispute $dispute,
        string $action,
        ?int $amountPaise = null,
        array $details = [],
        User $executor = null
    ): array {
        // Validate dispute state
        if (!in_array($dispute->status, [Dispute::STATUS_RESOLVED_APPROVED])) {
            throw new \InvalidArgumentException(
                "Settlement can only be executed for RESOLVED_APPROVED disputes. " .
                "Current status: {$dispute->status}"
            );
        }

        // Validate integrity before settlement
        $this->integrityService->assertResolutionAllowed($dispute);

        // Validate action
        $validActions = Dispute::getSettlementActions();
        if (!in_array($action, $validActions)) {
            throw new \InvalidArgumentException(
                "Invalid settlement action: {$action}. Valid: " . implode(', ', $validActions)
            );
        }

        $orchestrator = app(\App\Services\FinancialOrchestrator::class);
        return $orchestrator->settleDispute($dispute, $action, $amountPaise, $details, $executor);
    }

    /**
     * Get recommended settlement action based on dispute type.
     */
    public function getRecommendedAction(Dispute $dispute): array
    {
        $type = $dispute->getTypeEnum();

        if (!$type) {
            return [
                'action' => Dispute::SETTLEMENT_NONE,
                'reason' => 'Unknown dispute type',
            ];
        }

        return match ($type) {
            DisputeType::A_CONFUSION => [
                'action' => Dispute::SETTLEMENT_NONE,
                'reason' => 'Confusion disputes typically resolved with explanation only',
            ],
            DisputeType::B_PAYMENT => [
                'action' => Dispute::SETTLEMENT_REFUND,
                'reason' => 'Payment issues typically require refund',
                'suggested_amount' => $this->getSuggestedRefundAmount($dispute),
            ],
            DisputeType::C_ALLOCATION => [
                'action' => Dispute::SETTLEMENT_ALLOCATION_CORRECTION,
                'reason' => 'Allocation discrepancies require correction',
            ],
            DisputeType::D_FRAUD => [
                'action' => Dispute::SETTLEMENT_REFUND,
                'reason' => 'Fraud cases typically require full refund',
                'suggested_amount' => $this->getSuggestedRefundAmount($dispute),
                'note' => 'Review required - fraud cases may have additional implications',
            ],
        };
    }

    /**
     * Get suggested refund amount based on disputed entity.
     */
    private function getSuggestedRefundAmount(Dispute $dispute): ?int
    {
        $disputable = $dispute->disputable;

        if (!$disputable) {
            return null;
        }

        if ($disputable instanceof Payment) {
            return (int) ($disputable->amount * 100); // Convert to paise
        }

        if ($disputable instanceof Investment) {
            return (int) ($disputable->value_allocated * 100);
        }

        if ($disputable instanceof Withdrawal) {
            return (int) ($disputable->amount * 100);
        }

        return null;
    }
}
