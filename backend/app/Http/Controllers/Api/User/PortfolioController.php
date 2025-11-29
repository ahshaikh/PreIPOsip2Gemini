<?php
// V-PHASE3-1730-091 (Created) | V-FINAL-1730-462 

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class PortfolioController extends Controller
{
    /**
     * Get User Portfolio Summary
     * Endpoint: /api/v1/user/portfolio
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            // 1. Get Investments with Product Relation
            // Defensive: Check if relation exists on User model before calling
            if (!method_exists($user, 'investments')) {
                return $this->emptyResponse();
            }

            $investments = $user->investments()
                ->where('status', 'active') // Only count active investments
                ->with('product')
                ->get();

            if ($investments->isEmpty()) {
                return $this->emptyResponse();
            }

            // 2. Aggregate Data
            $portfolio = $investments->map(function ($inv) {
                // Safe access to product data
                $product = $inv->product;
                
                $invested = (float) $inv->total_amount;
                $units = (float) $inv->units_allocated;
                
                // Logic: Use product's CMP (Current Market Price) or fallback to purchase price
                $currentPrice = (float) ($product->current_market_price ?? $inv->price_per_share ?? 0);
                
                // If price is 0 (missing), assume no change to avoid showing 100% loss
                $currentValue = $currentPrice > 0 ? ($units * $currentPrice) : $invested;

                return [
                    'product_name' => $product->name ?? 'Unknown Asset',
                    'product_slug' => $product->slug ?? 'unknown',
                    'sector' => $product->sector ?? 'General',
                    'units' => $units,
                    'invested' => $invested,
                    'current_value' => $currentValue,
                ];
            });

            // 3. Calculate KPIs
            $totalInvested = $portfolio->sum('invested');
            $currentValue = $portfolio->sum('current_value');
            $totalPL = $currentValue - $totalInvested;
            $totalRoiPercent = $totalInvested > 0 ? ($totalPL / $totalInvested) * 100 : 0;

            // 4. Group Holdings (Consolidate multiple buys of same product)
            $holdings = $portfolio->groupBy('product_name')->map(function ($group) {
                $first = $group->first();
                $grpInvested = $group->sum('invested');
                $grpValue = $group->sum('current_value');
                $grpPL = $grpValue - $grpInvested;

                return [
                    'product_name' => $first['product_name'],
                    'product_slug' => $first['product_slug'],
                    'sector' => $first['sector'],
                    'total_units' => $group->sum('units'),
                    'current_value' => $grpValue,
                    'cost_basis' => $grpInvested,
                    'unrealized_pl' => $grpPL,
                    'roi_percent' => $grpInvested > 0 ? round(($grpPL / $grpInvested) * 100, 2) : 0,
                ];
            })->values();

            return response()->json([
                'summary' => [ // Structure matched to frontend requirements
                    'total_invested' => $totalInvested,
                    'current_value' => $currentValue,
                    'total_returns' => $totalPL, // Changed key to match standard
                    'returns_percentage' => round($totalRoiPercent, 2),
                ],
                'holdings' => $holdings
            ]);

        } catch (\Throwable $e) {
            Log::error("Portfolio Error: " . $e->getMessage());
            return $this->emptyResponse();
        }
    }

    private function emptyResponse()
    {
        return response()->json([
            'summary' => [
                'total_invested' => 0,
                'current_value' => 0,
                'total_returns' => 0,
                'returns_percentage' => 0,
            ],
            'holdings' => [],
        ]);
    }
}