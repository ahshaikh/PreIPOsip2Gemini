<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Product;
use App\Models\Deal;
use App\Models\Investment;
use App\Models\User;
use App\Models\Subscription;
use App\Models\BulkPurchase;
use App\Models\CompanyShareListing;
use App\Models\Offer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Unified Admin Dashboard Controller.
 *
 * Consolidates all module metrics into a single dashboard view.
 */
class UnifiedDashboardController extends Controller
{
    /**
     * Get complete dashboard overview.
     * GET /api/v1/admin/dashboard/overview
     */
    public function overview(Request $request)
    {
        $period = $request->get('period', '30'); // days

        return response()->json([
            'success' => true,
            'kpis' => $this->getKeyMetrics($period),
            'charts' => $this->getChartsData($period),
            'alerts' => $this->getActionableAlerts(),
            'recent_activity' => $this->getRecentActivity(),
            'period' => $period,
            'generated_at' => now(),
        ]);
    }

    /**
     * Get key performance indicators.
     */
    protected function getKeyMetrics(int $period): array
    {
        $periodStart = now()->subDays($period);

        return [
            // Revenue & Financial
            'revenue' => [
                'total_investments' => Investment::where('status', 'completed')
                    ->where('created_at', '>=', $periodStart)
                    ->sum('amount'),
                'total_revenue_all_time' => Investment::where('status', 'completed')->sum('amount'),
                'pending_investments' => Investment::where('status', 'pending')->sum('amount'),
                'average_investment_size' => Investment::where('status', 'completed')
                    ->where('created_at', '>=', $periodStart)
                    ->avg('amount'),
            ],

            // Inventory
            'inventory' => [
                'total_inventory_value' => BulkPurchase::sum('total_value_received'),
                'available_inventory_value' => BulkPurchase::sum('value_remaining'),
                'allocated_percentage' => $this->calculateAllocationRate(),
                'products_out_of_stock' => Product::whereDoesntHave('bulkPurchases', function($q) {
                    $q->where('value_remaining', '>', 0);
                })->count(),
            ],

            // Deals & Campaigns
            'deals' => [
                'active_deals' => Deal::where('status', 'active')
                    ->where('deal_type', 'active')->count(),
                'upcoming_deals' => Deal::where('deal_type', 'upcoming')->count(),
                'total_deal_value' => Deal::where('status', 'active')->sum('valuation'),
                'conversion_rate' => $this->calculateDealConversionRate($period),
            ],

            // Users & Subscriptions
            'users' => [
                'total_users' => User::count(),
                'active_subscribers' => Subscription::whereIn('status', ['active', 'paused'])->count(),
                'new_users_period' => User::where('created_at', '>=', $periodStart)->count(),
                // ARCH-FIX: Query user_kyc table (canonical source) instead of denormalized users.kyc_status
                'kyc_pending' => User::whereHas('kyc', fn($q) => $q->where('status', 'pending'))
                    ->orWhereDoesntHave('kyc')
                    ->count(),
            ],

            // Companies
            'companies' => [
                'total_companies' => Company::count(),
                'verified_companies' => Company::where('is_verified', true)->count(),
                'pending_verification' => Company::where('is_verified', false)
                    ->where('profile_completed', true)->count(),
                'active_companies_with_deals' => Company::whereHas('deals', function($q) {
                    $q->where('status', 'active');
                })->count(),
            ],

            // Share Listings
            'share_listings' => [
                'pending_review' => CompanyShareListing::whereIn('status', ['pending', 'under_review'])->count(),
                'total_value_pending' => CompanyShareListing::whereIn('status', ['pending', 'under_review'])
                    ->sum('total_value'),
                'approved_count' => CompanyShareListing::where('status', 'approved')->count(),
                'rejection_rate' => $this->calculateRejectionRate(),
            ],

            // Campaigns
            'campaigns' => [
                'active_offers' => Offer::where('status', 'active')
                    ->where(function($q) {
                        $q->whereNull('expiry')->orWhere('expiry', '>=', now());
                    })->count(),
                'total_discount_given' => Offer::sum('usage_count'),
                'top_performing_offer' => $this->getTopPerformingOffer(),
            ],
        ];
    }

    /**
     * Get charts data for visualizations.
     */
    protected function getChartsData(int $period): array
    {
        $periodStart = now()->subDays($period);

        return [
            // Investment trend (daily)
            'investment_trend' => Investment::where('status', 'completed')
                ->where('created_at', '>=', $periodStart)
                ->selectRaw('DATE(created_at) as date, SUM(amount) as total, COUNT(*) as count')
                ->groupBy('date')
                ->orderBy('date')
                ->get(),

            // Revenue by product
            'revenue_by_product' => Investment::where('status', 'completed')
                ->join('products', 'investments.product_id', '=', 'products.id')
                ->selectRaw('products.name, SUM(investments.amount) as revenue, COUNT(*) as count')
                ->groupBy('products.id', 'products.name')
                ->orderByDesc('revenue')
                ->limit(10)
                ->get(),

            // Deal performance
            'deal_performance' => Deal::where('status', 'active')
                ->withCount('investments')
                ->with('company:id,name')
                ->selectRaw('*, (SELECT SUM(amount) FROM investments WHERE investments.deal_id = deals.id) as total_raised')
                ->orderByDesc('total_raised')
                ->limit(10)
                ->get(),

            // User growth
            'user_growth' => User::where('created_at', '>=', $periodStart)
                ->selectRaw('DATE(created_at) as date, COUNT(*) as new_users')
                ->groupBy('date')
                ->orderBy('date')
                ->get(),

            // Subscription distribution
            'subscription_distribution' => Subscription::whereIn('status', ['active', 'paused'])
                ->join('plans', 'subscriptions.plan_id', '=', 'plans.id')
                ->selectRaw('plans.name, COUNT(*) as count')
                ->groupBy('plans.id', 'plans.name')
                ->get(),

            // Company distribution by sector
            'companies_by_sector' => Company::selectRaw('sector, COUNT(*) as count')
                ->groupBy('sector')
                ->orderByDesc('count')
                ->get(),
        ];
    }

    /**
     * Get actionable alerts for admin attention.
     */
    protected function getActionableAlerts(): array
    {
        $alerts = [];

        // Low inventory alerts
        $lowStockProducts = Product::whereHas('bulkPurchases', function($q) {
            $q->havingRaw('SUM(value_remaining) < 100000'); // Less than 1L
        })->with('company:id,name')->limit(5)->get();

        foreach ($lowStockProducts as $product) {
            $alerts[] = [
                'type' => 'warning',
                'category' => 'inventory',
                'message' => "Low inventory for {$product->company->name} - {$product->name}",
                'action' => "/admin/bulk-purchases/create?product_id={$product->id}",
                'severity' => 'medium',
            ];
        }

        // Pending share listings
        $pendingListingsCount = CompanyShareListing::whereIn('status', ['pending', 'under_review'])->count();
        if ($pendingListingsCount > 0) {
            $alerts[] = [
                'type' => 'info',
                'category' => 'share_listings',
                'message' => "{$pendingListingsCount} share listings awaiting review",
                'action' => "/admin/share-listings?status=awaiting_review",
                'severity' => 'high',
            ];
        }

        // Unverified companies with complete profiles
        $unverifiedCount = Company::where('is_verified', false)
            ->where('profile_completed', true)->count();
        if ($unverifiedCount > 0) {
            $alerts[] = [
                'type' => 'warning',
                'category' => 'companies',
                'message' => "{$unverifiedCount} companies ready for verification",
                'action' => "/admin/companies?verified=false&profile_complete=true",
                'severity' => 'medium',
            ];
        }

        // Expiring offers
        $expiringOffers = Offer::where('status', 'active')
            ->whereNotNull('expiry')
            ->where('expiry', '<=', now()->addDays(7))
            ->where('expiry', '>=', now())
            ->count();
        if ($expiringOffers > 0) {
            $alerts[] = [
                'type' => 'info',
                'category' => 'campaigns',
                'message' => "{$expiringOffers} offers expiring in next 7 days",
                'action' => "/admin/offers?expiring_soon=true",
                'severity' => 'low',
            ];
        }

        // KYC pending
        // ARCH-FIX: Query user_kyc table (canonical source) instead of denormalized users.kyc_status
        $kycPending = User::whereHas('kyc', fn($q) => $q->where('status', 'pending'))
            ->orWhereDoesntHave('kyc')
            ->count();
        if ($kycPending > 10) {
            $alerts[] = [
                'type' => 'warning',
                'category' => 'compliance',
                'message' => "{$kycPending} KYC verifications pending",
                'action' => "/admin/kyc/queue",
                'severity' => 'high',
            ];
        }

        return $alerts;
    }

    /**
     * Get recent activity across all modules.
     */
    protected function getRecentActivity(): array
    {
        $activities = [];

        // Recent investments
        $recentInvestments = Investment::with(['user:id,name', 'product:id,name', 'deal:id,title'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function($inv) {
                return [
                    'type' => 'investment',
                    'message' => "{$inv->user->name} invested ₹" . number_format($inv->amount) . " in {$inv->deal->title}",
                    'timestamp' => $inv->created_at,
                    'amount' => $inv->amount,
                ];
            });

        // Recent company registrations
        $recentCompanies = Company::orderBy('created_at', 'desc')
            ->limit(3)
            ->get()
            ->map(function($company) {
                return [
                    'type' => 'company',
                    'message' => "New company registered: {$company->name}",
                    'timestamp' => $company->created_at,
                    'entity_id' => $company->id,
                ];
            });

        // Recent deal launches
        $recentDeals = Deal::with('company:id,name')
            ->where('status', 'active')
            ->orderBy('created_at', 'desc')
            ->limit(3)
            ->get()
            ->map(function($deal) {
                return [
                    'type' => 'deal',
                    'message' => "New deal launched: {$deal->title} by {$deal->company->name}",
                    'timestamp' => $deal->created_at,
                    'entity_id' => $deal->id,
                ];
            });

        return collect($activities)
            ->merge($recentInvestments)
            ->merge($recentCompanies)
            ->merge($recentDeals)
            ->sortByDesc('timestamp')
            ->values()
            ->take(15)
            ->all();
    }

    // --- HELPER METHODS ---

    protected function calculateAllocationRate(): float
    {
        $totalValue = BulkPurchase::sum('total_value_received');
        $remainingValue = BulkPurchase::sum('value_remaining');

        if ($totalValue == 0) return 0;

        return round((($totalValue - $remainingValue) / $totalValue) * 100, 2);
    }

    protected function calculateDealConversionRate(int $period): float
    {
        $periodStart = now()->subDays($period);

        $totalViews = Deal::where('created_at', '>=', $periodStart)->count() * 100; // Estimate
        $totalInvestments = Investment::where('created_at', '>=', $periodStart)->count();

        if ($totalViews == 0) return 0;

        return round(($totalInvestments / $totalViews) * 100, 2);
    }

    protected function calculateRejectionRate(): float
    {
        $total = CompanyShareListing::whereIn('status', ['approved', 'rejected'])->count();
        $rejected = CompanyShareListing::where('status', 'rejected')->count();

        if ($total == 0) return 0;

        return round(($rejected / $total) * 100, 2);
    }

    protected function getTopPerformingOffer(): ?array
    {
        $topOffer = Offer::where('usage_count', '>', 0)
            ->orderBy('usage_count', 'desc')
            ->first();

        if (!$topOffer) return null;

        return [
            'id' => $topOffer->id,
            'title' => $topOffer->title,
            'code' => $topOffer->code,
            'usage_count' => $topOffer->usage_count,
        ];
    }

    /**
     * Get workflow suggestions dashboard.
     * GET /api/v1/admin/dashboard/workflows
     */
    public function workflowSuggestions()
    {
        return response()->json([
            'success' => true,
            'suggestions' => [
                // Products needing inventory
                'products_needing_inventory' => Product::whereDoesntHave('bulkPurchases')
                    ->with('company:id,name')
                    ->limit(5)
                    ->get()
                    ->map(function($product) {
                        return [
                            'entity' => 'product',
                            'id' => $product->id,
                            'name' => $product->name,
                            'company' => $product->company->name,
                            'action' => 'add_inventory',
                            'priority' => 'high',
                        ];
                    }),

                // Companies ready for deals
                'companies_ready_for_deals' => Company::whereHas('products')
                    ->whereDoesntHave('deals')
                    ->where('is_verified', true)
                    ->limit(5)
                    ->get()
                    ->map(function($company) {
                        return [
                            'entity' => 'company',
                            'id' => $company->id,
                            'name' => $company->name,
                            'action' => 'create_deal',
                            'priority' => 'medium',
                        ];
                    }),

                // Deals ready for campaigns
                'deals_ready_for_campaigns' => Deal::where('status', 'active')
                    ->whereHas('investments')
                    ->whereDoesntHave('offers')
                    ->with('company:id,name')
                    ->limit(5)
                    ->get()
                    ->map(function($deal) {
                        return [
                            'entity' => 'deal',
                            'id' => $deal->id,
                            'title' => $deal->title,
                            'company' => $deal->company->name,
                            'action' => 'create_campaign',
                            'priority' => 'low',
                        ];
                    }),
            ],
        ]);
    }
}
