<?php
// V-REMEDIATE-1730-161

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\UserProfitShare;
use Illuminate\Http\Request;

class ProfitShareController extends Controller
{
    /**
     * Get the user's profit share history.
     */
    public function index(Request $request)
    {
        $shares = UserProfitShare::where('user_id', $request->user()->id)
            ->with('profitSharePeriod:id,period_name,end_date')
            ->latest()
            ->paginate(20);

        return response()->json($shares);
    }

    /**
     * Get Paginated Distribution History
     * Endpoint: /api/v1/user/profit-sharing/history
     * [PROTOCOL 7 IMPLEMENTATION]
     */
    public function distributionHistory(Request $request)
    {
        $request->validate([
            'page' => 'nullable|integer',
        ]);

        // Dynamic Pagination
        $perPage = function_exists('setting') ? (int) setting('records_per_page', 15) : 15;

        $shares = UserProfitShare::where('user_id', $request->user()->id)
            ->with('profitSharePeriod:id,period_name,start_date,end_date,distribution_date,total_profit')
            ->latest()
            ->paginate($perPage)
            ->appends($request->query());

        return response()->json($shares);
    }
}