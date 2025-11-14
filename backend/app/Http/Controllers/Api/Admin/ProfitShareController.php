<?php
// V-FINAL-1730-300 | V-FINAL-1730-373 (Refactored)

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProfitShare;
use App\Services\ProfitShareService; // <-- IMPORT
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
        return ProfitShare::latest()->paginate(25);
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
        return $profitShare->load('distributions.user:id,username');
    }

    /**
     * Step 2: Calculate the distribution.
     */
    public function calculate(Request $request, ProfitShare $profitShare)
    {
        try {
            $result = $this->service->calculateDistribution($profitShare);
            return response()->json([
                'message' => 'Calculation complete.',
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
}