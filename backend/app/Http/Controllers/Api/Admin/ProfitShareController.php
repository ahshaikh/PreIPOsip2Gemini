<?php
// V-PHASE3-1730-097 (Created) | V-FINAL-1730-300 | V-FINAL-1730-373 (Refactored) | V-FINAL-1730-568 (Service Injected) | V-FINAL-1730-574 (Adjust/Reverse)

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

    /**
     * Get profit sharing configuration settings
     *
     * GET /api/v1/admin/profit-sharing-settings
     */
    public function getSettings()
    {
        $settings = \App\Models\Setting::where('group', 'profit_share_config')
            ->get()
            ->keyBy('key');

        return response()->json(['settings' => $settings]);
    }

    /**
     * Update profit sharing configuration settings
     *
     * PUT /api/v1/admin/profit-sharing-settings
     */
    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'settings' => 'required|array',
            'settings.*.key' => 'required|string',
            'settings.*.value' => 'required',
        ]);

        foreach ($validated['settings'] as $settingData) {
            $setting = \App\Models\Setting::where('key', $settingData['key'])->first();

            if ($setting && $setting->group === 'profit_share_config') {
                $setting->update([
                    'value' => $settingData['value'],
                    'updated_by' => auth()->id(),
                ]);

                // Clear cache
                \Illuminate\Support\Facades\Cache::forget('setting.' . $setting->key);
            }
        }

        \Illuminate\Support\Facades\Cache::forget('settings');

        return response()->json([
            'success' => true,
            'message' => 'Profit sharing settings updated successfully',
        ]);
    }

    /**
     * Preview distribution without saving to database
     *
     * POST /api/v1/admin/profit-sharing/{profitShare}/preview
     */
    public function preview(Request $request, ProfitShare $profitShare)
    {
        try {
            $result = $this->service->calculateDistribution($profitShare, true);

            // Format distributions for preview
            $formatted = array_map(function($dist) {
                return [
                    'user_id' => $dist['user_id'],
                    'username' => $dist['subscription']->user->username ?? 'Unknown',
                    'investment' => $dist['investment_weight'],
                    'tenure_months' => $dist['tenure_months'],
                    'share_percent' => $dist['share_percent'] * 100,
                    'amount' => round($dist['amount'], 2),
                ];
            }, $result['distributions']);

            return response()->json([
                'message' => 'Preview generated successfully',
                'distributions' => $formatted,
                'metadata' => $result['metadata'],
                'total_distributed' => $result['total_distributed'],
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Publish financial report with visibility controls
     *
     * POST /api/v1/admin/profit-sharing/{profitShare}/publish-report
     */
    public function publishReport(Request $request, ProfitShare $profitShare)
    {
        $validated = $request->validate([
            'visibility' => 'required|in:public,private,partners_only',
        ]);

        try {
            $reportData = $this->service->publishReport(
                $profitShare,
                $validated['visibility'],
                $request->user()
            );

            return response()->json([
                'message' => 'Report published successfully',
                'report' => $reportData,
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get published report (with visibility checks)
     *
     * GET /api/v1/admin/profit-sharing/{profitShare}/report
     */
    public function getReport(ProfitShare $profitShare)
    {
        if (!$profitShare->published_at) {
            return response()->json(['message' => 'Report not published yet'], 404);
        }

        $distributions = $profitShare->distributions()->with('user')->get();
        $showDetails = setting('profit_share_show_beneficiary_details', false);

        $reportData = [
            'period_name' => $profitShare->period_name,
            'period' => [
                'start_date' => $profitShare->start_date->format('Y-m-d'),
                'end_date' => $profitShare->end_date->format('Y-m-d'),
            ],
            'financials' => [
                'net_profit' => $profitShare->net_profit,
                'total_pool' => $profitShare->total_pool,
                'total_distributed' => $profitShare->total_distributed,
            ],
            'statistics' => [
                'total_beneficiaries' => $distributions->count(),
                'average_per_user' => $distributions->count() > 0
                    ? round($profitShare->total_distributed / $distributions->count(), 2)
                    : 0,
            ],
            'metadata' => $profitShare->calculation_metadata,
            'visibility' => $profitShare->report_visibility,
            'published_at' => $profitShare->published_at,
        ];

        // Add beneficiary details based on visibility settings
        if ($showDetails && $profitShare->report_visibility !== 'private') {
            $reportData['beneficiaries'] = $distributions->map(function ($dist) {
                return [
                    'username' => $dist->user->username,
                    'amount' => $dist->amount,
                ];
            })->toArray();
        }

        return response()->json(['report' => $reportData]);
    }
}