<?php
// V-PHASE3-1730-090

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;
use Razorpay\Api\Api; // Example with Razorpay

class PaymentController extends Controller
{
    /**
     * Initiate a payment for a pending payment record.
     */
    public function initiate(Request $request)
    {
        $validated = $request->validate([
            'payment_id' => 'required|exists:payments,id',
        ]);
        
        $payment = Payment::findOrFail($validated['payment_id']);
        $user = $request->user();

        if ($payment->user_id !== $user->id || $payment->status !== 'pending') {
            return response()->json(['message' => 'Invalid payment.'], 403);
        }
        
        // --- Razorpay API Call ---
        $api = new Api(env('RAZORPAY_KEY'), env('RAZORPAY_SECRET'));
        
        $order = $api->order->create([
            'receipt' => 'payment_' . $payment->id,
            'amount' => $payment->amount * 100, // Amount in paise
            'currency' => 'INR',
        ]);
        
        $payment->update(['gateway_order_id' => $order->id]);

        return response()->json([
            'order_id' => $order->id,
            'razorpay_key' => env('RAZORPAY_KEY'),
            'amount' => $payment->amount * 100,
            'name' => 'PreIPO SIP Payment',
            'description' => 'Payment for ' . $payment->subscription->plan->name,
            'prefill' => [
                'name' => $user->profile->first_name . ' ' . $user->profile->last_name,
                'email' => $user->email,
                'contact' => $user->mobile,
            ]
        ]);
    }
    
    /**
     * Verify a payment after it's completed on the frontend.
     * This is a fallback, the webhook is the primary verification.
     */
    public function verify(Request $request)
    {
        // ... Verification logic ...
        return response()->json(['message' => 'Payment status is being confirmed.']);
    }
}