<?php

namespace App\Http\Controllers\Api\Company;

use App\Http\Controllers\Controller;
use App\Models\CompanyAnalytics;
use App\Models\InvestorInterest;
use Illuminate\Http\Request;

class CompanyAnalyticsController extends Controller
{
    /**
     * Get company analytics dashboard
     */
    public function dashboard(Request $request)
    {
        $companyUser = $request->user();
        $company = $companyUser->company;

        // FIX: Add null check to prevent crash if company relationship missing
        if (!$company) {
            return response()->json([
                'success' => false,
                'message' => 'Company not found',
            ], 404);
        }


        // Get date range (default last 30 days)
        $startDate = $request->get('start_date', now()->subDays(30)->toDateString());
        $endDate = $request->get('end_date', now()->toDateString());

        $analytics = CompanyAnalytics::where('company_id', $company->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'asc')
            ->get();

        // Calculate totals
        $totals = [
            'profile_views' => $analytics->sum('profile_views'),
            'document_downloads' => $analytics->sum('document_downloads'),
            'financial_report_downloads' => $analytics->sum('financial_report_downloads'),
            'deal_views' => $analytics->sum('deal_views'),
            'investor_interest_clicks' => $analytics->sum('investor_interest_clicks'),
        ];

        // Get investor interests
        $investorInterests = InvestorInterest::where('company_id', $company->id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $interestStats = [
            'total' => InvestorInterest::where('company_id', $company->id)->count(),
            'pending' => InvestorInterest::where('company_id', $company->id)->pending()->count(),
            'qualified' => InvestorInterest::where('company_id', $company->id)->qualified()->count(),
        ];

        return response()->json([
            'success' => true,
            'analytics' => $analytics,
            'totals' => $totals,
            'investor_interests' => $investorInterests,
            'interest_stats' => $interestStats,
        ], 200);
    }
}
