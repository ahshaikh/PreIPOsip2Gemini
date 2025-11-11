// V-PHASE3-1730-091
<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\UserInvestment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PortfolioController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        
        $holdings = UserInvestment::where('user_id', $user->id)
            ->with('product:id,name,slug,company_logo,face_value_per_unit')
            ->select(
                'product_id',
                DB::raw('SUM(units_allocated) as total_units'),
                DB::raw('SUM(value_allocated) as total_value')
            )
            ->groupBy('product_id')
            ->get();
            
        $summary = [
            'total_invested' => $holdings->sum('total_value'),
            'current_value' => $holdings->sum('total_value'), // Placeholder
            'unrealized_gain' => 0, // Placeholder
        ];

        return response()->json([
            'summary' => $summary,
            'holdings' => $holdings,
        ]);
    }
}