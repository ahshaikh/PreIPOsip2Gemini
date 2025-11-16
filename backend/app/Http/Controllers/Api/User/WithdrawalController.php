<?php
// V-FINAL-1730-583 (Created)

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Withdrawal;
use App\Services\WithdrawalService;
use Illuminate\Http\Request;

class WithdrawalController extends Controller
{
    protected $withdrawalService;
    public function __construct(WithdrawalService $withdrawalService)
    {
        $this->withdrawalService = $withdrawalService;
    }

    /**
     * Test: testUserCanViewWithdrawalHistory
     */
    public function index(Request $request)
    {
        $history = $request->user()->withdrawals()
            ->latest()
            ->paginate(20);
            
        return response()->json($history);
    }

    /**
     * Test: testUserCanCancelPendingWithdrawal
     */
    public function cancel(Request $request, Withdrawal $withdrawal)
    {
        try {
            $this->withdrawalService->cancelUserWithdrawal($request->user(), $withdrawal);
            return response()->json(['message' => 'Withdrawal request cancelled successfully.']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}