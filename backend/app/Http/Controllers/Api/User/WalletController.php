<?php
// V-REMEDIATE-1730-257 (Auto-Withdrawal Added)

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Wallet;
use App\Models\Transaction;
use App\Models\Withdrawal;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WalletController extends Controller
{
    public function show(Request $request)
    {
        $wallet = Wallet::firstOrCreate(['user_id' => $request->user()->id]);
        $transactions = $wallet->transactions()->latest()->paginate(20);

        return response()->json([
            'wallet' => $wallet,
            'transactions' => $transactions,
        ]);
    }
    
    public function initiateDeposit(Request $request)
    {
        return response()->json(['message' => 'Deposit feature not implemented.'], 501);
    }

    public function requestWithdrawal(Request $request)
    {
        // 1. Validate
        $min = setting('min_withdrawal_amount', 1000);
        $validated = $request->validate([
            'amount' => "required|numeric|min:$min",
            'bank_details' => 'required|array', // Expecting JSON object from frontend
        ]);
        
        $user = $request->user();
        $wallet = $user->wallet;
        $amount = $validated['amount'];
        $fee = 0; 
        $netAmount = $amount - $fee;

        if ($wallet->balance < $amount) {
            return response()->json(['message' => 'Insufficient balance.'], 400);
        }

        // 2. Check Auto-Approval Rules (FSD-WITHDRAW-004)
        // Rule 1: Amount threshold (e.g., <= 5000)
        $autoApproveLimit = setting('auto_approval_max_amount', 5000);
        $isSmallAmount = $amount <= $autoApproveLimit;

        // Rule 2: User trust (e.g., > 5 successful payments)
        $successfulPayments = Payment::where('user_id', $user->id)->where('status', 'paid')->count();
        $isTrustedUser = $successfulPayments >= 5;

        // Determine Status
        $initialStatus = ($isSmallAmount && $isTrustedUser) ? 'approved' : 'pending';

        try {
            DB::transaction(function () use ($wallet, $user, $amount, $netAmount, $validated, $initialStatus, $fee) {
                // Lock balance
                $wallet->decrement('balance', $amount);
                $wallet->increment('locked_balance', $amount);

                // Create withdrawal
                Withdrawal::create([
                    'user_id' => $user->id,
                    'wallet_id' => $wallet->id,
                    'amount' => $amount,
                    'fee' => $fee,
                    'net_amount' => $netAmount,
                    'status' => $initialStatus,
                    'bank_details' => $validated['bank_details'],
                    // If auto-approved, we could set admin_id to null or a system bot ID
                ]);
                
                Transaction::create([
                    'user_id' => $user->id,
                    'wallet_id' => $wallet->id,
                    'type' => 'withdrawal_request',
                    'amount' => -$amount,
                    'balance_after' => $wallet->balance,
                    'description' => "Withdrawal request ($initialStatus)",
                ]);
            });
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error processing request.'], 500);
        }
        
        $message = $initialStatus === 'approved' 
            ? 'Withdrawal auto-approved! Funds will be processed shortly.' 
            : 'Withdrawal request submitted for approval.';

        return response()->json(['message' => $message]);
    }
}