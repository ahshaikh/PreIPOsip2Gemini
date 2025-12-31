<?php
// V-PHASE3-1730-094 (Created) | V-REMEDIATE-1730-257 (Auto-Withdrawal) | V-FINAL-1730-360 (Refactored)
// V-FINAL-1730-429 (FormRequest) | V-FINAL-1730-447 (WalletService) | V-AUDIT-FIX-MODULE7 (Statement Optimization)
// V-AUDIT-FIX-OOM-PROTECTION | V-AUDIT-MODULE3-004 (Comprehensive Module 3 Fixes) | V-AUDIT-FIX-2025 (Transactions API)
// [PROTOCOL 1 MERGE]: Added Rules Authority Endpoint

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Wallet;
use App\Models\Transaction;
use App\Services\WithdrawalService;
use App\Services\WalletService;
use App\Http\Requests\User\WithdrawalRequest;
use App\Http\Requests\Financial\WalletDepositRequest;
use App\Enums\TransactionType;
use App\Enums\KycStatus;
use App\Exceptions\Financial\InsufficientBalanceException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

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
     * [PROTOCOL 1] FINANCIAL AUTHORITY ENDPOINT
     * This method provides the Single Source of Truth for wallet capabilities and limits.
     * Frontend MUST query this instead of hardcoding limits.
     */
    public function getRules(Request $request)
    {
        $user = Auth::user();
        
        // RBI/Compliance Logic
        // Check if we have an Enum class, otherwise fallback to string comparison
        $isKycVerified = false;
        if (class_exists(KycStatus::class)) {
             $isKycVerified = $user->kyc_status === KycStatus::VERIFIED;
        } else {
             $isKycVerified = $user->kyc_status === 'verified';
        }

        // Dynamic Limits based on risk profile
        $maxLoad = $isKycVerified ? 500000 : 10000; 
        $dailyWithdrawalLimit = $isKycVerified ? 500000 : 0; 
        $autoApproveThreshold = (int) setting('finance.auto_approve_threshold', 50000);

        return response()->json([
            'meta' => [
                'timestamp' => now()->toIso8601String(),
                'version' => 'v2.financial_rules'
            ],
            'data' => [
                'currency' => 'INR',
                'capabilities' => [
                    'can_deposit' => true,
                    'can_withdraw' => $isKycVerified,
                ],
                'limits' => [
                    'deposit' => [
                        'min' => 100,
                        'max' => $maxLoad,
                        'step' => 100,
                    ],
                    'withdrawal' => [
                        'min' => 500,
                        'max' => $dailyWithdrawalLimit,
                        'requires_manual_approval_above' => $autoApproveThreshold,
                    ]
                ],
                'messages' => [
                    'withdrawal_blocked' => $isKycVerified ? null : 'Compliance Requirement: Complete KYC to unlock withdrawals.',
                    'sla_text' => 'Processed within T+1 bank working days.',
                ]
            ]
        ]);
    }

    /**
     * Get wallet details and recent transactions
     */
    public function show(Request $request)
    {
        $userId = $request->user()->id;

        // Use TransactionType enum values for filtering
        $creditTypes = array_map(fn($type) => $type->value, TransactionType::credits());

        // Optimization: DB-level aggregation
        $wallet = Wallet::where('user_id', $userId)
            ->withSum(['transactions as total_deposited' => function($query) use ($creditTypes) {
                $query->whereIn('type', $creditTypes)
                      ->where('amount_paise', '>', 0);
            }], 'amount_paise') 
            ->withSum(['transactions as total_withdrawn' => function($query) {
                $query->where('type', TransactionType::WITHDRAWAL->value);
            }], 'amount_paise')
            ->first();

        // Lazy wallet creation logic
        if (!$wallet) {
            $wallet = Wallet::create(['user_id' => $userId]);
            $wallet->total_deposited = 0;
            $wallet->total_withdrawn = 0;
        } else {
            // Convert paise to rupees for display in summary
            $wallet->total_withdrawn = abs($wallet->total_withdrawn ?? 0) / 100;
            $wallet->total_deposited = ($wallet->total_deposited ?? 0) / 100;
        }

        // [AUDIT FIX]: Eager load recent transactions for the dashboard view
        // The Transaction model has $appends=['amount'], so it will include float values.
        $transactions = $wallet->transactions()->latest()->paginate(20);

        return response()->json(['wallet' => $wallet, 'transactions' => $transactions]);
    }

    /**
     * Get paginated transaction history for the logged-in user.
     */
    public function transactions(Request $request)
    {
        $request->validate([
            'type' => 'nullable|string',
            'page' => 'nullable|integer',
        ]);

        $userId = Auth::id();
        $query = Transaction::where('user_id', $userId)->latest();

        // Apply filters
        if ($request->has('type') && $request->type !== 'all') {
            $query->where('type', $request->type);
        }

        // Pagination
        $perPage = (int) setting('records_per_page', 15);

        return response()->json(
            $query->latest()->paginate($perPage)
        );
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
     */
    public function initiateDeposit(WalletDepositRequest $request)
    {
        // If we reach here, compliance gates have passed (KYC approved, account active)
        $validated = $request->validated();

        $user = $request->user();
        $amount = (string) $validated['amount'];

        $orderId = 'WD_' . time() . '_' . Str::random(8);

        $orderDetails = [
            'order_id' => $orderId,
            'amount' => $amount,
            'currency' => 'INR',
            'user_id' => $user->id,
            'description' => 'Wallet deposit',
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
     */
    public function handleDepositCallback(Request $request)
    {
        return response()->json([
            'message' => 'Deposit callback received. Payment verification in progress.',
        ], 200);
    }

    /**
     * Request a withdrawal
     */
    public function requestWithdrawal(WithdrawalRequest $request)
    {
        $validated = $request->validated();
        $user = $request->user();

        $amount = (string) $validated['amount'];
        // Convert to Paise for Service Layer
        $amountPaise = (int) round((float)$amount * 100); 

        $idempotencyKey = $request->header('X-Idempotency-Key') ?? $request->input('idempotency_key');

        if ($idempotencyKey) {
            $existingWithdrawal = $user->withdrawals()
                ->where('idempotency_key', $idempotencyKey)
                ->where('created_at', '>=', now()->subHours(24))
                ->first();

            if ($existingWithdrawal) {
                return response()->json([
                    'message' => 'Withdrawal request already processed.',
                    'withdrawal_id' => $existingWithdrawal->id
                ], 200);
            }
        }

        try {
            $withdrawal = DB::transaction(function () use ($user, $amount, $amountPaise, $validated, $idempotencyKey) {

                $safeBankDetails = [
                    'account_number' => $validated['bank_details']['account_number'] ?? null,
                    'ifsc_code' => $validated['bank_details']['ifsc_code'] ?? null,
                    'account_holder_name' => $validated['bank_details']['account_holder_name'] ?? null,
                    'bank_name' => $validated['bank_details']['bank_name'] ?? null,
                ];

                $withdrawal = $this->withdrawalService->createWithdrawalRecord(
                    $user,
                    $amount,
                    $safeBankDetails,
                    $idempotencyKey
                );

                // Lock funds in wallet using TransactionType enum
                $this->walletService->withdraw(
                    $user,
                    $amountPaise, // [AUDIT FIX]: Pass Paise integer
                    TransactionType::WITHDRAWAL_REQUEST, 
                    "Withdrawal request #{$withdrawal->id}",
                    $withdrawal,
                    true, // Lock balance
                    false // Allow overdraft? NO for user withdrawals
                );

                return $withdrawal;
            });

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
            return response()->json($e->toArray(), 422);

        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);

        } catch (\Exception $e) {
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
     */
    public function downloadStatement(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date|after_or_equal:start_date',
            'format'     => 'sometimes|string|in:pdf,csv',
        ]);

        $startDate = $request->start_date ? Carbon::parse($request->start_date)->startOfDay() : now()->subDays(30)->startOfDay();
        $endDate   = $request->end_date ? Carbon::parse($request->end_date)->endOfDay() : now()->endOfDay();
        $format    = $request->format ?? 'pdf';

        if ($startDate->diffInDays($endDate) > 365) {
            return response()->json(['message' => 'Date range cannot exceed 1 year.'], 422);
        }

        $user = $request->user();
        $wallet = Wallet::where('user_id', $user->id)->first();

        if (!$wallet) {
            return response()->json(['message' => 'Wallet not found'], 404);
        }

        $query = $wallet->transactions()
            ->with('reference')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'desc');

        $count = $query->count();

        if ($count > 1000 && $format === 'pdf') {
            return response()->json([
                'message' => "Too many transactions for PDF ({$count} found). Please use CSV format for large statements.",
                'suggestion' => 'Add ?format=csv to your request URL',
                'count' => $count
            ], 422);
        }

        if ($format === 'csv') {
            return $this->generateCSVStatement($query, $user, $wallet, $startDate, $endDate);
        } else {
            return $this->generatePDFStatement($query, $user, $wallet, $startDate, $endDate);
        }
    }

    /**
     * Generate CSV statement (handles large datasets)
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

            foreach ($query->cursor() as $transaction) {
                // [AUDIT FIX]: Use accessors to get Float values from Paise
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
     */
    private function generatePDFStatement($query, $user, $wallet, $startDate, $endDate)
    {
        $transactions = $query->cursor();

        $data = [
            'user' => $user,
            'wallet' => $wallet,
            'transactions' => $transactions,
            'period' => $startDate->format('d M Y') . ' to ' . $endDate->format('d M Y'),
            'generated_at' => now()->format('d M Y, h:i A'),
        ];

        $pdf = Pdf::loadView('pdf.wallet-statement', $data);

        $filename = 'statement-' . $startDate->format('Ymd') . '-' . $endDate->format('Ymd') . '.pdf';
        return $pdf->download($filename);
    }
}