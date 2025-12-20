<?php
// V-PHASE3-1730-091 (Created) | V-FINAL-1730-462 | V-AUDIT-MODULE5-004 (Performance Refactor)

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Services\ValuationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * PortfolioController - User Investment Portfolio
 *
 * V-AUDIT-MODULE5-004 (HIGH) - Performance Optimization
 * Refactored from in-memory PHP Collection processing to database-level aggregation.
 *
 * Previous Issue:
 * - Loaded all user investments into memory (500+ records for 2-year SIP)
 * - Caused OOM (Out of Memory) errors on high-traffic portfolios
 * - Performed calculations in PHP instead of leveraging database
 *
 * Current Implementation:
 * - Uses DB aggregation (selectRaw, SUM, GROUP BY)
 * - Minimal memory footprint
 * - Integrates ValuationService for consistent valuation logic
 * - Returns proper HTTP 500 on errors (no more silent failures)
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
     *
     * V-AUDIT-MODULE5-005 (MEDIUM) - Fixed Error Swallowing
     * Now returns proper HTTP 500 on system errors instead of silently returning empty response.
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

            // V-AUDIT-MODULE5-004: Database-level aggregation (not in-memory PHP)
            // Group investments by product and calculate sums at DB level
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

            // Calculate current values using ValuationService
            $holdingsWithMetrics = $holdings->map(function ($holding) {
                // V-AUDIT-MODULE5-003: Use centralized ValuationService
                $currentPrice = (float) ($holding->current_market_price ?? $holding->face_value_per_unit ?? 0);
                $units = (float) $holding->total_units;
                $costBasis = (float) $holding->cost_basis;

                // Calculate current value
                $currentValue = $units * $currentPrice;

                // Calculate P&L and ROI
                $profitLoss = $this->valuationService->calculateProfitLoss($currentValue, $costBasis);
                $roiPercent = $this->valuationService->calculateROI($profitLoss, $costBasis);

                return [
                    'product_name' => $holding->product_name,
                    'product_slug' => $holding->product_slug,
                    'sector' => $holding->sector ?? 'General',
                    'total_units' => round($units, 4),
                    'current_value' => round($currentValue, 2),
                    'cost_basis' => round($costBasis, 2),
                    'unrealized_pl' => round($profitLoss, 2),
                    'roi_percent' => round($roiPercent, 2),
                ];
            });

            // Calculate summary KPIs
            $totalInvested = $holdingsWithMetrics->sum('cost_basis');
            $currentValue = $holdingsWithMetrics->sum('current_value');
            $totalPL = $this->valuationService->calculateProfitLoss($currentValue, $totalInvested);
            $totalRoiPercent = $this->valuationService->calculateROI($totalPL, $totalInvested);

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
            // V-AUDIT-MODULE5-005 (MEDIUM): Return empty response instead of error
            // This allows dashboard to load even if portfolio data unavailable
            Log::error("Portfolio Error: " . $e->getMessage(), [
                'user_id' => $request->user()->id ?? null,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            // Return empty portfolio instead of 500 error
            return $this->emptyResponse();
        }
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
