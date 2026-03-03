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

        return DB::transaction(function () use ($dispute, $action, $amountPaise, $details, $executor) {
            $result = match ($action) {
                Dispute::SETTLEMENT_REFUND => $this->executeRefund($dispute, $amountPaise, $details),
                Dispute::SETTLEMENT_CREDIT => $this->executeCredit($dispute, $amountPaise, $details),
                Dispute::SETTLEMENT_ALLOCATION_CORRECTION => $this->executeAllocationCorrection($dispute, $details),
                Dispute::SETTLEMENT_NONE => $this->executeNoAction($dispute, $details),
            };

            // Update dispute with settlement info
            $dispute->update([
                'settlement_action' => $action,
                'settlement_amount_paise' => $amountPaise,
                'settlement_details' => array_merge($details, ['result' => $result]),
            ]);

            // Create timeline entry
            $this->createSettlementTimeline($dispute, $action, $amountPaise, $result, $executor);

            Log::channel('financial_contract')->info('Dispute settlement executed', [
                'dispute_id' => $dispute->id,
                'action' => $action,
                'amount_paise' => $amountPaise,
                'result' => $result,
                'executor_id' => $executor?->id,
            ]);

            return $result;
        });
    }

    /**
     * Execute refund settlement - credit wallet with disputed amount.
     */
    private function executeRefund(Dispute $dispute, ?int $amountPaise, array $details): array
    {
        if (!$amountPaise || $amountPaise <= 0) {
            throw new \InvalidArgumentException('Refund amount must be positive');
        }

        $user = $dispute->user;
        if (!$user) {
            throw new \RuntimeException('No user associated with dispute for refund');
        }

        // Credit wallet with refund
        $this->walletService->deposit(
            $user,
            $amountPaise / 100, // Convert to rupees
            TransactionType::REFUND,
            "Dispute #{$dispute->id} settlement refund",
            $dispute
        );

        // Create ledger entry for refund
        $this->ledgerService->recordDisputeSettlement(
            $dispute->id,
            $amountPaise / 100,
            'refund',
            "Settlement refund for dispute #{$dispute->id}"
        );

        return [
            'type' => 'refund',
            'amount_paise' => $amountPaise,
            'amount_rupees' => $amountPaise / 100,
            'user_id' => $user->id,
            'wallet_credited' => true,
            'ledger_entry_created' => true,
        ];
    }

    /**
     * Execute credit settlement - add goodwill/compensation credit.
     */
    private function executeCredit(Dispute $dispute, ?int $amountPaise, array $details): array
    {
        if (!$amountPaise || $amountPaise <= 0) {
            throw new \InvalidArgumentException('Credit amount must be positive');
        }

        $user = $dispute->user;
        if (!$user) {
            throw new \RuntimeException('No user associated with dispute for credit');
        }

        // Credit wallet with goodwill bonus
        $this->walletService->deposit(
            $user,
            $amountPaise / 100,
            TransactionType::BONUS_CREDIT,
            "Dispute #{$dispute->id} goodwill credit",
            $dispute
        );

        // Create ledger entry
        $this->ledgerService->recordDisputeSettlement(
            $dispute->id,
            $amountPaise / 100,
            'goodwill_credit',
            "Goodwill credit for dispute #{$dispute->id}"
        );

        return [
            'type' => 'credit',
            'amount_paise' => $amountPaise,
            'amount_rupees' => $amountPaise / 100,
            'user_id' => $user->id,
            'wallet_credited' => true,
            'ledger_entry_created' => true,
            'credit_type' => 'goodwill',
        ];
    }

    /**
     * Execute allocation correction - fix share allocation discrepancy.
     */
    private function executeAllocationCorrection(Dispute $dispute, array $details): array
    {
        // Allocation corrections require specific details
        if (empty($details['correction_type'])) {
            throw new \InvalidArgumentException(
                'Allocation correction requires correction_type in details'
            );
        }

        $correctionType = $details['correction_type'];
        $result = [
            'type' => 'allocation_correction',
            'correction_type' => $correctionType,
        ];

        switch ($correctionType) {
            case 'add_units':
                if (empty($details['units']) || empty($details['product_id'])) {
                    throw new \InvalidArgumentException(
                        'add_units correction requires units and product_id'
                    );
                }
                // This would integrate with AllocationService
                // For now, record the intent
                $result['units_to_add'] = $details['units'];
                $result['product_id'] = $details['product_id'];
                $result['status'] = 'pending_manual_processing';
                break;

            case 'price_adjustment':
                if (empty($details['investment_id']) || empty($details['new_price'])) {
                    throw new \InvalidArgumentException(
                        'price_adjustment correction requires investment_id and new_price'
                    );
                }
                $result['investment_id'] = $details['investment_id'];
                $result['new_price'] = $details['new_price'];
                $result['status'] = 'pending_manual_processing';
                break;

            default:
                $result['status'] = 'unknown_correction_type';
        }

        // Note: Actual allocation corrections should be executed through proper allocation services
        // This records the settlement intent and requires manual follow-up

        return $result;
    }

    /**
     * Execute no-action settlement (informational resolution).
     */
    private function executeNoAction(Dispute $dispute, array $details): array
    {
        return [
            'type' => 'none',
            'reason' => $details['reason'] ?? 'Resolved without financial action',
            'notes' => $details['notes'] ?? null,
        ];
    }

    /**
     * Create timeline entry for settlement.
     */
    private function createSettlementTimeline(
        Dispute $dispute,
        string $action,
        ?int $amountPaise,
        array $result,
        ?User $executor
    ): void {
        $title = match ($action) {
            Dispute::SETTLEMENT_REFUND => 'Refund processed',
            Dispute::SETTLEMENT_CREDIT => 'Goodwill credit issued',
            Dispute::SETTLEMENT_ALLOCATION_CORRECTION => 'Allocation correction initiated',
            Dispute::SETTLEMENT_NONE => 'Resolved without settlement',
        };

        $description = match ($action) {
            Dispute::SETTLEMENT_REFUND => sprintf(
                'Refund of ₹%.2f credited to wallet',
                ($amountPaise ?? 0) / 100
            ),
            Dispute::SETTLEMENT_CREDIT => sprintf(
                'Goodwill credit of ₹%.2f issued',
                ($amountPaise ?? 0) / 100
            ),
            Dispute::SETTLEMENT_ALLOCATION_CORRECTION => 'Allocation correction has been initiated',
            Dispute::SETTLEMENT_NONE => 'Dispute resolved without financial settlement',
        };

        DisputeTimeline::create([
            'dispute_id' => $dispute->id,
            'event_type' => DisputeTimeline::EVENT_SETTLEMENT,
            'actor_user_id' => $executor?->id,
            'actor_role' => $executor ? DisputeTimeline::ROLE_ADMIN : DisputeTimeline::ROLE_SYSTEM,
            'title' => $title,
            'description' => $description,
            'metadata' => [
                'action' => $action,
                'amount_paise' => $amountPaise,
                'result' => $result,
            ],
            'visible_to_investor' => true,
            'is_internal_note' => false,
        ]);
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
