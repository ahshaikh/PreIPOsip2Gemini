<?php
// V-PHASE3-1730-095 | V-FINAL-1730-361 (Refactored)

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Withdrawal;
use App\Services\WithdrawalService; // <-- IMPORT
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WithdrawalController extends Controller
{
    // Inject the service
    protected $withdrawalService;
    public function __construct(WithdrawalService $withdrawalService)
    {
        $this->withdrawalService = $withdrawalService;
    }
    
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
        try {
            $this->withdrawalService->approveWithdrawal($withdrawal, $request->user());
            return response()->json(['message' => 'Withdrawal approved. Ready for processing.']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function reject(Request $request, Withdrawal $withdrawal)
    {
        $validated = $request->validate(['reason' => 'required|string|min:5']);
        
        try {
            $this->withdrawalService->rejectWithdrawal($withdrawal, $request->user(), $validated['reason']);
            return response()->json(['message' => 'Withdrawal rejected and funds returned to user.']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
    
    public function complete(Request $request, Withdrawal $withdrawal)
    {
        $validated = $request->validate(['utr_number' => 'required|string|max:100']);

        try {
            $this->withdrawalService->completeWithdrawal($withdrawal, $request->user(), $validated['utr_number']);
            return response()->json(['message' => 'Withdrawal marked as complete.']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}