<?php
// V-PHASE3-1730-095

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Withdrawal;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WithdrawalController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'pending');
        $withdrawals = Withdrawal::where('status', $status)
            ->with('user:id,username')
            ->latest()
            ->paginate(25);
            
        return response()->json($withdrawals);
    }
    
    public function approve(Request $request, Withdrawal $withdrawal)
    {
        if ($withdrawal->status !== 'pending') {
            return response()->json(['message' => 'Invalid status.'], 400);
        }
        
        $withdrawal->update([
            'status' => 'approved',
            'admin_id' => $request->user()->id,
        ]);
        
        // TODO: Send "Withdrawal Approved" email
        
        return response()->json(['message' => 'Withdrawal approved. Ready for processing.']);
    }

    public function reject(Request $request, Withdrawal $withdrawal)
    {
        $validated = $request->validate(['reason' => 'required|string|min:10']);
        
        if ($withdrawal->status !== 'pending' && $withdrawal->status !== 'approved') {
            return response()->json(['message' => 'Invalid status.'], 400);
        }

        try {
            DB::transaction(function () use ($withdrawal, $validated) {
                // 1. Update withdrawal
                $withdrawal->update([
                    'status' => 'rejected',
                    'admin_id' => $request->user()->id,
                    'rejection_reason' => $validated['reason'],
                ]);

                // 2. Unlock balance
                $wallet = $withdrawal->wallet;
                $wallet->decrement('locked_balance', $withdrawal->amount);
                $wallet->increment('balance', $withdrawal->amount);
                
                // 3. Create reversal transaction
                Transaction::create([
                    'user_id' => $withdrawal->user_id,
                    'wallet_id' => $wallet->id,
                    'type' => 'withdrawal_rejected',
                    'amount' => $withdrawal->amount,
                    'balance_after' => $wallet->balance,
                    'description' => 'Withdrawal rejected. Funds returned.',
                    'reference_type' => Withdrawal::class,
                    'reference_id' => $withdrawal->id,
                ]);
            });
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error processing rejection.'], 500);
        }

        // TODO: Send "Withdrawal Rejected" email
        
        return response()->json(['message' => 'Withdrawal rejected and funds returned to user.']);
    }
    
    public function complete(Request $request, Withdrawal $withdrawal)
    {
        $validated = $request->validate(['utr_number' => 'required|string|max:100']);

        if ($withdrawal->status !== 'approved') {
            return response()->json(['message' => 'Withdrawal must be approved first.'], 400);
        }

        try {
            DB::transaction(function () use ($withdrawal, $validated) {
                // 1. Update withdrawal
                $withdrawal->update([
                    'status' => 'completed',
                    'utr_number' => $validated['utr_number'],
                ]);

                // 2. Debit locked balance
                $wallet = $withdrawal->wallet;
                $wallet->decrement('locked_balance', $withdrawal->amount);
                
                // 3. Create final transaction
                Transaction::create([
                    'user_id' => $withdrawal->user_id,
                    'wallet_id' => $wallet->id,
                    'type' => 'withdrawal',
                    'amount' => -$withdrawal->amount,
                    'balance_after' => $wallet->balance,
                    'description' => 'Withdrawal completed. UTR: ' . $validated['utr_number'],
                    'reference_type' => Withdrawal::class,
                    'reference_id' => $withdrawal->id,
                ]);
            });
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error completing withdrawal.'], 500);
        }
        
        // TODO: Send "Withdrawal Completed" email
        
        return response()->json(['message' => 'Withdrawal marked as complete.']);
    }
}