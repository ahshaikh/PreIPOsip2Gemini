<?php
// V-SECURITY-VALIDATION - Wallet Deposit Request Validation
// V-COMPLIANCE-GATE-2025 - KYC enforcement for cash ingress

namespace App\Http\Requests\Financial;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Services\ComplianceGateService;

class WalletDepositRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * [COMPLIANCE GATE]: Enforces KYC requirement BEFORE allowing cash ingress
     */
    public function authorize(): bool
    {
        $user = $this->user();

        if (!$user) {
            return false;
        }

        // Check if wallet deposits are enabled globally
        if (!setting('wallet_deposits_enabled', true)) {
            return false;
        }

        // [C.8 FIX]: COMPLIANCE GATE - Block cash ingress before KYC
        $complianceGate = app(ComplianceGateService::class);
        $canReceiveFunds = $complianceGate->canReceiveFunds($user);

        if (!$canReceiveFunds['allowed']) {
            // Log the compliance block for audit trail
            $complianceGate->logComplianceBlock($user, 'wallet_deposit', $canReceiveFunds);

            // Store reason for failedAuthorization() method to access
            $this->merge(['_compliance_block_reason' => $canReceiveFunds['reason']]);

            return false;
        }

        return true;
    }

    /**
     * Get the error messages for authorization failures.
     */
    protected function failedAuthorization()
    {
        $reason = $this->input('_compliance_block_reason', 'You are not authorized to perform this action.');

        throw new \Illuminate\Auth\Access\AuthorizationException($reason);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $minDeposit = (float) setting('min_wallet_deposit', 100);
        $maxDeposit = (float) setting('max_wallet_deposit', 1000000);

        return [
            'amount' => [
                'required',
                'numeric',
                "min:{$minDeposit}",
                "max:{$maxDeposit}",
                'regex:/^\d+(\.\d{1,2})?$/',
            ],
            'payment_method' => [
                'required',
                'string',
                Rule::in(['razorpay', 'upi', 'netbanking', 'card']),
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'amount.min' => 'Minimum deposit amount is ₹' . setting('min_wallet_deposit', 100),
            'amount.max' => 'Maximum deposit amount is ₹' . number_format(setting('max_wallet_deposit', 1000000)),
            'amount.regex' => 'Amount can have maximum 2 decimal places.',
            'payment_method.in' => 'Invalid payment method selected.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('amount')) {
            $amount = preg_replace('/[^0-9.]/', '', $this->amount);
            $this->merge(['amount' => $amount]);
        }
    }
}
