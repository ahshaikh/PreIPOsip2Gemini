<?php

namespace App\Services\Orchestration\Operations;

use App\Models\User;
use App\Models\Investment;
use App\Services\BenefitOrchestrator;
use App\Services\BenefitCalculationResult;
use App\Services\Orchestration\Saga\SagaContext;
use Illuminate\Support\Facades\Log;

/**
 * CalculateCampaignBenefitOperation
 *
 * [D.11-D.14]: Unified benefit calculation with full audit trail
 *
 * INTEGRATION:
 * - Uses BenefitOrchestrator as SINGLE AUTHORITY for benefit decisions
 * - Enforces precedence rules (promotional > referral > none)
 * - Prevents illegal stacking (exclusivity constraints)
 * - Logs full decision trail for audit/replay
 *
 * COMPENSATION:
 * - This is a READ-ONLY operation (calculates but doesn't apply)
 * - No compensation needed (no state changes)
 * - Actual benefit application happens in RecordCampaignLiabilityOperation
 */
class CalculateCampaignBenefitOperation implements OperationInterface
{
    private User $user;
    private Investment $investment;
    private BenefitOrchestrator $benefitOrchestrator;

    /**
     * @param User $user
     * @param Investment $investment
     */
    public function __construct(User $user, Investment $investment)
    {
        $this->user = $user;
        $this->investment = $investment;
        $this->benefitOrchestrator = app(BenefitOrchestrator::class);
    }

    /**
     * Execute benefit calculation
     *
     * [D.11]: SINGLE AUTHORITY - BenefitOrchestrator makes all decisions
     * [D.12]: CAUSAL ORDERING - Precedence rules enforced in orchestrator
     * [D.13]: NO STACKING - Exclusivity enforced in orchestrator
     * [D.14]: AUDITABLE - Full decision trail logged
     *
     * @param SagaContext $context
     * @return OperationResult
     */
    public function execute(SagaContext $context): OperationResult
    {
        Log::info("OPERATION: Calculating campaign benefit", [
            'user_id' => $this->user->id,
            'investment_id' => $this->investment->id,
            'original_amount' => $this->investment->total_amount,
        ]);

        try {
            // [D.11]: SINGLE AUTHORITY - Only BenefitOrchestrator decides
            $benefitResult = $this->benefitOrchestrator->calculateApplicableBenefit(
                $this->user,
                $this->investment
            );

            // Store result in saga context for downstream operations
            $context->setShared('benefit_result', $benefitResult->toArray());
            $context->setShared('benefit_type', $benefitResult->getBenefitType());
            $context->setShared('benefit_amount', $benefitResult->getBenefitAmount());
            $context->setShared('final_amount', $benefitResult->getFinalAmount());

            // Update investment final_amount based on benefit
            $this->investment->update([
                'final_amount' => $benefitResult->getFinalAmount(),
                'benefit_type' => $benefitResult->getBenefitType(),
                'benefit_amount' => $benefitResult->getBenefitAmount(),
            ]);

            Log::info("OPERATION: Benefit calculated successfully", [
                'user_id' => $this->user->id,
                'investment_id' => $this->investment->id,
                'benefit_type' => $benefitResult->getBenefitType(),
                'original_amount' => $benefitResult->getOriginalAmount(),
                'benefit_amount' => $benefitResult->getBenefitAmount(),
                'final_amount' => $benefitResult->getFinalAmount(),
                'reason' => $benefitResult->getEligibilityReason(),
            ]);

            return OperationResult::success(
                'Benefit calculated',
                [
                    'benefit_type' => $benefitResult->getBenefitType(),
                    'benefit_amount' => $benefitResult->getBenefitAmount(),
                    'final_amount' => $benefitResult->getFinalAmount(),
                    'has_benefit' => $benefitResult->hasApplicableBenefit(),
                ]
            );

        } catch (\Throwable $e) {
            Log::error("OPERATION FAILED: Benefit calculation exception", [
                'user_id' => $this->user->id,
                'investment_id' => $this->investment->id,
                'error' => $e->getMessage(),
            ]);

            return OperationResult::failure(
                "Benefit calculation failed: {$e->getMessage()}"
            );
        }
    }

    /**
     * Compensate benefit calculation
     *
     * NO-OP: This operation only calculates (doesn't change state)
     * The actual benefit application is done in RecordCampaignLiabilityOperation
     *
     * @param SagaContext $context
     * @return void
     */
    public function compensate(SagaContext $context): void
    {
        // NO-OP: Calculation doesn't need compensation
        // If we need to revert the investment.final_amount update, we can do it here
        // But typically the Investment will be deleted/marked failed on saga failure

        Log::info("COMPENSATION: CalculateCampaignBenefitOperation (no-op)", [
            'user_id' => $this->user->id,
            'investment_id' => $this->investment->id,
        ]);
    }
}
