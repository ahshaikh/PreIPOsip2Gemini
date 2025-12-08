<?php
// V-PHASE3-1730-094 (Created) | V-REMEDIATE-1730-257 (Auto-Withdrawal) | V-FINAL-1730-360 (Refactored) | V-FINAL-1730-429 (FormRequest) | V-FINAL-1730-447 (WalletService)

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Wallet;
use App\Services\WithdrawalService; // This service handles the *business logic*
use App\Services\WalletService;      // This service handles the *money*
use App\Http\Requests\User\WithdrawalRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

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
        $wallet = Wallet::firstOrCreate(['user_id' => $request->user()->id]);
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
     */
    public function downloadStatement(Request $request)
    {
        $user = $request->user();
        $wallet = Wallet::where('user_id', $user->id)->first();

        if (!$wallet) {
            return response()->json(['message' => 'Wallet not found'], 404);
        }

        // Get all transactions
        $transactions = $wallet->transactions()
            ->with('reference')
            ->orderBy('created_at', 'desc')
            ->get();

        // Prepare data for PDF
        $data = [
            'user' => $user,
            'wallet' => $wallet,
            'transactions' => $transactions,
            'generated_at' => now()->format('d M Y, h:i A'),
        ];

        // Generate PDF
        $pdf = Pdf::loadView('pdf.wallet-statement', $data);

        // Return PDF download
        $filename = 'wallet-statement-' . $user->id . '-' . now()->format('Y-m-d') . '.pdf';
        return $pdf->download($filename);
    }
}