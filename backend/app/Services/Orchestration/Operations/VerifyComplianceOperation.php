<?php

namespace App\Services\Orchestration\Operations;

use App\Models\User;
use App\Services\ComplianceGateService;
use App\Services\Orchestration\Saga\SagaContext;
use Illuminate\Support\Facades\Log;

/**
 * VerifyComplianceOperation
 *
 * [C.8 + C.9]: Compliance gate at orchestration level
 *
 * PROTOCOL ENFORCEMENT:
 * - "Compliance must gate money BEFORE, not after"
 * - This operation executes FIRST in every financial saga
 * - If KYC incomplete → Saga aborts before ANY state changes
 * - NO compensation needed (nothing happened yet)
 *
 * FAILURE SEMANTICS:
 * - Returns OperationResult::failure() if compliance blocked
 * - Saga coordinator aborts entire flow
 * - No wallet credits, no admin ledger entries, no allocations
 */
class VerifyComplianceOperation implements OperationInterface
{
    private User $user;
    private string $operationType;
    private float $amount;
    private ComplianceGateService $complianceGate;

    /**
     * @param User $user
     * @param string $operationType Type of operation ('investment', 'withdrawal', 'deposit')
     * @param float $amount Amount for the operation
     */

	public function getName(): string
	{
	    return 'verify_compliance';
	}

    public function __construct(User $user, string $operationType, float $amount)
    {
        $this->user = $user;
        $this->operationType = $operationType;
        $this->amount = $amount;
        $this->complianceGate = app(ComplianceGateService::class);
    }

    /**
     * Execute compliance verification
     *
     * @param SagaContext $context
     * @return OperationResult
     */
    public function execute(SagaContext $context): OperationResult
    {
        Log::info("OPERATION: Verifying compliance for {$this->operationType}", [
            'user_id' => $this->user->id,
            'operation_type' => $this->operationType,
            'amount' => $this->amount,
        ]);

        // Route to appropriate compliance check based on operation type
        $complianceResult = match ($this->operationType) {
            'investment' => $this->complianceGate->canInvest($this->user, $this->amount),
            'withdrawal' => $this->complianceGate->canWithdraw($this->user, $this->amount),
            'deposit', 'wallet_credit' => $this->complianceGate->canReceiveFunds($this->user),
            default => throw new \InvalidArgumentException("Unknown operation type: {$this->operationType}"),
        };

        if (!$complianceResult['allowed']) {
            Log::warning("OPERATION BLOCKED: Compliance check failed", [
                'user_id' => $this->user->id,
                'email' => $this->user->email,
                'operation_type' => $this->operationType,
                'reason' => $complianceResult['reason'],
                'requirements' => $complianceResult['requirements'],
            ]);

            // Log compliance block for audit trail
            $this->complianceGate->logComplianceBlock(
                $this->user,
                $this->operationType,
                $complianceResult
            );

            // Return failure - saga will abort
            return OperationResult::failure(
                $complianceResult['reason'],
                ['compliance_blocked' => true, 'requirements' => $complianceResult['requirements']]
            );
        }

        Log::info("OPERATION: Compliance verified", [
            'user_id' => $this->user->id,
            'operation_type' => $this->operationType,
            'requirements_met' => $complianceResult['requirements'],
        ]);

        // Store compliance verification in saga context for audit trail
        $context->setShared('compliance_verified_at', now()->toDateTimeString());
        $context->setShared('compliance_requirements_met', $complianceResult['requirements']);

        return OperationResult::success('Compliance verified', [
            'kyc_status' => $this->user->kyc_status,
            'requirements_met' => $complianceResult['requirements'],
        ]);
    }

    /**
     * Compensate compliance verification
     *
     * NO-OP: Compliance verification doesn't change state, so nothing to undo
     *
     * @param SagaContext $context
     * @return void
     */
    public function compensate(SagaContext $context): void
    {
        // NO-OP: No state changes to reverse
        Log::info("COMPENSATION: VerifyComplianceOperation (no-op)", [
            'user_id' => $this->user->id,
        ]);
    }
}
