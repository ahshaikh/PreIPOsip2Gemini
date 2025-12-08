<?php
// V-FINAL-1730-259 (Fraud Management Added) | V-FINAL-1730-568 (Created) | V-FINAL-1730-587 (V2.0 Refund)

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\User;
use App\Models\Transaction;
use App\Jobs\ProcessSuccessfulPaymentJob;
use App\Services\WalletService; // <-- IMPORT
use App\Services\AllocationService; // <-- IMPORT
use App\Services\RazorpayService; // <-- IMPORT
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    protected $walletService;
    protected $allocationService;
    protected $razorpayService;

    public function __construct(WalletService $walletService, AllocationService $allocationService, RazorpayService $razorpayService)
    {
        $this->walletService = $walletService;
        $this->allocationService = $allocationService;
        $this->razorpayService = $razorpayService;
    }

    /**
     * View All Payments with Filters.
     */
    public function index(Request $request)
    {
        $query = Payment::with(['user:id,username,email', 'subscription.plan:id,name']);

        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }
        if ($request->query('flagged') === 'true') {
            $query->where('is_flagged', true);
        }
        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('user', function($q) use ($search) {
                $q->where('username', 'like', "%{$search}%");
            })->orWhere('gateway_payment_id', 'like', "%{$search}%");
        }

        return $query->orderBy('is_flagged', 'desc')
                     ->orderByRaw("FIELD(status, 'pending_approval') DESC")
                     ->latest()
                     ->paginate(20);
    }

    /**
     * Manual Payment Entry (Offline Payments by Admin).
     */
    public function storeOffline(Request $request)
    {
        $min = setting('min_payment_amount', 1);
        $max = setting('max_payment_amount', 1000000);

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'amount' => "required|numeric|min:$min|max:$max",
            'payment_date' => 'required|date',
            'reference_id' => 'required|string|max:255',
            'method' => 'required|string',
        ]);

        $user = User::findOrFail($validated['user_id']);
        $subscription = $user->subscription()->where('status', 'active')->first();
        if (!$subscription) {
            return response()->json(['message' => 'User has no active subscription.'], 400);
        }

        try {
            DB::transaction(function () use ($user, $subscription, $validated) {
                $payment = Payment::create([
                    'user_id' => $user->id,
                    'subscription_id' => $subscription->id,
                    'amount' => $validated['amount'],
                    'status' => 'paid',
                    'gateway' => 'offline',
                    'gateway_payment_id' => $validated['reference_id'],
                    'gateway_order_id' => 'OFF-' . time(),
                    'paid_at' => $validated['payment_date'],
                    'is_on_time' => true,
                    'payment_type' => 'sip_installment',
                ]);
    
                ProcessSuccessfulPaymentJob::dispatch($payment);
            });
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error recording payment: ' . $e->getMessage()], 500);
        }
            
        return response()->json(['message' => 'Offline payment recorded and processed.'], 201);
    }

    /**
     * Approve a User-Submitted Manual Payment or Flagged Payment.
     */
    public function approveManual(Request $request, Payment $payment)
    {
        if ($payment->status !== 'pending_approval') {
            return response()->json(['message' => 'Payment is not pending approval.'], 400);
        }

        DB::transaction(function () use ($payment) {
            $payment->update([
                'status' => 'paid',
                'is_on_time' => true,
                'is_flagged' => false,
                'flag_reason' => null
            ]);
            ProcessSuccessfulPaymentJob::dispatch($payment);
        });

        return response()->json(['message' => 'Payment approved and processed.']);
    }

    /**
     * Reject a User-Submitted Manual Payment.
     */
    public function rejectManual(Request $request, Payment $payment)
    {
        if ($payment->status !== 'pending_approval') {
            return response()->json(['message' => 'Payment is not pending approval.'], 400);
        }
        $payment->update(['status' => 'failed', 'failure_reason' => 'Admin Rejected']);
        return response()->json(['message' => 'Payment rejected.']);
    }

    /**
     * FSD-PAY-007: V2.0 Refund (with Bonus/Allocation Reversal)
     */
    public function refund(Request $request, Payment $payment)
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:255',
            'reverse_bonuses' => 'required|boolean',
            'reverse_allocations' => 'required|boolean',
        ]);

        if ($payment->status !== 'paid') {
            return response()->json(['message' => 'Only paid payments can be refunded.'], 400);
        }

        try {
            DB::transaction(function () use ($payment, $validated) {
                $user = $payment->user;
                $reason = $validated['reason'];

                // 0. Process Gateway Refund (if applicable)
                if (in_array($payment->gateway, ['razorpay', 'razorpay_auto']) && $payment->gateway_payment_id) {
                    try {
                        $this->razorpayService->refundPayment($payment->gateway_payment_id, $payment->amount);
                    } catch (\Exception $e) {
                        // Log but don't block internal refund
                        \Log::error("Razorpay refund failed for Payment #{$payment->id}: " . $e->getMessage());
                        // Optionally: throw $e; to require gateway refund success
                    }
                }

                // 1. Reverse Bonuses (if checked)
                if ($validated['reverse_bonuses']) {
                    $bonuses = $payment->bonuses()->get();
                    foreach ($bonuses as $bonus) {
                        // Create negative bonus transaction
                        $bonus->reverse($reason);
                        // Debit wallet (securely)
                        $this->walletService->withdraw($user, $bonus->net_amount, 'reversal', "Reversal: {$reason}", $bonus);
                    }
                }

                // 2. Reverse Allocations (if checked)
                if ($validated['reverse_allocations']) {
                    $this->allocationService->reverseAllocation($payment, $reason);
                }

                // 3. Refund the original payment amount to user's wallet
                $this->walletService->deposit(
                    $user,
                    $payment->amount,
                    'refund',
                    "Refund for Payment #{$payment->id}: {$reason}",
                    $payment
                );

                // 4. Mark payment as refunded
                $payment->update(['status' => 'refunded']);
            });

        } catch (\Exception $e) {
            return response()->json(['message' => 'Error processing refund: ' . $e->getMessage()], 500);
        }

        return response()->json(['message' => 'Payment refunded and all actions reversed.']);
    }
}