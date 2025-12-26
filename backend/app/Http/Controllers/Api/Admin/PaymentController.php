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
            'reason' => 'nullable|string|max:255',
            'reverse_bonuses' => 'nullable|boolean',
            'reverse_allocations' => 'nullable|boolean',
        ]);

        // Set defaults if not provided
        $validated['reason'] = $validated['reason'] ?? 'Admin initiated refund';
        $validated['reverse_bonuses'] = $validated['reverse_bonuses'] ?? true;
        $validated['reverse_allocations'] = $validated['reverse_allocations'] ?? true;

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

    /**
     * Get payment details with full information
     * GET /api/v1/admin/payments/{payment}
     */
    public function show(Payment $payment)
    {
        $payment->load([
            'user:id,username,email,mobile',
            'subscription.plan:id,name,monthly_amount',
            'bonuses',
        ]);

        // Get related transactions
        $transactions = \App\Models\Transaction::where('reference_type', Payment::class)
            ->where('reference_id', $payment->id)
            ->get();

        return response()->json([
            'payment' => $payment,
            'transactions' => $transactions,
        ]);
    }

    /**
     * Get payment gateway settings
     * GET /api/v1/admin/payment-gateways
     */
    public function getGatewaySettings()
    {
        $gateways = [
            'razorpay' => [
                'enabled' => setting('payment_gateway_razorpay_enabled', true),
                'key' => setting('payment_gateway_razorpay_key', ''),
                'secret_set' => !empty(setting('payment_gateway_razorpay_secret', '')),
            ],
            'stripe' => [
                'enabled' => setting('payment_gateway_stripe_enabled', false),
                'key' => setting('payment_gateway_stripe_key', ''),
                'secret_set' => !empty(setting('payment_gateway_stripe_secret', '')),
            ],
            'paytm' => [
                'enabled' => setting('payment_gateway_paytm_enabled', false),
            ],
        ];

        return response()->json(['gateways' => $gateways]);
    }

    /**
     * Update payment gateway settings
     * PUT /api/v1/admin/payment-gateways
     */
    public function updateGatewaySettings(Request $request)
    {
        $validated = $request->validate([
            'gateway' => 'required|in:razorpay,stripe,paytm',
            'enabled' => 'required|boolean',
            'key' => 'nullable|string',
            'secret' => 'nullable|string',
        ]);

        $gateway = $validated['gateway'];

        // Update settings
        \App\Models\Setting::updateOrCreate(
            ['key' => "payment_gateway_{$gateway}_enabled"],
            ['value' => $validated['enabled'], 'group' => 'payment_gateway']
        );

        if (isset($validated['key'])) {
            \App\Models\Setting::updateOrCreate(
                ['key' => "payment_gateway_{$gateway}_key"],
                ['value' => $validated['key'], 'group' => 'payment_gateway']
            );
        }

        if (isset($validated['secret'])) {
            \App\Models\Setting::updateOrCreate(
                ['key' => "payment_gateway_{$gateway}_secret"],
                ['value' => $validated['secret'], 'group' => 'payment_gateway']
            );
        }

        // Clear cache
        \Illuminate\Support\Facades\Cache::forget('settings');

        return response()->json(['message' => 'Gateway settings updated successfully']);
    }

    /**
     * Get payment method settings
     * GET /api/v1/admin/payment-methods
     */
    public function getMethodSettings()
    {
        $methods = [
            'upi' => [
                'enabled' => setting('payment_method_upi_enabled', true),
                'fee' => setting('payment_method_upi_fee', 0),
                'fee_percent' => setting('payment_method_upi_fee_percent', 0),
            ],
            'card' => [
                'enabled' => setting('payment_method_card_enabled', true),
                'fee' => setting('payment_method_card_fee', 0),
                'fee_percent' => setting('payment_method_card_fee_percent', 2),
            ],
            'netbanking' => [
                'enabled' => setting('payment_method_netbanking_enabled', true),
                'fee' => setting('payment_method_netbanking_fee', 0),
                'fee_percent' => setting('payment_method_netbanking_fee_percent', 1),
            ],
            'wallet' => [
                'enabled' => setting('payment_method_wallet_enabled', true),
            ],
        ];

        return response()->json(['methods' => $methods]);
    }

    /**
     * Update payment method settings
     * PUT /api/v1/admin/payment-methods
     */
    public function updateMethodSettings(Request $request)
    {
        $validated = $request->validate([
            'method' => 'required|in:upi,card,netbanking,wallet',
            'enabled' => 'required|boolean',
            'fee' => 'nullable|numeric|min:0',
            'fee_percent' => 'nullable|numeric|min:0|max:100',
        ]);

        $method = $validated['method'];

        \App\Models\Setting::updateOrCreate(
            ['key' => "payment_method_{$method}_enabled"],
            ['value' => $validated['enabled'], 'group' => 'payment_methods']
        );

        if (isset($validated['fee'])) {
            \App\Models\Setting::updateOrCreate(
                ['key' => "payment_method_{$method}_fee"],
                ['value' => $validated['fee'], 'group' => 'payment_methods']
            );
        }

        if (isset($validated['fee_percent'])) {
            \App\Models\Setting::updateOrCreate(
                ['key' => "payment_method_{$method}_fee_percent"],
                ['value' => $validated['fee_percent'], 'group' => 'payment_methods']
            );
        }

        \Illuminate\Support\Facades\Cache::forget('settings');

        return response()->json(['message' => 'Payment method settings updated successfully']);
    }

    /**
     * Get auto-debit configuration
     * GET /api/v1/admin/auto-debit-config
     */
    public function getAutoDebitConfig()
    {
        $config = [
            'enabled' => setting('auto_debit_enabled', true),
            'max_retries' => setting('auto_debit_max_retries', 3),
            'retry_interval_days' => setting('auto_debit_retry_interval_days', 1),
            'reminder_days' => setting('auto_debit_reminder_days', 3),
            'suspend_after_max_retries' => setting('auto_debit_suspend_after_max_retries', true),
        ];

        return response()->json(['config' => $config]);
    }

    /**
     * Update auto-debit configuration
     * PUT /api/v1/admin/auto-debit-config
     */
    public function updateAutoDebitConfig(Request $request)
    {
        $validated = $request->validate([
            'enabled' => 'required|boolean',
            'max_retries' => 'required|integer|min:1|max:10',
            'retry_interval_days' => 'required|integer|min:1|max:7',
            'reminder_days' => 'required|integer|min:1|max:7',
            'suspend_after_max_retries' => 'required|boolean',
        ]);

        foreach ($validated as $key => $value) {
            \App\Models\Setting::updateOrCreate(
                ['key' => "auto_debit_{$key}"],
                ['value' => $value, 'group' => 'auto_debit']
            );
        }

        \Illuminate\Support\Facades\Cache::forget('settings');

        return response()->json(['message' => 'Auto-debit configuration updated successfully']);
    }

    /**
     * View failed payments for retry management
     * GET /api/v1/admin/payments/failed
     */
    public function failedPayments(Request $request)
    {
        $query = Payment::where('status', 'failed')
            ->with(['user:id,username,email', 'subscription.plan:id,name']);

        if ($request->has('retry_count')) {
            $query->where('retry_count', $request->retry_count);
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        return $query->latest()->paginate(25);
    }

    /**
     * Retry a failed payment
     * POST /api/v1/admin/payments/{payment}/retry
     */
    public function retryPayment(Payment $payment)
    {
        if ($payment->status !== 'failed') {
            return response()->json(['message' => 'Only failed payments can be retried'], 400);
        }

        // Dispatch retry job
        \App\Jobs\RetryAutoDebitJob::dispatch($payment);

        return response()->json(['message' => 'Payment retry initiated']);
    }

    /**
     * Payment analytics dashboard
     * GET /api/v1/admin/payments/analytics
     */
    public function analytics(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $startDate = $validated['start_date'] ?? now()->subDays(30);
        $endDate = $validated['end_date'] ?? now();

        $query = Payment::whereBetween('created_at', [$startDate, $endDate]);

        $analytics = [
            'total_payments' => $query->count(),
            'total_amount' => $query->where('status', 'paid')->sum('amount'),
            'by_status' => Payment::whereBetween('created_at', [$startDate, $endDate])
                ->selectRaw('status, count(*) as count, sum(amount) as total')
                ->groupBy('status')
                ->get(),
            'by_gateway' => Payment::whereBetween('created_at', [$startDate, $endDate])
                ->where('status', 'paid')
                ->selectRaw('gateway, count(*) as count, sum(amount) as total')
                ->groupBy('gateway')
                ->get(),
            'by_method' => Payment::whereBetween('created_at', [$startDate, $endDate])
                ->where('status', 'paid')
                ->selectRaw('method, count(*) as count, sum(amount) as total')
                ->groupBy('method')
                ->get(),
            'daily_trend' => Payment::whereBetween('created_at', [$startDate, $endDate])
                ->where('status', 'paid')
                ->selectRaw('DATE(created_at) as date, count(*) as count, sum(amount) as total')
                ->groupBy('date')
                ->orderBy('date')
                ->get(),
            'success_rate' => [
                'total' => Payment::whereBetween('created_at', [$startDate, $endDate])->count(),
                'successful' => Payment::whereBetween('created_at', [$startDate, $endDate])
                    ->where('status', 'paid')->count(),
                'failed' => Payment::whereBetween('created_at', [$startDate, $endDate])
                    ->where('status', 'failed')->count(),
            ],
            'avg_payment_amount' => $query->where('status', 'paid')->avg('amount'),
            'refunded_payments' => Payment::whereBetween('created_at', [$startDate, $endDate])
                ->where('status', 'refunded')
                ->count(),
            'refunded_amount' => Payment::whereBetween('created_at', [$startDate, $endDate])
                ->where('status', 'refunded')
                ->sum('amount'),
        ];

        return response()->json(['analytics' => $analytics]);
    }

    /**
     * Export payments to CSV
     * GET /api/v1/admin/payments/export
     */
    public function export(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'status' => 'nullable|in:pending,paid,failed,refunded',
        ]);

        $query = Payment::with(['user:id,username,email', 'subscription.plan:id,name']);

        if (isset($validated['start_date'])) {
            $query->whereDate('created_at', '>=', $validated['start_date']);
        }

        if (isset($validated['end_date'])) {
            $query->whereDate('created_at', '<=', $validated['end_date']);
        }

        if (isset($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        $payments = $query->latest()->get();

        $csv = "ID,User,Email,Plan,Amount,Status,Gateway,Method,Paid At,Created At\n";
        foreach ($payments as $payment) {
            $csv .= implode(',', [
                $payment->id,
                $payment->user->username ?? 'N/A',
                $payment->user->email ?? 'N/A',
                $payment->subscription->plan->name ?? 'N/A',
                $payment->amount,
                $payment->status,
                $payment->gateway ?? 'N/A',
                $payment->method ?? 'N/A',
                $payment->paid_at?->format('Y-m-d H:i:s') ?? 'N/A',
                $payment->created_at->format('Y-m-d H:i:s'),
            ]) . "\n";
        }

        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="payments_export_' . now()->format('Y-m-d') . '.csv"');
    }
}