// V-PHASE3-1730-092
<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\BonusTransaction;
use Illuminate\Http\Request;

class BonusController extends Controller
{
    public function index(Request $request)
    {
        $bonuses = BonusTransaction::where('user_id', $request->user()->id)
            ->latest()
            ->paginate(20);
            
        $summary = BonusTransaction::where('user_id', $request->user()->id)
            ->groupBy('type')
            ->selectRaw('type, SUM(amount) as total')
            ->pluck('total', 'type');
            
        return response()->json([
            'summary' => $summary,
            'transactions' => $bonuses,
        ]);
    }
}