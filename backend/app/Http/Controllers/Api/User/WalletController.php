<?php
// V-PHASE3-1730-094

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Wallet;
use App\Models\Transaction;
use App\Models\Withdrawal;
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
        // TODO: Create a 'deposits' table and logic
        // This would be similar to PaymentController@initiate
        return response()->json(['message' => 'Deposit feature not implemented.'], 501);
    }

    public function requestWithdrawal(Request $request)
    {
        $user = $request->user();
        $wallet = $user->wallet;

        $validated = $request->validate([
            'amount' => 'required|numeric|min:' . setting('min_withdrawal_amount', 1000),
            'bank_details' => 'required|json', // Simple for now
        ]);
        
        $amount = $validated['amount'];
        $fee = 0; // Configurable
        $netAmount = $amount - $fee;

        if ($wallet->balance < $amount) {
            return response()->json(['message' => 'Insufficient balance.'], 400);
        }

        try {
            DB::transaction(function () use ($wallet, $user, $amount, $netAmount, $validated) {
                // 1. Lock balance
                $wallet->decrement('balance', $amount);
                $wallet->increment('locked_balance', $amount);

                // 2. Create withdrawal request
                Withdrawal::create([
                    'user_id' => $user->id,
                    'wallet_id' => $wallet->id,
                    'amount' => $amount,
                    'fee' => 0,
                    'net_amount' => $netAmount,
                    'status' => 'pending',
                    'bank_details' => $validated['bank_details'],
                ]);
                
                // 3. Create a transaction record
                Transaction::create([
                    'user_id' => $user->id,
                    'wallet_id' => $wallet->id,
                    'type' => 'withdrawal_request',
                    'amount' => -$amount,
                    'balance_after' => $wallet->balance,
                    'description' => 'Withdrawal request created',
                ]);
            });
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error processing request.'], 500);
        }
        
        // TODO: Notify admin
        return response()->json(['message' => 'Withdrawal request submitted for approval.']);
    }
}