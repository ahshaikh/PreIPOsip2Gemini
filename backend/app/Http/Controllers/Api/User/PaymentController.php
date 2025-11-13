<?php
// V-FINAL-1730-254 (Consolidated Logic)

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\RazorpayService;
use Illuminate\Http\Request;
use Razorpay\Api\Api;
use Illuminate\Support\Facades\Storage;

class PaymentController extends Controller
{
    protected $razorpayService;

    public function __construct(RazorpayService $razorpayService)
    {
        $this->razorpayService = $razorpayService;
    }

    /**
     * Initiate a payment.
     * Handles both One-Time Orders and Recurring Subscription setups.
     */
    public function initiate(Request $request)
    {
        $validated = $request->validate([
            'payment_id' => 'required|exists:payments,id',
            'enable_auto_debit' => 'nullable|boolean' // Frontend sends this flag
        ]);
        
        $payment = Payment::with('subscription.plan')->findOrFail($validated['payment_id']);
        $user = $request->user();

        // Security check
        if ($payment->user_id !== $user->id || $payment->status !== 'pending') {
            return response()->json(['message' => 'Invalid payment.'], 403);
        }

        // --- Dynamic Limits Check (Gap 2 Fix) ---
        $min = setting('min_payment_amount', 1);
        $max = setting('max_payment_amount', 1000000);
        if ($payment->amount < $min || $payment->amount > $max) {
             return response()->json(['message' => "Payment amount must be between ₹$min and ₹$max."], 400);
        }
        // ----------------------------------------

        $plan = $payment->subscription->plan;
        $isAutoDebit = $request->input('enable_auto_debit', false);

        // --- PATH A: AUTO-DEBIT (SUBSCRIPTION) ---
        if ($isAutoDebit) {
            // 1. Ensure Plan exists on Razorpay
            $rpPlanId = $this->razorpayService->createPlan($plan);
            
            if (!$rpPlanId) {
                return response()->json(['message' => 'Auto-debit unavailable for this plan. Contact support.'], 400);
            }

            // 2. Create Subscription on Razorpay
            $rpSubId = $this->razorpayService->createSubscription(
                $rpPlanId, 
                $plan->duration_months
            );

            // 3. Save to DB
            $payment->subscription->update([
                'is_auto_debit' => true,
                'razorpay_subscription_id' => $rpSubId
            ]);
            
            $payment->update(['gateway_order_id' => $rpSubId]); // Store sub ID as order ID for reference

            return response()->json([
                'type' => 'subscription',
                'subscription_id' => $rpSubId, // Frontend needs this
                'razorpay_key' => env('RAZORPAY_KEY'),
                'name' => 'PreIPO SIP Auto-Debit',
                'description' => 'Setup recurring payment for ' . $plan->name,
                'prefill' => [
                    'name' => $user->profile->first_name . ' ' . $user->profile->last_name,
                    'email' => $user->email,
                    'contact' => $user->mobile,
                ]
            ]);
        }

        // --- PATH B: STANDARD ONE-TIME PAYMENT ---
        $api = new Api(env('RAZORPAY_KEY'), env('RAZORPAY_SECRET'));
        
        $order = $api->order->create([
            'receipt' => 'payment_' . $payment->id,
            'amount' => $payment->amount * 100, // in paise
            'currency' => 'INR',
        ]);
        
        $payment->update(['gateway_order_id' => $order->id]);

        return response()->json([
            'type' => 'order',
            'order_id' => $order->id, // Frontend needs this
            'razorpay_key' => env('RAZORPAY_KEY'),
            'amount' => $payment->amount * 100,
            'name' => 'PreIPO SIP Payment',
            'description' => 'Payment for ' . $plan->name,
            'prefill' => [
                'name' => $user->profile->first_name . ' ' . $user->profile->last_name,
                'email' => $user->email,
                'contact' => $user->mobile,
            ]
        ]);
    }

    /**
     * Submit Manual Payment Proof (UTR + Screenshot)
     */
    public function submitManual(Request $request)
    {
        $validated = $request->validate([
            'payment_id' => 'required|exists:payments,id',
            'utr_number' => 'required|string|max:50',
            'payment_proof' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120', // 5MB max
        ]);

        $payment = Payment::findOrFail($validated['payment_id']);
        $user = $request->user();

        if ($payment->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        if ($payment->status !== 'pending') {
            return response()->json(['message' => 'Payment is not in pending state.'], 400);
        }

        // 1. Upload File
        $path = $request->file('payment_proof')->store("payment_proofs/{$user->id}", 'public');

        // 2. Update Payment Record
        $payment->update([
            'status' => 'pending_approval', // Special status for admin review
            'gateway' => 'manual_transfer',
            'gateway_payment_id' => $validated['utr_number'],
            'payment_proof_path' => $path,
            'paid_at' => now(), // Tentative date
        ]);

        return response()->json(['message' => 'Payment proof submitted successfully. Waiting for admin approval.']);
    }
    
    /**
     * Verify a payment (Stub for frontend logic).
     */
    public function verify(Request $request)
    {
        return response()->json(['message' => 'Payment status is being confirmed.']);
    }
}