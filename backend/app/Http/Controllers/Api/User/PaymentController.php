<?php
// V-PHASE3-1730-090 (Created) | V-FINAL-1730-426 (Request Validated) | V-REFACTOR-1730-601 (Fat Service) | V-AUDIT-FIX-MODULE8 (Race Condition)

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\RazorpayService;
use App\Services\PaymentInitiationService;
use App\Services\PaymentWebhookService; // <-- Added
use App\Http\Requests\InitiatePaymentRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function __construct(
        protected RazorpayService $razorpayService,
        protected PaymentInitiationService $paymentInitiationService,
        protected PaymentWebhookService $paymentWebhookService // <-- Injected
    ) {}

    /**
     * Initiate a payment via PaymentInitiationService.
     */
    public function initiate(InitiatePaymentRequest $request)
    {
        $validated = $request->validated();
        $payment = Payment::with('subscription.plan')->findOrFail($validated['payment_id']);
        
        // Ownership Check
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

        $min = setting('min_payment_amount', 1);
        $max = setting('max_payment_amount', 1000000);
        if ($payment->amount < $min || $payment->amount > $max) {
            return response()->json(['message' => "Payment amount must be between ₹$min and ₹$max."], 400);
        }

        $path = $request->file('payment_proof')->store("payment_proofs/{$user->id}", 'public');

        // MODULE 8 FIX: Ensure upload was successful
        if (!$path) {
            return response()->json(['message' => 'Failed to upload payment proof. Please try again.'], 500);
        }

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
     * MODULE 8 FIX: Uses PaymentWebhookService to fulfill payment safely.
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
        
        if ($payment->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        // If already paid, return success immediately
        if ($payment->status === 'paid') {
            return response()->json(['message' => 'Payment already verified.', 'status' => 'paid']);
        }

        try {
            // 1. Verify Signature locally
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

            // 2. Fulfill Payment using Service (Handles Race Condition & Logic)
            // This is the critical fix from Module 8.
            $this->paymentWebhookService->fulfillPayment(
                $payment, 
                $validated['razorpay_payment_id']
            );

            return response()->json([
                'message' => 'Payment verified successfully.',
                'status' => 'paid',
                'payment_id' => $payment->id,
            ]);

        } catch (\Razorpay\Api\Errors\SignatureVerificationError $e) {
            $payment->update(['status' => 'failed']);
            return response()->json(['message' => 'Payment verification failed. Invalid signature.', 'status' => 'failed'], 400);
        } catch (\Exception $e) {
            Log::error("Payment Verification Error: " . $e->getMessage());
            return response()->json(['message' => 'Payment verification error.', 'status' => 'error'], 500);
        }
    }
}