<?php
// V-PHASE3-1730-090 (Created) | V-FINAL-1730-426 (Request Validated) | V-REFACTOR-1730-601 (Fat Service Implemented)

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\RazorpayService;
use App\Services\PaymentInitiationService; // <-- NEW SERVICE
use App\Http\Requests\InitiatePaymentRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function __construct(
        protected RazorpayService $razorpayService,
        protected PaymentInitiationService $paymentInitiationService
    ) {}

    /**
     * Initiate a payment via PaymentInitiationService.
     */
    public function initiate(InitiatePaymentRequest $request)
    {
        $validated = $request->validated();
        $payment = Payment::with('subscription.plan')->findOrFail($validated['payment_id']);
        
        // Ownership Check (Double check strictly for security)
        if ($payment->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized payment access.'], 403);
        }

        try {
            $response = $this->paymentInitiationService->initiate(
                $request->user(),
                $payment,
                $request->input('enable_auto_debit', false)
            );

            return response()->json($response);

        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            // Log is already handled in service for critical errors
            return response()->json(['message' => $e->getMessage()], 500);
        }
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

        // Limit Check (Redundant but safe to keep or move to service if desired)
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

        if ($payment->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        if ($payment->status === 'paid') {
            return response()->json(['message' => 'Payment already verified.', 'status' => 'paid']);
        }

        try {
            $isSubscription = !empty($validated['razorpay_subscription_id']);
            $attributes = [
                'razorpay_payment_id' => $validated['razorpay_payment_id'],
                'razorpay_signature' => $validated['razorpay_signature'],
            ];

            if ($isSubscription) {
                $attributes['razorpay_subscription_id'] = $validated['razorpay_subscription_id'];
            } else {
                $attributes['razorpay_order_id'] = $validated['razorpay_order_id'];
            }

            $this->razorpayService->getApi()->utility->verifyPaymentSignature($attributes);

            $payment->update([
                'status' => 'paid',
                'gateway_payment_id' => $validated['razorpay_payment_id'],
                'paid_at' => now(),
            ]);

            if ($payment->subscription && $payment->subscription->status === 'pending') {
                $payment->subscription->update(['status' => 'active']);
            }

            return response()->json([
                'message' => 'Payment verified successfully.',
                'status' => 'paid',
                'payment_id' => $payment->id,
            ]);

        } catch (\Razorpay\Api\Errors\SignatureVerificationError $e) {
            $payment->update(['status' => 'failed']);
            return response()->json(['message' => 'Payment verification failed. Invalid signature.', 'status' => 'failed'], 400);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Payment verification error.', 'status' => 'error'], 500);
        }
    }
}