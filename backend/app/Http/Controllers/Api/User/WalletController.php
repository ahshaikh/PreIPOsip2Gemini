<?php
// V-PHASE3-1730-094 (Created) | V-REMEDIATE-1730-257 (Auto-Withdrawal) | V-FINAL-1730-360 (Refactored) | V-FINAL-1730-429 (FormRequest) | V-FINAL-1730-447 (WalletService) | V-AUDIT-FIX-MODULE7 (Statement Optimization)

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Wallet;
use App\Services\WithdrawalService; // This service handles the *business logic*
use App\Services\WalletService;      // This service handles the *money*
use App\Http\Requests\User\WithdrawalRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class WalletController extends Controller
{
    protected $withdrawalService;
    protected $walletService;

    public function __construct(WithdrawalService $withdrawalService, WalletService $walletService)
    {
        $this->withdrawalService = $withdrawalService;
        $this->walletService = $walletService;
    }

    public function show(Request $request)
    {
        $userId = $request->user()->id;

        // MODULE 7 FIX: Optimization for Wallet Totals
        // Instead of using Model Accessors which load all transactions into memory,
        // we use database-level aggregation (withSum). This is scalable for millions of rows.
        
        $wallet = Wallet::where('user_id', $userId)
            ->withSum(['transactions as total_deposited' => function($query) {
                // Sum only positive money-in types
                $query->whereIn('type', ['deposit', 'admin_adjustment', 'bonus_credit', 'refund'])
                      ->where('amount', '>', 0);
            }], 'amount')
            ->withSum(['transactions as total_withdrawn' => function($query) {
                // Sum withdrawals (they are stored as negative numbers, will wrap in abs() below)
                $query->where('type', 'withdrawal');
            }], 'amount')
            ->first();

        // Handle case where wallet doesn't exist yet
        if (!$wallet) {
            $wallet = Wallet::create(['user_id' => $userId]);
            $wallet->total_deposited = 0;
            $wallet->total_withdrawn = 0;
        } else {
            // Ensure withdrawal total is positive for display
            $wallet->total_withdrawn = abs($wallet->total_withdrawn ?? 0);
            $wallet->total_deposited = $wallet->total_deposited ?? 0;
        }

        $transactions = $wallet->transactions()->latest()->paginate(20);
        
        return response()->json(['wallet' => $wallet, 'transactions' => $transactions]);
    }

    /**
     * Get user's withdrawal requests
     */
    public function withdrawals(Request $request)
    {
        $withdrawals = $request->user()->withdrawals()
            ->latest()
            ->paginate(20);

        return response()->json($withdrawals);
    }

    public function initiateDeposit(Request $request)
    {
        return response()->json(['message' => 'Deposit feature not implemented.'], 501);
    }

    /**
     * Request a withdrawal.
     * This action is now a wrapper for two services:
     * 1. WithdrawalService: Checks rules (KYC, Limits) and creates the Withdrawal *record*.
     * 2. WalletService: Safely moves the money from 'balance' to 'locked_balance'.
     */
    public function requestWithdrawal(WithdrawalRequest $request)
    {
        // $request is now fully validated (KYC, Min Amount, Daily Limit, Balance)
        $validated = $request->validated();
        $user = $request->user();
        $amount = (float)$validated['amount'];
        
        try {
            // We use DB::transaction to wrap both service calls.
            // If the wallet lock fails, the withdrawal record is also rolled back.
            $withdrawal = DB::transaction(function () use ($user, $amount, $validated) {
                
                // 1. Create the withdrawal record (logic from WithdrawalService)
                $withdrawal = $this->withdrawalService->createWithdrawalRecord($user, $amount, $validated['bank_details']);
                
                // 2. Lock the balance (logic from WalletService)
                $this->walletService->withdraw(
                    $user,
                    $amount,
                    'withdrawal_request',
                    "Withdrawal request #{$withdrawal->id}",
                    $withdrawal,
                    true // true = lockBalance
                );

                return $withdrawal;
            });
            
            // 3. Fire events *after* transaction is committed
            if ($withdrawal->status === 'approved') {
                event(new \App\Events\WithdrawalApproved($withdrawal));
            }

            $message = $withdrawal->status === 'approved' 
                ? 'Withdrawal auto-approved! Funds will be processed shortly.' 
                : 'Withdrawal request submitted for approval.';

            return response()->json(['message' => $message]);

        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * Download wallet statement as PDF
     * MODULE 7 FIX: Enforce Date Ranges
     */
    public function downloadStatement(Request $request)
    {
        // MODULE 7 FIX: "Statement of Death" Prevention
        // We MUST validate a date range to prevent loading 5000+ transactions into RAM.
        // If user doesn't provide dates, we default to the last 30 days.
        
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date|after_or_equal:start_date',
        ]);

        $startDate = $request->start_date ? Carbon::parse($request->start_date)->startOfDay() : now()->subDays(30)->startOfDay();
        $endDate   = $request->end_date ? Carbon::parse($request->end_date)->endOfDay() : now()->endOfDay();

        // Hard limit: Max 1 year range to protect server memory
        if ($startDate->diffInDays($endDate) > 365) {
            return response()->json(['message' => 'Date range cannot exceed 1 year.'], 422);
        }

        $user = $request->user();
        $wallet = Wallet::where('user_id', $user->id)->first();

        if (!$wallet) {
            return response()->json(['message' => 'Wallet not found'], 404);
        }

        // Get transactions within the safe window
        $transactions = $wallet->transactions()
            ->with('reference')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'desc')
            ->get();

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

        // Return PDF download
        $filename = 'statement-' . $startDate->format('Ymd') . '-' . $endDate->format('Ymd') . '.pdf';
        return $pdf->download($filename);
    }
}