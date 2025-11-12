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
}