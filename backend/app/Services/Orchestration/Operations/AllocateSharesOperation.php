<?php

namespace App\Services\Orchestration\Operations;

use App\Services\Orchestration\Saga\SagaContext;
use App\Services\AllocationService;
use App\Exceptions\InsufficientInventoryException;
use Illuminate\Support\Facades\Log;

/**
 * AllocateSharesOperation
 *
 * PROTOCOL:
 * - Allocates shares from inventory SYNCHRONOUSLY (not async!)
 * - Uses FIFO allocation with pessimistic locking
 * - Creates UserInvestment records
 * - Decrements BulkPurchase.value_remaining atomically
 * - Compensation: Reverses allocation by incrementing inventory back
 */
class AllocateSharesOperation implements OperationInterface
{
    private AllocationService $allocationService;

    public function __construct(
        private $investment,
        private $user
    ) {
        $this->allocationService = app(AllocationService::class);
    }

    public function execute(SagaContext $context): OperationResult
    {
        try {
            // Allocate shares synchronously
            // This replaces ProcessAllocationJob (no more async failures!)
            // GAP 2 FIX: Now throws InsufficientInventoryException instead of returning false
            $this->allocationService->allocateShares(
                $this->user,
                $this->investment->product,
                $this->investment->final_amount,
                $this->investment,
                'investment',
                setting('allow_fractional_shares', true)
            );

            // Get allocated investments for compensation
            $allocatedInvestments = $this->investment->userInvestments;
            $context->setShared('allocated_investment_ids', $allocatedInvestments->pluck('id')->toArray());

            Log::info("OPERATION: Shares allocated successfully", [
                'investment_id' => $this->investment->id,
                'user_investments_created' => $allocatedInvestments->count(),
            ]);

            return OperationResult::success('Shares allocated', [
                'user_investments_count' => $allocatedInvestments->count(),
                'total_units' => $allocatedInvestments->sum('units_allocated'),
            ]);

        } catch (InsufficientInventoryException $e) {
            // GAP 2 FIX: Catch typed exception for insufficient inventory
            Log::warning("OPERATION: Allocation failed - insufficient inventory", [
                'investment_id' => $this->investment->id,
                'amount' => $this->investment->final_amount,
                'available' => $e->getAvailable(),
                'requested' => $e->getRequested(),
            ]);

            return OperationResult::failure(
                'Insufficient inventory to fulfill allocation',
                ['inventory_depleted' => true]
            );

        } catch (\Throwable $e) {
            Log::error("OPERATION FAILED: Share allocation exception", [
                'investment_id' => $this->investment->id,
                'error' => $e->getMessage(),
            ]);

            return OperationResult::failure(
                "Allocation failed: {$e->getMessage()}"
            );
        }
    }

    public function compensate(SagaContext $context): void
    {
        $investmentIds = $context->getShared('allocated_investment_ids', []);

        if (empty($investmentIds)) {
            Log::warning("COMPENSATION SKIPPED: No allocated investments found");
            return;
        }

        try {
            // Reverse allocation using AllocationService
            $this->allocationService->reverseAllocation(
                $this->investment,
                "Saga compensation - allocation failed downstream"
            );

            Log::info("COMPENSATION: Share allocation reversed", [
                'investment_id' => $this->investment->id,
                'reversed_count' => count($investmentIds),
            ]);

        } catch (\Throwable $e) {
            Log::error("COMPENSATION FAILED: Could not reverse allocation", [
                'investment_id' => $this->investment->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function getName(): string
    {
        return 'AllocateShares';
    }
}
