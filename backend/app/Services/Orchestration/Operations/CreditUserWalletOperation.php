<?php

namespace App\Services\Orchestration\Operations;

use App\Services\FinancialOrchestrator;
use App\Services\Orchestration\Saga\SagaContext;
use App\Enums\TransactionType;
use Illuminate\Support\Facades\Log;

/**
 * CreditUserWalletOperation
 *
 * PROTOCOL:
 * - Credits user wallet atomically
 * - Creates transaction ledger entry
 * - Stores transaction ID in context for compensation
 * - Compensation: Reverses transaction by debiting same amount
 */
class CreditUserWalletOperation implements OperationInterface
{
    private FinancialOrchestrator $financialOrchestrator;

    public function __construct(
        private $user,
        private float $amount,
        private $payment
    ) {
        $this->financialOrchestrator = app(FinancialOrchestrator::class);
    }

    public function execute(SagaContext $context): OperationResult
    {
        try {
            $amountPaise = (int) round($this->amount * 100);
            $this->financialOrchestrator->creditUserWallet(
                $this->user,
                $amountPaise,
                TransactionType::DEPOSIT,
                "Payment #{$this->payment->id} received",
                $this->payment
            );

            $context->setShared('wallet_credit_applied', true);

            Log::info("OPERATION: Wallet credited", [
                'user_id' => $this->user->id,
                'amount_paise' => $amountPaise,
            ]);

            return OperationResult::success('Wallet credited', [
                'amount_paise' => $amountPaise,
            ]);

        } catch (\Throwable $e) {
            Log::error("OPERATION FAILED: Wallet credit failed", [
                'user_id' => $this->user->id,
                'amount' => $this->amount,
                'error' => $e->getMessage(),
            ]);

            return OperationResult::failure(
                "Failed to credit wallet: {$e->getMessage()}"
            );
        }
    }

    public function compensate(SagaContext $context): void
    {
        if (!$context->getShared('wallet_credit_applied')) {
            Log::warning("COMPENSATION SKIPPED: No wallet credit marker found");
            return;
        }

        try {
            $amountPaise = (int) round($this->amount * 100);
            $this->financialOrchestrator->debitUserWallet(
                $this->user,
                $amountPaise,
                TransactionType::CHARGEBACK,
                "Payment #{$this->payment->id} reversed (saga compensation)",
                $this->payment,
                true
            );

            Log::info("COMPENSATION: Wallet credit reversed", [
                'user_id' => $this->user->id,
                'amount_paise' => $amountPaise,
            ]);

        } catch (\Throwable $e) {
            // Compensation failure logged but doesn't stop compensation chain
            Log::error("COMPENSATION FAILED: Could not reverse wallet credit", [
                'user_id' => $this->user->id,
                'amount' => $this->amount,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function getName(): string
    {
        return 'CreditUserWallet';
    }
}
