<?php
// V-FINAL-1730-300 | V-FINAL-1730-373 (Refactored) | V-FINAL-1730-568 (Service Injected) | V-FINAL-1730-574 (Adjust/Reverse)

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProfitShare;
use App\Services\ProfitShareService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProfitShareController extends Controller
{
    protected $service;
    public function __construct(ProfitShareService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        return ProfitShare::with('admin:id,username')->latest()->paginate(25);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'period_name' => 'required|string|max:255|unique:profit_shares',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'net_profit' => 'required|numeric|min:0',
            'total_pool' => 'required|numeric|min:0|lte:net_profit',
        ]);

        $period = ProfitShare::create($validated + [
            'admin_id' => $request->user()->id,
            'status' => 'pending',
        ]);

        return response()->json($period, 201);
    }

    public function show(ProfitShare $profitShare)
    {
        // Load the distributions and the associated user
        return $profitShare->load('distributions.user:id,username,email');
    }

    /**
     * Step 2: Calculate the distribution.
     */
    public function calculate(Request $request, ProfitShare $profitShare)
    {
        try {
            $result = $this->service->calculateDistribution($profitShare);
            return response()->json([
                'message' => 'Calculation complete. Ready to distribute.',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Step 3: Distribute the funds.
     */
    public function distribute(Request $request, ProfitShare $profitShare)
    {
        try {
            $this->service->distributeToWallets($profitShare, $request->user());
            return response()->json(['message' => 'Profit share distributed successfully.']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * NEW: Manually adjust a single user's share.
     */
    public function adjust(Request $request, ProfitShare $profitShare)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:0',
            'reason' => 'required|string',
        ]);
        
        try {
            $this->service->manualAdjustment(
                $profitShare,
                $validated['user_id'],
                $validated['amount'],
                $validated['reason']
            );
            return response()->json(['message' => 'Adjustment saved.']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * NEW: Reverse an entire distribution.
     */
    public function reverse(Request $request, ProfitShare $profitShare)
    {
        $validated = $request->validate([
            'reason' => 'required|string|min:10',
        ]);
        
        try {
            $this->service->reverseDistribution($profitShare, $validated['reason']);
            return response()->json(['message' => 'Distribution reversed successfully.']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}