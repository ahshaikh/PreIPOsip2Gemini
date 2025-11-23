<?php
// V-SECURITY-VALIDATION - Payment Initiation Request Validation

namespace App\Http\Requests\Financial;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InitiatePaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // User must be authenticated and KYC verified for payments
        $user = $this->user();

        if (!$user) {
            return false;
        }

        // Check if KYC is required for payments
        if (setting('require_kyc_for_payment', true) && $user->kyc_status !== 'approved') {
            return false;
        }

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $minAmount = (float) setting('min_payment_amount', 100);
        $maxAmount = (float) setting('max_payment_amount', 10000000);

        return [
            'amount' => [
                'required',
                'numeric',
                "min:{$minAmount}",
                "max:{$maxAmount}",
                'regex:/^\d+(\.\d{1,2})?$/', // Max 2 decimal places
            ],
            'plan_id' => [
                'required_without:subscription_id',
                'nullable',
                'integer',
                Rule::exists('plans', 'id')->where('is_active', true),
            ],
            'subscription_id' => [
                'required_without:plan_id',
                'nullable',
                'integer',
                Rule::exists('subscriptions', 'id')->where('user_id', $this->user()->id),
            ],
            'payment_method' => [
                'required',
                'string',
                Rule::in(['razorpay', 'upi', 'netbanking', 'card', 'wallet', 'manual']),
            ],
            'coupon_code' => [
                'nullable',
                'string',
                'max:50',
                'alpha_dash',
            ],
            'notes' => [
                'nullable',
                'string',
                'max:500',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'amount.min' => 'Minimum payment amount is ₹' . setting('min_payment_amount', 100),
            'amount.max' => 'Maximum payment amount is ₹' . number_format(setting('max_payment_amount', 10000000)),
            'amount.regex' => 'Amount can have maximum 2 decimal places.',
            'plan_id.exists' => 'Selected plan is not available.',
            'subscription_id.exists' => 'Subscription not found or does not belong to you.',
            'payment_method.in' => 'Invalid payment method selected.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Sanitize amount - remove any currency symbols or commas
        if ($this->has('amount')) {
            $amount = preg_replace('/[^0-9.]/', '', $this->amount);
            $this->merge(['amount' => $amount]);
        }
    }
}
