<?php
// V-PHASE3-1730-091 (Created) | V-FINAL-1730-462 | V-AUDIT-MODULE5-004 (Performance Refactor) | V-FIX-PROTOCOL-7

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Services\ValuationService;
use App\Models\Transaction; // Added for transactions() method
use App\Models\UserInvestment; // Added for index() logic
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

/**
 * PortfolioController - User Investment Portfolio
 *
 * V-AUDIT-MODULE5-004 (HIGH) - Performance Optimization
 * Refactored from in-memory PHP Collection processing to database-level aggregation.
 */
class PortfolioController extends Controller
{
    protected $valuationService;

    public function __construct(ValuationService $valuationService)
    {
        $this->valuationService = $valuationService;
    }

    /**
     * Get User Portfolio Summary
     * Endpoint: /api/v1/user/portfolio
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            // Check if required tables exist before querying
            if (!DB::getSchemaBuilder()->hasTable('user_investments') ||
                !DB::getSchemaBuilder()->hasTable('products')) {
                Log::warning('Portfolio tables missing', [
                    'user_id' => $user->id,
                    'tables_checked' => ['user_investments', 'products']
                ]);
                return $this->emptyResponse();
            }

            // V-AUDIT-MODULE5-004: Database-level aggregation
            $holdings = DB::table('user_investments as ui')
                ->join('products as p', 'ui.product_id', '=', 'p.id')
                ->where('ui.user_id', $user->id)
                ->where('ui.status', 'active')
                ->where('ui.is_reversed', false)
                ->select(
                    'p.id as product_id',
                    'p.name as product_name',
                    'p.slug as product_slug',
                    'p.sector',
                    'p.current_market_price',
                    'p.face_value_per_unit',
                    DB::raw('SUM(ui.units_allocated) as total_units'),
                    DB::raw('SUM(ui.value_allocated) as cost_basis')
                )
                ->groupBy('p.id', 'p.name', 'p.slug', 'p.sector', 'p.current_market_price', 'p.face_value_per_unit')
                ->get();

            if ($holdings->isEmpty()) {
                return $this->emptyResponse();
            }

            // Calculate current values using ValuationService logic manually here for performance
            // or iterate as per original design.
            $holdingsWithMetrics = $holdings->map(function ($holding) {
                $currentPrice = (float) ($holding->current_market_price ?? $holding->face_value_per_unit ?? 0);
                $units = (float) $holding->total_units;
                $costBasis = (float) $holding->cost_basis;

                $currentValue = $units * $currentPrice;
                $profitLoss = $currentValue - $costBasis;
                $roiPercent = $costBasis > 0 ? ($profitLoss / $costBasis) * 100 : 0;

                return [
                    'product_name' => $holding->product_name,
                    'product_slug' => $holding->product_slug,
                    'sector' => $holding->sector ?? 'General',
                    'total_units' => round($units, 4),
                    'current_value' => round($currentValue, 2),
                    'cost_basis' => round($costBasis, 2),
                    'unrealized_pl' => round($profitLoss, 2), // Matches frontend key
                    'total_returns' => round($profitLoss, 2), // Alias for frontend consistency
                    'roi_percent' => round($roiPercent, 2),
                ];
            });

            // Calculate summary KPIs
            $totalInvested = $holdingsWithMetrics->sum('cost_basis');
            $currentValue = $holdingsWithMetrics->sum('current_value');
            $totalPL = $currentValue - $totalInvested;
            $totalRoiPercent = $totalInvested > 0 ? ($totalPL / $totalInvested) * 100 : 0;

            return response()->json([
                'summary' => [
                    'total_invested' => round($totalInvested, 2),
                    'current_value' => round($currentValue, 2),
                    'total_returns' => round($totalPL, 2),
                    'returns_percentage' => round($totalRoiPercent, 2),
                ],
                'holdings' => $holdingsWithMetrics->values()
            ]);

        } catch (\Throwable $e) {
            Log::error("Portfolio Error: " . $e->getMessage(), [
                'user_id' => $request->user()->id ?? null,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return $this->emptyResponse();
        }
    }

    /**
     * Get Paginated Transactions for Portfolio
     * Endpoint: /api/v1/user/portfolio/transactions
     * [PROTOCOL 7 IMPLEMENTATION]
     */
    public function transactions(Request $request): JsonResponse
    {
        // 1. Base Query
        $query = Transaction::where('user_id', Auth::id());

        // 2. Filter by Transaction Type (if provided)
        // Frontend uses 'all', 'investment', 'dividend', 'profit_share'
        if ($request->has('type') && $request->type !== 'all') {
            $query->where('type', $request->type);
        } else {
             // Default: Show only portfolio-relevant transactions
             // You can adjust this list based on your specific requirements
             $query->whereIn('type', [
                 'investment', 
                 'dividend', 
                 'profit_share', 
                 'refund', 
                 'buy', 
                 'sell'
             ]);
        }

        // 3. Dynamic Pagination (Protocol 7)
        // Fallback to 15 if setting helper fails
        $perPage = function_exists('setting') ? (int) setting('records_per_page', 15) : 15;

        $transactions = $query->latest()
            ->paginate($perPage)
            ->appends($request->query());

        return response()->json($transactions);
    }

    private function emptyResponse(): JsonResponse
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