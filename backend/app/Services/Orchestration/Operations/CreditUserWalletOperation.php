<?php

namespace App\Services\Orchestration\Operations;

use App\Services\Orchestration\Saga\SagaContext;
use App\Services\WalletService;
use App\Services\Accounting\AdminLedger;
use App\Enums\TransactionType; // V-FIX: Import TransactionType enum
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
    private WalletService $walletService;

    public function __construct(
        private $user,
        private float $amount,
        private $payment
    ) {
        $this->walletService = app(WalletService::class);
    }

    public function execute(SagaContext $context): OperationResult
    {
        try {
            // V-FIX-WALLET-NOT-REFLECTING: Fix deposit() call signature
            // WalletService::deposit() expects: (User, amount, TransactionType, description, ?Model reference, bool bypassCheck)
            // Previous call was passing wrong parameter types
            // Credit wallet (atomic operation)
            $transaction = $this->walletService->deposit(
                $this->user,
                $this->amount,
                TransactionType::DEPOSIT,  // V-FIX: Use enum instead of 'payment_received' string
                "Payment #{$this->payment->id} received",
                $this->payment  // V-FIX: Pass Payment model (not string 'payment' and id separately)
            );

            // Store transaction ID for potential compensation
            $context->setShared('wallet_credit_transaction_id', $transaction->id);

            Log::info("OPERATION: Wallet credited", [
                'user_id' => $this->user->id,
                'amount' => $this->amount,
                'transaction_id' => $transaction->id,
            ]);

            return OperationResult::success('Wallet credited', [
                'transaction_id' => $transaction->id,
                'new_balance' => $this->user->wallet->balance,
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
        $transactionId = $context->getShared('wallet_credit_transaction_id');

        if (!$transactionId) {
            Log::warning("COMPENSATION SKIPPED: No transaction ID found for wallet credit");
            return;
        }

        try {
            // Reverse the credit by debiting the same amount
            $this->walletService->withdraw(
                $this->user,
                $this->amount,
                'payment_reversal',
                "Payment #{$this->payment->id} reversed (saga compensation)",
                'payment',
                $this->payment->id
            );

            Log::info("COMPENSATION: Wallet credit reversed", [
                'user_id' => $this->user->id,
                'amount' => $this->amount,
                'original_transaction_id' => $transactionId,
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
