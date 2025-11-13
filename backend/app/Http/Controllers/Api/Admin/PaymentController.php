<?php
// V-FINAL-1730-259 (Fraud Management Added)

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\User;
use App\Models\Transaction;
use App\Jobs\ProcessSuccessfulPaymentJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $query = Payment::with(['user:id,username,email', 'subscription.plan:id,name']);

        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }
        
        // --- NEW: Filter for Flagged ---
        if ($request->query('flagged') === 'true') {
            $query->where('is_flagged', true);
        }
        // ------------------------------

        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('user', function($q) use ($search) {
                $q->where('username', 'like', "%{$search}%");
            })->orWhere('gateway_payment_id', 'like', "%{$search}%");
        }

        // Prioritize Flagged & Pending
        return $query->orderBy('is_flagged', 'desc')
                     ->orderByRaw("FIELD(status, 'pending_approval') DESC")
                     ->latest()
                     ->paginate(20);
    }

    // ... (storeOffline remains same) ...

    /**
     * Approve a Payment (Manual or Flagged)
     */
    public function approveManual(Request $request, Payment $payment)
    {
        if ($payment->status !== 'pending_approval') {
            return response()->json(['message' => 'Payment is not pending approval.'], 400);
        }

        DB::beginTransaction();
        try {
            $payment->update([
                'status' => 'paid',
                'is_on_time' => true,
                'is_flagged' => false, // Clear flag
                'flag_reason' => null
            ]);

            ProcessSuccessfulPaymentJob::dispatch($payment);

            DB::commit();
            return response()->json(['message' => 'Payment approved and processed successfully.']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    // ... (rejectManual and refund remain same) ...
    public function rejectManual(Request $request, Payment $payment) { /* ... */ }
    public function refund(Request $request, Payment $payment) { /* ... */ }
}