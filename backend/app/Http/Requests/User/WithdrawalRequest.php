<?php
// V-FINAL-1730-428 (Created)

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Setting;
use App\Models\Withdrawal;
use Illuminate\Validation\Validator;

class WithdrawalRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * This checks for KYC status.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        
        // Test: test_validates_kyc_approved
        if ($user->kyc->status !== 'verified') {
            return false;
        }
        
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        // Get dynamic minimum from settings
        $min = setting('min_withdrawal_amount', 1000);

        return [
            // Test: test_validates_amount_positive
            // Test: test_validates_amount_minimum
            'amount' => "required|numeric|min:{$min}",
            
            // Test: test_validates_bank_details_present
            'bank_details' => 'required|array',
            'bank_details.account' => 'required|string',
            'bank_details.ifsc' => 'required|string',
        ];
    }

    /**
     * Get the "after" validation call hooks.
     * This is for complex rules that run *after* basic rules pass.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $user = $this->user();
            $amount = (float)$this->input('amount');

            // Test: test_validates_sufficient_balance
            if ($user->wallet->balance < $amount) {
                $validator->errors()->add('amount', 'Insufficient wallet balance.');
            }

            // Test: test_validates_withdrawal_limit_per_day
            $maxPerDay = setting('max_withdrawal_amount_per_day', 50000);
            
            $withdrawnToday = Withdrawal::where('user_id', $user->id)
                ->where('status', '!=', 'rejected')
                ->whereDate('created_at', today())
                ->sum('amount');
                
            if (($withdrawnToday + $amount) > $maxPerDay) {
                $validator->errors()->add('amount', "This withdrawal exceeds your daily limit of ₹{$maxPerDay}.");
            }
        });
    }

    /**
     * Handle a failed authorization attempt.
     */
    protected function failedAuthorization()
    {
        throw new \Illuminate\Auth\Access\AuthorizationException('KYC must be verified to request a withdrawal.');
    }
}