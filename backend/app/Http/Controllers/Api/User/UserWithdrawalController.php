<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Withdrawal;
use App\Services\WithdrawalService;
use Illuminate\Http\Request;

class UserWithdrawalController extends Controller
{
    protected $withdrawalService;

    public function __construct(WithdrawalService $withdrawalService)
    {
        $this->withdrawalService = $withdrawalService;
    }

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
            ->firstOrFail();

        try {
            $this->withdrawalService->cancelUserWithdrawal($user, $withdrawal);

            return response()->json([
                'message' => 'Withdrawal request cancelled successfully',
                'withdrawal' => $withdrawal->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
