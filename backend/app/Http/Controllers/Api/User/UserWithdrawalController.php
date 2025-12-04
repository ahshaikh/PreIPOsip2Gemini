<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Withdrawal;
use Illuminate\Http\Request;

class UserWithdrawalController extends Controller
{
    /**
     * Get user's withdrawal requests
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $withdrawals = Withdrawal::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($withdrawals);
    }

    /**
     * Cancel a withdrawal request
     */
    public function cancel(Request $request, $withdrawalId)
    {
        $user = $request->user();

        $withdrawal = Withdrawal::where('user_id', $user->id)
            ->where('id', $withdrawalId)
            ->whereIn('status', ['pending', 'approved'])
            ->firstOrFail();

        if ($withdrawal->status === 'approved') {
            return response()->json([
                'message' => 'Cannot cancel an approved withdrawal. Please contact support.'
            ], 400);
        }

        // Update withdrawal status
        $withdrawal->update([
            'status' => 'cancelled',
            'processed_at' => now(),
        ]);

        // If amount was locked, unlock it back to balance
        if ($withdrawal->status === 'pending') {
            $wallet = $user->wallet;
            if ($wallet) {
                $wallet->decrement('locked_balance', $withdrawal->amount);
                $wallet->increment('balance', $withdrawal->amount);

                // Create transaction record
                $wallet->transactions()->create([
                    'user_id' => $user->id,
                    'type' => 'withdrawal_cancelled',
                    'status' => 'completed',
                    'amount' => $withdrawal->amount,
                    'balance_before' => $wallet->balance - $withdrawal->amount,
                    'balance_after' => $wallet->balance,
                    'description' => "Withdrawal request #{$withdrawal->id} cancelled",
                    'reference_type' => Withdrawal::class,
                    'reference_id' => $withdrawal->id,
                ]);
            }
        }

        return response()->json([
            'message' => 'Withdrawal request cancelled successfully',
            'withdrawal' => $withdrawal,
        ]);
    }
}
