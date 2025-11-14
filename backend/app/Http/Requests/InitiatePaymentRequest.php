<?php
// V-FINAL-1730-425 (Created)

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Payment;
use Illuminate\Support\Facades\Log;

class InitiatePaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * This checks test_validates_user_owns_subscription.
     */
    public function authorize(): bool
    {
        $payment = Payment::find($this->input('payment_id'));

        if (!$payment) {
            return true; // Let the 'exists' rule handle the 422
        }
        
        $authorized = $payment->user_id === $this->user()->id;
        
        if (!$authorized) {
            Log::warning("AuthZ Failure: User {$this->user()->id} tried to access Payment {$payment->id}");
        }

        return $authorized;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // Test: test_validates_subscription_exists (via payment)
            'payment_id' => 'required|exists:payments,id',
            'enable_auto_debit' => 'nullable|boolean'
        ];
    }

    /**
     * Handle a failed authorization attempt.
     */
    protected function failedAuthorization()
    {
        // This stops the request with a 403 Forbidden
        throw new \Illuminate\Auth\Access\AuthorizationException('This payment does not belong to you.');
    }
}