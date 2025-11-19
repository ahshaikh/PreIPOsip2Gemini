<?php
// V-FINAL-1730-462 (Created)

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PortfolioController extends Controller
{
    /**
     * FSD-PORTFOLIO-001: Get aggregated portfolio statistics.
     */
    public function index(Request $request)
        {
        $user = $request->user();
        
        // 1. Get all investments, eager load the product's current price
        $investments = $user->investments()->with('product')->get();

        if ($investments->isEmpty()) {
            return response()->json([
                'total_invested' => 0,
                'current_value' => 0,
                'total_pl' => 0,
                'total_roi_percent' => 0,
                'holdings' => [],
            ]);
        }

        // 2. Calculate KPIs
        // FSD-PORTFOLIO-002
        $totalInvested = $investments->sum('value_allocated');
        
        // FSD-PORTFOLIO-003
        $currentValue = $investments->sum(function($investment) {
            // We use the 'current_value' accessor from the UserInvestment model
            return $investment->current_value;
        });

        $totalPL = $currentValue - $totalInvested;
        $totalRoiPercent = ($totalInvested > 0) ? ($totalPL / $totalInvested) * 100 : 0;

        // 3. Aggregate holdings by product
        // FSD-PORTFOLIO-004
        $holdings = $investments->groupBy('product.name')->map(function ($group) {
            $first = $group->first();
            
            $totalUnits = $group->sum('units_allocated');
            $costBasis = $group->sum('value_allocated');
            $currentValue = $group->sum('current_value');
            $pl = $currentValue - $costBasis;
            $roi = ($costBasis > 0) ? ($pl / $costBasis) * 100 : 0;

            return [
                'product_name' => $first->product->name,
                'product_slug' => $first->product->slug,
                'sector' => $first->product->sector,
                'total_units' => $totalUnits,
                'current_value' => $currentValue,
                'cost_basis' => $costBasis,
                'unrealized_pl' => $pl,
                'roi_percent' => round($roi, 2),
            ];
        })->values(); // Reset keys to a simple array


        return response()->json([
            'total_invested' => $totalInvested,
            'current_value' => $currentValue,
            'total_pl' => $totalPL,
            'total_roi_percent' => round($totalRoiPercent, 2),
            'holdings' => $holdings,
        ]);
    }
}