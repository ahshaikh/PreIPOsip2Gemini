<?php

namespace App\Services\Orchestration\Operations;

use App\Services\Orchestration\Saga\SagaContext;
use App\Services\Accounting\AdminLedger;
use Illuminate\Support\Facades\Log;

/**
 * RecordCampaignLiabilityOperation
 *
 * PROTOCOL:
 * - Records campaign discount as admin EXPENSE/LIABILITY
 * - Uses double-entry accounting via AdminLedger
 * - Ensures admin balance reflects campaign costs
 * - Compensation: Creates reversal entries
 *
 * CRITICAL: Campaign discounts are financial liabilities, not just "features"
 */
class RecordCampaignLiabilityOperation implements OperationInterface
{
    private AdminLedger $adminLedger;

    public function __construct(
        private $campaign,
        private float $discountAmount,
        private $investment
    ) {
        $this->adminLedger = app(AdminLedger::class);
    }

    public function execute(SagaContext $context): OperationResult
    {
        // Skip if no discount applied
        if ($this->discountAmount <= 0) {
            return OperationResult::success('No campaign discount to record');
        }

        if (!$this->campaign) {
            Log::warning("OPERATION: Campaign is null but discount exists", [
                'discount_amount' => $this->discountAmount,
                'investment_id' => $this->investment->id,
            ]);
            return OperationResult::failure('Campaign missing but discount applied');
        }

        try {
            // Record as admin expense in ledger (double-entry)
            // DEBIT: Expenses (+discount)
            // CREDIT: Liabilities (+discount) [owed to user as share value]
            $entries = $this->adminLedger->recordCampaignDiscount(
                $this->discountAmount,
                $this->campaign->id,
                $this->investment->id,
                "Campaign '{$this->campaign->code}' discount for investment #{$this->investment->id}"
            );

            // Store entry IDs for compensation
            $context->setShared('campaign_ledger_debit_id', $entries[0]->id);
            $context->setShared('campaign_ledger_credit_id', $entries[1]->id);

            Log::info("OPERATION: Campaign liability recorded in admin ledger", [
                'campaign_id' => $this->campaign->id,
                'discount_amount' => $this->discountAmount,
                'investment_id' => $this->investment->id,
                'ledger_entries' => [$entries[0]->id, $entries[1]->id],
            ]);

            return OperationResult::success('Campaign liability recorded', [
                'ledger_entry_ids' => [$entries[0]->id, $entries[1]->id],
                'discount_amount' => $this->discountAmount,
            ]);

        } catch (\Throwable $e) {
            Log::error("OPERATION FAILED: Campaign liability recording failed", [
                'campaign_id' => $this->campaign?->id,
                'discount_amount' => $this->discountAmount,
                'error' => $e->getMessage(),
            ]);

            return OperationResult::failure(
                "Failed to record campaign liability: {$e->getMessage()}"
            );
        }
    }

    public function compensate(SagaContext $context): void
    {
        $debitId = $context->getShared('campaign_ledger_debit_id');
        $creditId = $context->getShared('campaign_ledger_credit_id');

        if (!$debitId || !$creditId) {
            Log::warning("COMPENSATION SKIPPED: No ledger entries found for campaign");
            return;
        }

        try {
            // Create REVERSAL entries (ledger entries are immutable, so create offsetting entries)
            // This is the CORRECT way to "undo" accounting entries

            // Reverse: DEBIT Liabilities (reduce liability)
            //          CREDIT Expenses (reduce expenses)
            $reversalEntries = $this->adminLedger->createDoubleEntry(
                debitAccount: 'liabilities',
                creditAccount: 'expenses',
                amount: $this->discountAmount,
                referenceType: 'campaign_usage',
                referenceId: $this->campaign->id,
                description: "REVERSAL: Campaign discount for investment #{$this->investment->id} (saga compensation)"
            );

            Log::info("COMPENSATION: Campaign liability reversed via offsetting entries", [
                'campaign_id' => $this->campaign->id,
                'discount_amount' => $this->discountAmount,
                'original_entries' => [$debitId, $creditId],
                'reversal_entries' => [$reversalEntries[0]->id, $reversalEntries[1]->id],
            ]);

        } catch (\Throwable $e) {
            Log::error("COMPENSATION FAILED: Could not reverse campaign liability", [
                'campaign_id' => $this->campaign?->id,
                'discount_amount' => $this->discountAmount,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function getName(): string
    {
        return 'RecordCampaignLiability';
    }
}
