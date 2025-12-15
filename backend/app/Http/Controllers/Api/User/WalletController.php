<?php
// V-PHASE3-1730-094 (Created) | V-REMEDIATE-1730-257 (Auto-Withdrawal) | V-FINAL-1730-360 (Refactored) | V-FINAL-1730-429 (FormRequest) | V-FINAL-1730-447 (WalletService) | V-AUDIT-FIX-MODULE7 (Statement Optimization) | V-AUDIT-FIX-OOM-PROTECTION

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Wallet;
use App\Services\WithdrawalService;
use App\Services\WalletService;
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

        // Optimization: DB-level aggregation
        $wallet = Wallet::where('user_id', $userId)
            ->withSum(['transactions as total_deposited' => function($query) {
                $query->whereIn('type', ['deposit', 'admin_adjustment', 'bonus_credit', 'refund'])
                      ->where('amount', '>', 0);
            }], 'amount')
            ->withSum(['transactions as total_withdrawn' => function($query) {
                $query->where('type', 'withdrawal');
            }], 'amount')
            ->first();

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

    public function requestWithdrawal(WithdrawalRequest $request)
    {
        $validated = $request->validated();
        $user = $request->user();
        $amount = (float)$validated['amount'];
        
        try {
            $withdrawal = DB::transaction(function () use ($user, $amount, $validated) {
                
                $withdrawal = $this->withdrawalService->createWithdrawalRecord($user, $amount, $validated['bank_details']);
                
                $this->walletService->withdraw(
                    $user,
                    $amount,
                    'withdrawal_request',
                    "Withdrawal request #{$withdrawal->id}",
                    $withdrawal,
                    true
                );

                return $withdrawal;
            });
            
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
     * [AUDIT FIX] Added strict limits and cursor pagination to prevent OOM
     */
    public function downloadStatement(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date|after_or_equal:start_date',
        ]);

        $startDate = $request->start_date ? Carbon::parse($request->start_date)->startOfDay() : now()->subDays(30)->startOfDay();
        $endDate   = $request->end_date ? Carbon::parse($request->end_date)->endOfDay() : now()->endOfDay();

        // Hard limit: Max 1 year range
        if ($startDate->diffInDays($endDate) > 365) {
            return response()->json(['message' => 'Date range cannot exceed 1 year.'], 422);
        }

        $user = $request->user();
        $wallet = Wallet::where('user_id', $user->id)->first();

        if (!$wallet) {
            return response()->json(['message' => 'Wallet not found'], 404);
        }

        // [AUDIT FIX] Limit to 1000 transactions to prevents PHP OOM during PDF rendering
        // DomPDF renders in-memory, so passing 10k rows will crash the server regardless of streaming
        $query = $wallet->transactions()
            ->with('reference')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'desc');

        $count = $query->count();
        if ($count > 1000) {
            return response()->json([
                'message' => 'Too many transactions for PDF (Found: ' . $count . '). Please reduce the date range or contact support for a CSV export.'
            ], 422);
        }

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