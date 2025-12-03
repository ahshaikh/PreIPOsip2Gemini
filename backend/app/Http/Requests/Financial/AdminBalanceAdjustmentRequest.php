<?php
// V-SECURITY-VALIDATION - Admin Balance Adjustment Request Validation

namespace App\Http\Requests\Financial;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\User;
use App\Models\Wallet;

class AdminBalanceAdjustmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Must be admin with appropriate permission
        return $this->user()->can('users.adjust_wallet');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'user_id' => [
                'required',
                'integer',
                'exists:users,id',
            ],
            'amount' => [
                'required',
                'numeric',
                'not_in:0',
                'min:-10000000',
                'max:10000000',
                'regex:/^-?\d+(\.\d{1,2})?$/',
            ],
            'type' => [
                'required',
                'string',
                Rule::in(['credit', 'debit']),
            ],
            'wallet_type' => [
                'required',
                'string',
                Rule::in(['main', 'bonus', 'referral']),
            ],
            'reason' => [
                'required',
                'string',
                'min:10',
                'max:500',
            ],
            'reference_number' => [
                'nullable',
                'string',
                'max:100',
            ],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // For debit operations, ensure sufficient balance
            if ($this->type === 'debit') {
                $user = User::find($this->user_id);
                if ($user) {
                    $wallet = Wallet::where('user_id', $user->id)
                        ->where('wallet_type', $this->wallet_type ?? 'main')
                        ->first();

                    $currentBalance = $wallet ? $wallet->balance : 0;
                    $debitAmount = abs((float) $this->amount);

                    if ($debitAmount > $currentBalance) {
                        $validator->errors()->add(
                            'amount',
                            "Insufficient balance. Current {$this->wallet_type} balance: ₹" . number_format($currentBalance, 2)
                        );
                    }
                }
            }

            // Validate daily adjustment limit for non-super-admins
            $admin = $this->user();
            if (!$admin->hasRole('super-admin')) {
                $dailyLimit = (float) setting('admin_daily_adjustment_limit', 100000);

                $todayAdjustments = \App\Models\ActivityLog::where('user_id', $admin->id)
                    ->where('activity_type', 'balance_adjustment')
                    ->whereDate('created_at', today())
                    ->sum('metadata->amount');

                $requestedAmount = abs((float) $this->amount);

                if (($todayAdjustments + $requestedAmount) > $dailyLimit) {
                    $remaining = max(0, $dailyLimit - $todayAdjustments);
                    $validator->errors()->add(
                        'amount',
                        "Daily adjustment limit exceeded. Remaining: ₹" . number_format($remaining, 2)
                    );
                }
            }
        });
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'amount.not_in' => 'Adjustment amount cannot be zero.',
            'amount.regex' => 'Amount can have maximum 2 decimal places.',
            'reason.min' => 'Please provide a detailed reason (at least 10 characters).',
            'reason.required' => 'A reason is required for audit purposes.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('amount')) {
            $amount = preg_replace('/[^0-9.-]/', '', $this->amount);
            // Ensure positive for credit, negative for debit
            if ($this->type === 'debit' && $amount > 0) {
                $amount = -abs($amount);
            } elseif ($this->type === 'credit' && $amount < 0) {
                $amount = abs($amount);
            }
            $this->merge(['amount' => $amount]);
        }
    }
}
