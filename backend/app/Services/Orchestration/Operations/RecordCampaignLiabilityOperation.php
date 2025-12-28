<?php

namespace App\Services\Orchestration\Operations;

use App\Models\Investment;
use App\Services\Orchestration\Saga\SagaContext;
use App\Services\Accounting\AdminLedger;
use App\Services\BenefitOrchestrator;
use App\Services\BenefitCalculationResult;
use Illuminate\Support\Facades\Log;

/**
 * RecordCampaignLiabilityOperation
 *
 * [D.15]: Account for campaign costs as admin liabilities
 * [D.14]: Record campaign usage for audit trail
 *
 * PROTOCOL:
 * - Records campaign discount as admin EXPENSE/LIABILITY
 * - Uses double-entry accounting via AdminLedger
 * - Tracks usage in campaign_usages table (for limits and auditing)
 * - Ensures admin balance reflects campaign costs
 * - Compensation: Creates reversal entries
 *
 * CRITICAL: Campaign discounts are financial liabilities, not just "features"
 */
class RecordCampaignLiabilityOperation implements OperationInterface
{
    private AdminLedger $adminLedger;
    private BenefitOrchestrator $benefitOrchestrator;

    public function __construct(
        private Investment $investment
    ) {
        $this->adminLedger = app(AdminLedger::class);
        $this->benefitOrchestrator = app(BenefitOrchestrator::class);
    }

    public function execute(SagaContext $context): OperationResult
    {
        // Get benefit result from context (calculated by CalculateCampaignBenefitOperation)
        $benefitResultArray = $context->getShared('benefit_result');

        if (!$benefitResultArray) {
            Log::warning("OPERATION: No benefit result found in context", [
                'investment_id' => $this->investment->id,
            ]);
            return OperationResult::success('No benefit to record');
        }

        // Reconstruct BenefitCalculationResult from array
        // (We can't store objects in context, only arrays)
        $benefitAmount = $benefitResultArray['benefit_amount'] ?? 0;

        // Skip if no benefit applied
        if ($benefitAmount <= 0) {
            return OperationResult::success('No campaign benefit to record');
        }

        try {
            // [D.14 + D.15]: Record campaign usage and cost
            // This will:
            // 1. Insert into campaign_usages table (for limits and audit)
            // 2. Record in AdminLedger as EXPENSE/LIABILITY (double-entry)
            $benefitResult = $this->reconstructBenefitResult($benefitResultArray);

            $this->benefitOrchestrator->recordCampaignUsageAndCost(
                $benefitResult,
                $this->investment
            );

            // Store for compensation (we'll need the benefit amount to reverse)
            $context->setShared('recorded_benefit_amount', $benefitAmount);
            $context->setShared('recorded_benefit_type', $benefitResultArray['benefit_type']);

            Log::info("OPERATION: Campaign liability recorded", [
                'investment_id' => $this->investment->id,
                'benefit_type' => $benefitResultArray['benefit_type'],
                'benefit_amount' => $benefitAmount,
                'admin_expense' => true,
            ]);

            return OperationResult::success('Campaign liability recorded', [
                'benefit_amount' => $benefitAmount,
                'benefit_type' => $benefitResultArray['benefit_type'],
            ]);

        } catch (\Throwable $e) {
            Log::error("OPERATION FAILED: Campaign liability recording failed", [
                'investment_id' => $this->investment->id,
                'benefit_amount' => $benefitAmount,
                'error' => $e->getMessage(),
            ]);

            return OperationResult::failure(
                "Failed to record campaign liability: {$e->getMessage()}"
            );
        }
    }

    public function compensate(SagaContext $context): void
    {
        $benefitAmount = $context->getShared('recorded_benefit_amount');
        $benefitType = $context->getShared('recorded_benefit_type');

        if (!$benefitAmount || $benefitAmount <= 0) {
            Log::info("COMPENSATION SKIPPED: No benefit amount to reverse");
            return;
        }

        try {
            // Create REVERSAL entries in AdminLedger
            // (Ledger entries are immutable, so create offsetting entries)

            // Reverse: DEBIT Liabilities (reduce liability)
            //          CREDIT Expenses (reduce expenses)
            $reversalEntries = $this->adminLedger->createDoubleEntry(
                debitAccount: 'liabilities',
                creditAccount: 'expenses',
                amount: $benefitAmount,
                referenceType: 'investment',
                referenceId: $this->investment->id,
                description: "REVERSAL: Campaign benefit for investment #{$this->investment->id} (saga compensation)"
            );

            // Mark campaign usage as reversed
            \DB::table('campaign_usages')
                ->where('investment_id', $this->investment->id)
                ->update([
                    'is_reversed' => true,
                    'reversed_at' => now(),
                    'reversal_reason' => 'Saga compensation - investment failed',
                ]);

            Log::info("COMPENSATION: Campaign liability reversed", [
                'investment_id' => $this->investment->id,
                'benefit_type' => $benefitType,
                'benefit_amount' => $benefitAmount,
                'reversal_entries' => [$reversalEntries[0]->id, $reversalEntries[1]->id],
            ]);

        } catch (\Throwable $e) {
            Log::error("COMPENSATION FAILED: Could not reverse campaign liability", [
                'investment_id' => $this->investment->id,
                'benefit_amount' => $benefitAmount,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Reconstruct BenefitCalculationResult from array
     * (Needed because we can't store objects in SagaContext)
     */
    private function reconstructBenefitResult(array $data): BenefitCalculationResult
    {
        $benefitType = $data['benefit_type'];

        if ($benefitType === 'promotional_campaign') {
            $campaign = \App\Models\Campaign::find($data['campaign_id']);
            return BenefitCalculationResult::fromCampaign(
                campaign: $campaign,
                originalAmount: $data['original_amount'],
                benefitAmount: $data['benefit_amount'],
                finalAmount: $data['final_amount'],
                eligibilityReason: $data['eligibility_reason'],
                metadata: $data['metadata']
            );
        } elseif ($benefitType === 'referral_bonus') {
            $referral = \App\Models\Referral::find($data['referral_id']);
            return BenefitCalculationResult::fromReferral(
                referral: $referral,
                originalAmount: $data['original_amount'],
                benefitAmount: $data['benefit_amount'],
                finalAmount: $data['final_amount'],
                eligibilityReason: $data['eligibility_reason'],
                metadata: $data['metadata']
            );
        } else {
            return BenefitCalculationResult::noBenefit($data['original_amount']);
        }
    }

    public function getName(): string
    {
        return 'RecordCampaignLiability';
    }
}
