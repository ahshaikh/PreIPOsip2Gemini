<?php
// V-SECURITY-VALIDATION - Withdrawal Request Validation

namespace App\Http\Requests\Financial;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Wallet;

class WithdrawalRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        if (!$user) {
            return false;
        }

        // Must have approved KYC
        if ($user->kyc_status !== 'approved') {
            return false;
        }

        // Must have verified bank account
        if (empty($user->bank_account_number) || empty($user->bank_ifsc_code)) {
            return false;
        }

        // Check if withdrawals are enabled
        if (!setting('withdrawals_enabled', true)) {
            return false;
        }

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $user = $this->user();
        $wallet = Wallet::where('user_id', $user->id)
            ->where('wallet_type', 'main')
            ->first();

        $availableBalance = $wallet ? $wallet->balance : 0;
        $minWithdrawal = (float) setting('min_withdrawal_amount', 100);
        $maxWithdrawal = min(
            (float) setting('max_withdrawal_amount', 1000000),
            $availableBalance
        );

        return [
            'amount' => [
                'required',
                'numeric',
                "min:{$minWithdrawal}",
                "max:{$maxWithdrawal}",
                'regex:/^\d+(\.\d{1,2})?$/', // Max 2 decimal places
                function ($attribute, $value, $fail) use ($availableBalance) {
                    if ($value > $availableBalance) {
                        $fail('Insufficient wallet balance. Available: ₹' . number_format($availableBalance, 2));
                    }
                },
            ],
            'wallet_type' => [
                'nullable',
                'string',
                Rule::in(['main', 'bonus', 'referral']),
            ],
            'bank_account_id' => [
                'nullable',
                'integer',
                Rule::exists('bank_accounts', 'id')->where('user_id', $user->id),
            ],
            'notes' => [
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'amount.min' => 'Minimum withdrawal amount is ₹' . setting('min_withdrawal_amount', 100),
            'amount.max' => 'Maximum withdrawal amount exceeds your available balance or limit.',
            'amount.regex' => 'Amount can have maximum 2 decimal places.',
            'bank_account_id.exists' => 'Selected bank account not found.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Check daily withdrawal limit
            $user = $this->user();
            $dailyLimit = (float) setting('daily_withdrawal_limit', 500000);

            $todayWithdrawals = $user->withdrawals()
                ->whereDate('created_at', today())
                ->whereIn('status', ['pending', 'approved', 'completed'])
                ->sum('amount');

            $requestedAmount = (float) $this->amount;

            if (($todayWithdrawals + $requestedAmount) > $dailyLimit) {
                $remaining = max(0, $dailyLimit - $todayWithdrawals);
                $validator->errors()->add(
                    'amount',
                    "Daily withdrawal limit exceeded. Remaining today: ₹" . number_format($remaining, 2)
                );
            }

            // Check pending withdrawal count
            $pendingCount = $user->withdrawals()
                ->where('status', 'pending')
                ->count();

            $maxPending = (int) setting('max_pending_withdrawals', 3);

            if ($pendingCount >= $maxPending) {
                $validator->errors()->add(
                    'amount',
                    "You have {$pendingCount} pending withdrawals. Maximum allowed: {$maxPending}"
                );
            }
        });
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

        // Default to main wallet if not specified
        if (!$this->has('wallet_type')) {
            $this->merge(['wallet_type' => 'main']);
        }
    }
}
