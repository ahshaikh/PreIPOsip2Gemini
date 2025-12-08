<?php
// V-PHASE3-1730-090 (Created) | V-REMEDIATE-1730-190 | V-FINAL-1730-426 (Request Validated) | V-FINAL-1730-571 (Mandate Logic Added)

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\RazorpayService;
use App\Http\Requests\InitiatePaymentRequest;
use Illuminate\Http\Request;
use Razorpay\Api\Api;
use Illuminate\Support\Facades\Storage;
use App\Models\Plan;
use App\Jobs\ProcessSuccessfulPaymentJob;

class PaymentController extends Controller
{
    protected $razorpayService;

    public function __construct(RazorpayService $razorpayService)
    {
        $this->razorpayService = $razorpayService;
    }

    /**
     * Initiate a payment.
     * Handles both One-Time Orders and Recurring Subscription (Mandate) setups.
     */
    public function initiate(InitiatePaymentRequest $request)
    {
        $validated = $request->validated();
        $payment = Payment::with('subscription.plan')->findOrFail($validated['payment_id']);
        $user = $request->user();
        
        // --- Dynamic Limits Check ---
        $min = setting('min_payment_amount', 1);
        $max = setting('max_payment_amount', 1000000);
        if ($payment->amount < $min || $payment->amount > $max) {
             return response()->json(['message' => "Payment amount must be between ₹$min and ₹$max."], 400);
        }

        $plan = $payment->subscription->plan;
        $isAutoDebit = $request->input('enable_auto_debit', false);

        // --- PATH A: AUTO-DEBIT (SUBSCRIPTION) ---
        if ($isAutoDebit) {
            
            // 1. Ensure Plan exists on Razorpay
            if (!$plan->razorpay_plan_id) {
                try {
                    $this->razorpayService->createOrUpdateRazorpayPlan($plan);
                } catch (\Exception $e) {
                    return response()->json(['message' => 'Payment provider plan setup failed. Please try again.'], 500);
                }
            }

            // 2. Create Razorpay Subscription
            try {
                $razorpaySub = $this->razorpayService->createRazorpaySubscription(
                    $plan->razorpay_plan_id,
                    $user->email,
                    $plan->duration_months
                );
            } catch (\Exception $e) {
                return response()->json(['message' => 'Mandate creation failed. Please try again.'], 500);
            }
            
            // 3. Save Mandate ID to our DB
            $payment->subscription->update([
                'is_auto_debit' => true,
                'razorpay_subscription_id' => $razorpaySub->id
            ]);
            
            $payment->update(['gateway_order_id' => $razorpaySub->id]); // Use Sub ID as Order ID

            // 4. Return Subscription ID (not Order ID) to frontend
            return response()->json([
                'type' => 'subscription',
                'subscription_id' => $razorpaySub->id, // This is the mandate
                'razorpay_key' => setting('razorpay_key_id', env('RAZORPAY_KEY')),
                'name' => $plan->name . ' (Auto-Debit)',
                'description' => 'Setup recurring monthly payment',
                'prefill' => [
                    'name' => $user->profile->first_name . ' ' . $user->profile->last_name,
                    'email' => $user->email,
                    'contact' => $user->mobile,
                ]
            ]);
        }

        // --- PATH B: STANDARD ONE-TIME PAYMENT ---
        try {
            $order = $this->razorpayService->createOrder($payment->amount, 'payment_' . $payment->id);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Payment gateway failed. Please try again.'], 500);
        }
        
        $payment->update(['gateway_order_id' => $order->id]);

        return response()->json([
            'type' => 'order',
            'order_id' => $order->id,
            'razorpay_key' => setting('razorpay_key_id', env('RAZORPAY_KEY')),
            'amount' => $payment->amount * 100,
            'name' => 'PreIPO SIP Payment',
            'description' => 'One-time payment for ' . $plan->name,
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
            'payment_proof' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        $payment = Payment::findOrFail($validated['payment_id']);
        $user = $request->user();

        if ($payment->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        if ($payment->status !== 'pending') {
            return response()->json(['message' => 'Payment is not in pending state.'], 400);
        }

        // --- SECURITY: Validate amount limits on manual payments too ---
        $min = setting('min_payment_amount', 1);
        $max = setting('max_payment_amount', 1000000);
        if ($payment->amount < $min || $payment->amount > $max) {
            return response()->json(['message' => "Payment amount must be between ₹$min and ₹$max."], 400);
        }

        $path = $request->file('payment_proof')->store("payment_proofs/{$user->id}", 'public');

        $payment->update([
            'status' => 'pending_approval',
            'gateway' => 'manual_transfer',
            'gateway_payment_id' => $validated['utr_number'],
            'payment_proof_path' => $path,
            'paid_at' => now(),
        ]);

        return response()->json(['message' => 'Payment proof submitted successfully. Waiting for admin approval.']);
    }
    
    /**
     * Verify a Razorpay payment after completion.
     * Validates the payment signature and updates the payment status.
     */
    public function verify(Request $request)
    {
        $validated = $request->validate([
            'payment_id' => 'required|exists:payments,id',
            'razorpay_payment_id' => 'required|string',
            'razorpay_order_id' => 'required_without:razorpay_subscription_id|string|nullable',
            'razorpay_subscription_id' => 'required_without:razorpay_order_id|string|nullable',
            'razorpay_signature' => 'required|string',
        ]);

        $payment = Payment::with('subscription')->findOrFail($validated['payment_id']);
        $user = $request->user();

        // Verify ownership
        if ($payment->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        // Prevent double processing
        if ($payment->status === 'paid') {
            return response()->json(['message' => 'Payment already verified.', 'status' => 'paid']);
        }

        try {
            // Determine if this is an order or subscription payment
            $isSubscription = !empty($validated['razorpay_subscription_id']);

            if ($isSubscription) {
                // Verify subscription payment signature
                $attributes = [
                    'razorpay_payment_id' => $validated['razorpay_payment_id'],
                    'razorpay_subscription_id' => $validated['razorpay_subscription_id'],
                    'razorpay_signature' => $validated['razorpay_signature'],
                ];
                $this->razorpayService->getApi()->utility->verifyPaymentSignature($attributes);
            } else {
                // Verify order payment signature
                $attributes = [
                    'razorpay_order_id' => $validated['razorpay_order_id'],
                    'razorpay_payment_id' => $validated['razorpay_payment_id'],
                    'razorpay_signature' => $validated['razorpay_signature'],
                ];
                $this->razorpayService->getApi()->utility->verifyPaymentSignature($attributes);
            }

            // Signature verified - update payment
            $payment->update([
                'status' => 'paid',
                'gateway_payment_id' => $validated['razorpay_payment_id'],
                'paid_at' => now(),
            ]);

            // If subscription exists, update its status
            if ($payment->subscription && $payment->subscription->status === 'pending') {
                $payment->subscription->update(['status' => 'active']);
            }

            // Dispatch job to process bonuses, allocations, and wallet updates
            ProcessSuccessfulPaymentJob::dispatch($payment);

            return response()->json([
                'message' => 'Payment verified successfully.',
                'status' => 'paid',
                'payment_id' => $payment->id,
            ]);

        } catch (\Razorpay\Api\Errors\SignatureVerificationError $e) {
            // Signature verification failed
            $payment->update(['status' => 'failed']);

            return response()->json([
                'message' => 'Payment verification failed. Invalid signature.',
                'status' => 'failed',
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Payment verification error. Please contact support.',
                'status' => 'error',
            ], 500);
        }
    }
}