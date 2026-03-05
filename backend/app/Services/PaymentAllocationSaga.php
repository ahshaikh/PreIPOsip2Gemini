<?php
/**
 * FIX 4 (P0): Payment Allocation Saga
 * V-PHASE4-LEDGER (Ledger Integration + TDS Compliance)
 * V-ORCHESTRATION-2026: Consolidated financial mutations into FinancialOrchestrator.
 */

namespace App\Services;

use App\Models\Payment;
use App\Models\SagaExecution;
use Illuminate\Support\Facades\Log;

/**
 * PaymentAllocationSaga - Orchestrates the payment allocation lifecycle.
 * 
 * V-ORCHESTRATION-2026: This class now acts as a proxy for FinancialOrchestrator
 * to maintain backward compatibility while ensuring all mutations happen
 * within the central orchestrator's transaction boundary.
 */
class PaymentAllocationSaga
{
    /**
     * Execute the payment allocation saga.
     */
    public function execute(Payment $payment): SagaExecution
    {
        $orchestrator = app(\App\Services\FinancialOrchestrator::class);
        return $orchestrator->executePaymentAllocationSaga($payment);
    }
}
