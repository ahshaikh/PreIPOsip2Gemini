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

        // If amount was locked, unlock it (release locked funds)
        // Note: The status was already 'pending' before we updated it to 'cancelled' above
        $wallet = $user->wallet;
        if ($wallet && $withdrawal->funds_locked) {
            $amountPaise = (int) round($withdrawal->amount * 100);
            $balanceBeforePaise = $wallet->balance_paise;

            // Atomically decrement locked_balance_paise (release lock)
            $wallet->decrement('locked_balance_paise', $amountPaise);
            $wallet->refresh();

            // Create transaction record using paise fields
            $wallet->transactions()->create([
                'user_id' => $user->id,
                'type' => 'withdrawal_cancelled',
                'status' => 'completed',
                'amount_paise' => $amountPaise,
                'balance_before_paise' => $balanceBeforePaise,
                'balance_after_paise' => $wallet->balance_paise,
                'description' => "Withdrawal request #{$withdrawal->id} cancelled - funds unlocked",
                'reference_type' => Withdrawal::class,
                'reference_id' => $withdrawal->id,
            ]);
        }

        return response()->json([
            'message' => 'Withdrawal request cancelled successfully',
            'withdrawal' => $withdrawal,
        ]);
    }
}
