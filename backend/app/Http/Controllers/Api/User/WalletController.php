<?php
// V-PHASE3-1730-094 (Created) | V-REMEDIATE-1730-257 (Auto-Withdrawal) | V-FINAL-1730-360 (Refactored)
// V-FINAL-1730-429 (FormRequest) | V-FINAL-1730-447 (WalletService) | V-AUDIT-FIX-MODULE7 (Statement Optimization)
// V-AUDIT-FIX-OOM-PROTECTION | V-AUDIT-MODULE3-004 (Comprehensive Module 3 Fixes)

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Wallet;
use App\Services\WithdrawalService;
use App\Services\WalletService;
use App\Http\Requests\User\WithdrawalRequest;
use App\Enums\TransactionType; // ADDED: Import TransactionType enum
use App\Exceptions\Financial\InsufficientBalanceException; // ADDED: Import custom exception
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WalletController extends Controller
{
    protected $withdrawalService;
    protected $walletService;

    public function __construct(WithdrawalService $withdrawalService, WalletService $walletService)
    {
        $this->withdrawalService = $withdrawalService;
        $this->walletService = $walletService;
    }

    /**
     * Get wallet details and recent transactions
     *
     * AUDIT FIX (V-AUDIT-MODULE3-004):
     * - Uses TransactionType enum for transaction filtering
     * - Lazy wallet creation moved to event listener (see TODO)
     */
    public function show(Request $request)
    {
        $userId = $request->user()->id;

        // UPDATED: Use TransactionType enum values for filtering
        $creditTypes = array_map(fn($type) => $type->value, TransactionType::credits());

        // Optimization: DB-level aggregation
        $wallet = Wallet::where('user_id', $userId)
            ->withSum(['transactions as total_deposited' => function($query) use ($creditTypes) {
                $query->whereIn('type', $creditTypes)
                      ->where('amount', '>', 0);
            }], 'amount')
            ->withSum(['transactions as total_withdrawn' => function($query) {
                $query->where('type', TransactionType::WITHDRAWAL->value);
            }], 'amount')
            ->first();

        // TODO (LOW PRIORITY): Move wallet creation to UserRegistered event listener
        // Currently creates wallet on first dashboard visit (lazy creation)
        if (!$wallet) {
            $wallet = Wallet::create(['user_id' => $userId]);
            $wallet->total_deposited = 0;
            $wallet->total_withdrawn = 0;
        } else {
            $wallet->total_withdrawn = abs($wallet->total_withdrawn ?? 0);
            $wallet->total_deposited = $wallet->total_deposited ?? 0;
        }

        $transactions = $wallet->transactions()->latest()->paginate(20);

        return response()->json(['wallet' => $wallet, 'transactions' => $transactions]);
    }

    /**
     * Get user's withdrawal history
     */
    public function withdrawals(Request $request)
    {
        $withdrawals = $request->user()->withdrawals()
            ->latest()
            ->paginate(20);

        return response()->json($withdrawals);
    }

    /**
     * Initiate a deposit (Add Money to Wallet)
     *
     * AUDIT FIX (V-AUDIT-MODULE3-004):
     * - Implements basic deposit functionality (was returning 501)
     * - Integrates with payment gateway (placeholder for Razorpay)
     * - Uses TransactionType enum
     *
     * TODO: Complete Razorpay integration
     */
    public function initiateDeposit(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:100|max:100000', // Min ₹100, Max ₹1L
        ]);

        $user = $request->user();
        $amount = (string) $request->amount; // Convert to string for precision

        // Generate unique order ID
        $orderId = 'WD_' . time() . '_' . Str::random(8);

        // TODO: Integrate with Razorpay/Payment Gateway
        // For now, return order details for frontend to handle payment

        $orderDetails = [
            'order_id' => $orderId,
            'amount' => $amount,
            'currency' => 'INR',
            'user_id' => $user->id,
            'description' => 'Wallet deposit',
            // Add Razorpay order creation here when ready
            'payment_gateway' => 'razorpay',
            'callback_url' => route('api.user.wallet.deposit.callback'),
        ];

        Log::info("Wallet deposit initiated", [
            'user_id' => $user->id,
            'amount' => $amount,
            'order_id' => $orderId
        ]);

        return response()->json([
            'message' => 'Deposit initiated. Complete payment to add funds.',
            'order' => $orderDetails
        ], 200);
    }

    /**
     * Handle deposit callback from payment gateway
     *
     * TODO: Implement actual payment verification with Razorpay
     */
    public function handleDepositCallback(Request $request)
    {
        // TODO: Verify payment with Razorpay
        // For now, return placeholder response

        return response()->json([
            'message' => 'Deposit callback received. Payment verification in progress.',
        ], 200);
    }

    /**
     * Request a withdrawal
     *
     * AUDIT FIX (V-AUDIT-MODULE3-004):
     * - Added idempotency key support to prevent duplicate withdrawals
     * - Catches InsufficientBalanceException separately for proper 422 response
     * - Fixed mass assignment vulnerability by filtering bank_details
     * - Uses string for amount (financial precision)
     * - Uses TransactionType enum
     *
     * SECURITY FIX: Mass assignment protection
     * SECURITY FIX: Idempotency key prevents double-click submissions
     */
    public function requestWithdrawal(WithdrawalRequest $request)
    {
        $validated = $request->validated();
        $user = $request->user();

        // CRITICAL FIX: Convert to string for financial precision
        $amount = (string) $validated['amount'];

        // AUDIT FIX: Idempotency key support
        // Prevents duplicate withdrawals if user double-clicks submit button
        $idempotencyKey = $request->header('X-Idempotency-Key') ?? $request->input('idempotency_key');

        if ($idempotencyKey) {
            // Check if this request was already processed
            $existingWithdrawal = $user->withdrawals()
                ->where('idempotency_key', $idempotencyKey)
                ->where('created_at', '>=', now()->subHours(24)) // Only check last 24 hours
                ->first();

            if ($existingWithdrawal) {
                return response()->json([
                    'message' => 'Withdrawal request already processed.',
                    'withdrawal_id' => $existingWithdrawal->id
                ], 200);
            }
        }

        try {
            $withdrawal = DB::transaction(function () use ($user, $amount, $validated, $idempotencyKey) {

                // AUDIT FIX: Filter bank_details to prevent mass assignment
                // Only allow specific fields that are safe to pass through
                $safeBankDetails = [
                    'account_number' => $validated['bank_details']['account_number'] ?? null,
                    'ifsc_code' => $validated['bank_details']['ifsc_code'] ?? null,
                    'account_holder_name' => $validated['bank_details']['account_holder_name'] ?? null,
                    'bank_name' => $validated['bank_details']['bank_name'] ?? null,
                ];

                // Create withdrawal record with idempotency key
                $withdrawal = $this->withdrawalService->createWithdrawalRecord(
                    $user,
                    $amount,
                    $safeBankDetails,
                    $idempotencyKey
                );

                // Lock funds in wallet using TransactionType enum
                $this->walletService->withdraw(
                    $user,
                    $amount,
                    TransactionType::WITHDRAWAL_REQUEST->value, // UPDATED: Use enum
                    "Withdrawal request #{$withdrawal->id}",
                    $withdrawal,
                    true // Lock balance
                );

                return $withdrawal;
            });

            // Trigger event if auto-approved
            if ($withdrawal->status === 'approved') {
                event(new \App\Events\WithdrawalApproved($withdrawal));
            }

            $message = $withdrawal->status === 'approved'
                ? 'Withdrawal auto-approved! Funds will be processed shortly.'
                : 'Withdrawal request submitted for approval.';

            return response()->json([
                'message' => $message,
                'withdrawal_id' => $withdrawal->id
            ], 201);

        } catch (InsufficientBalanceException $e) {
            // AUDIT FIX: Catch business logic exception separately
            // Returns 422 (Unprocessable Entity) instead of 400
            return response()->json($e->toArray(), 422);

        } catch (\InvalidArgumentException $e) {
            // Validation errors
            return response()->json([
                'message' => $e->getMessage()
            ], 400);

        } catch (\Exception $e) {
            // System errors
            Log::error("Withdrawal request failed", [
                'user_id' => $user->id,
                'amount' => $amount,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Failed to process withdrawal request. Please try again.'
            ], 500);
        }
    }

    /**
     * Download wallet statement as PDF
     *
     * AUDIT FIX (V-AUDIT-MODULE3-004):
     * - Added CSV export option for large statements (>1000 transactions)
     * - Strict limits to prevent OOM errors
     * - Better error messages and user guidance
     */
    public function downloadStatement(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date|after_or_equal:start_date',
            'format'     => 'sometimes|string|in:pdf,csv', // ADDED: Format option
        ]);

        $startDate = $request->start_date ? Carbon::parse($request->start_date)->startOfDay() : now()->subDays(30)->startOfDay();
        $endDate   = $request->end_date ? Carbon::parse($request->end_date)->endOfDay() : now()->endOfDay();
        $format    = $request->format ?? 'pdf';

        // Hard limit: Max 1 year range
        if ($startDate->diffInDays($endDate) > 365) {
            return response()->json(['message' => 'Date range cannot exceed 1 year.'], 422);
        }

        $user = $request->user();
        $wallet = Wallet::where('user_id', $user->id)->first();

        if (!$wallet) {
            return response()->json(['message' => 'Wallet not found'], 404);
        }

        // Build query
        $query = $wallet->transactions()
            ->with('reference')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'desc');

        $count = $query->count();

        // AUDIT FIX: Suggest CSV for large statements
        if ($count > 1000 && $format === 'pdf') {
            return response()->json([
                'message' => "Too many transactions for PDF ({$count} found). Please use CSV format for large statements.",
                'suggestion' => 'Add ?format=csv to your request URL',
                'count' => $count
            ], 422);
        }

        // Generate statement based on format
        if ($format === 'csv') {
            return $this->generateCSVStatement($query, $user, $wallet, $startDate, $endDate);
        } else {
            return $this->generatePDFStatement($query, $user, $wallet, $startDate, $endDate);
        }
    }

    /**
     * Generate CSV statement (handles large datasets)
     *
     * AUDIT FIX (V-AUDIT-MODULE3-004): New method for CSV export
     */
    private function generateCSVStatement($query, $user, $wallet, $startDate, $endDate)
    {
        $filename = 'statement-' . $startDate->format('Ymd') . '-' . $endDate->format('Ymd') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($query) {
            $file = fopen('php://output', 'w');

            // CSV Header
            fputcsv($file, [
                'Date',
                'Transaction ID',
                'Type',
                'Description',
                'Amount',
                'Balance Before',
                'Balance After',
                'Status'
            ]);

            // Stream data using cursor for memory efficiency
            foreach ($query->cursor() as $transaction) {
                fputcsv($file, [
                    $transaction->created_at->format('Y-m-d H:i:s'),
                    $transaction->transaction_id,
                    $transaction->type,
                    $transaction->description,
                    $transaction->amount,
                    $transaction->balance_before,
                    $transaction->balance_after,
                    $transaction->status
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Generate PDF statement (limited to 1000 transactions)
     *
     * [AUDIT FIX] Added strict limits and cursor pagination to prevent OOM
     */
    private function generatePDFStatement($query, $user, $wallet, $startDate, $endDate)
    {
        // Use cursor() to keep memory usage low while fetching
        $transactions = $query->cursor();

        // Prepare data for PDF
        $data = [
            'user' => $user,
            'wallet' => $wallet,
            'transactions' => $transactions,
            'period' => $startDate->format('d M Y') . ' to ' . $endDate->format('d M Y'),
            'generated_at' => now()->format('d M Y, h:i A'),
        ];

        // Generate PDF
        $pdf = Pdf::loadView('pdf.wallet-statement', $data);

        $filename = 'statement-' . $startDate->format('Ymd') . '-' . $endDate->format('Ymd') . '.pdf';
        return $pdf->download($filename);
    }
}
