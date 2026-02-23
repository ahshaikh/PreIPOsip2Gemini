<?php
// V-WAVE3-REVERSAL-2026: Exception for accounts in financial recovery mode

namespace App\Exceptions\Financial;

use Exception;

/**
 * AccountRecoveryModeException
 *
 * Thrown when an operation is attempted on an account that is in
 * financial recovery mode (has outstanding receivables from
 * bonus reversal shortfalls).
 *
 * When in recovery mode:
 * - Withdrawals are blocked
 * - Share transfers are blocked
 * - Bonus accrual is blocked
 * - Only deposits are allowed
 */
class AccountRecoveryModeException extends Exception
{
    public function __construct(string $message = "Account is in financial recovery mode")
    {
        parent::__construct($message);
    }
}
