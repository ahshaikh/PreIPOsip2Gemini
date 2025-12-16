<?php
// V-AUDIT-MODULE3-002 (Created) - Custom exception for insufficient balance
// Purpose: Separate business logic errors from system errors for better error handling

namespace App\Exceptions\Financial;

use Exception;

/**
 * InsufficientBalanceException
 *
 * Thrown when a user attempts to withdraw or spend more funds than available in their wallet.
 * This is a business logic exception, not a system error, and should be caught separately
 * to return appropriate HTTP 422 (Unprocessable Entity) responses to the client.
 *
 * Usage Example:
 * ```php
 * try {
 *     $walletService->withdraw($user, $amount, ...);
 * } catch (InsufficientBalanceException $e) {
 *     return response()->json(['message' => $e->getMessage()], 422);
 * } catch (\Exception $e) {
 *     return response()->json(['message' => 'System error'], 500);
 * }
 * ```
 */
class InsufficientBalanceException extends Exception
{
    protected $availableBalance;
    protected $requestedAmount;

    /**
     * Create a new exception instance
     *
     * @param string $availableBalance The available balance in the wallet
     * @param string $requestedAmount The amount requested to withdraw
     * @param string|null $message Custom error message (optional)
     */
    public function __construct(string $availableBalance, string $requestedAmount, ?string $message = null)
    {
        $this->availableBalance = $availableBalance;
        $this->requestedAmount = $requestedAmount;

        $message = $message ?? "Insufficient funds. Available: ₹{$availableBalance}, Requested: ₹{$requestedAmount}";

        parent::__construct($message);
    }

    /**
     * Get the available balance
     *
     * @return string
     */
    public function getAvailableBalance(): string
    {
        return $this->availableBalance;
    }

    /**
     * Get the requested amount
     *
     * @return string
     */
    public function getRequestedAmount(): string
    {
        return $this->requestedAmount;
    }

    /**
     * Get error details as array for API response
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'error' => 'insufficient_balance',
            'message' => $this->getMessage(),
            'available_balance' => $this->availableBalance,
            'requested_amount' => $this->requestedAmount,
        ];
    }
}
