<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\UserInvestment;
use App\Models\UserProfitShare;
use App\Models\Referral;
use App\Models\Wallet;
use App\Models\ActivityLog;       
use App\Models\Subscription;      
use App\Models\BonusTransaction;  

class UserDashboardController extends Controller
{
    /**
     * Get aggregated dashboard stats.
     * Endpoint: GET /api/user/dashboard/overview
     */
    public function overview(Request $request)
    {
        $user = Auth::user();
        
        // 1. Fetch Wallet Balance
        $wallet = Wallet::where('user_id', $user->id)->first();
        $walletBalance = $wallet ? (float)$wallet->balance : 0.00;

        // 2. Investment Stats
        $investmentQuery = UserInvestment::where('user_id', $user->id)
            ->whereIn('status', ['active', 'Active', 'approved', 'Approved', 'running']);
            
        // [FIX] Use 'value_allocated' based on your migration history
        $totalInvested = (float) $investmentQuery->sum('value_allocated');
        
        // 3. Profit Stats
        // Assuming UserProfitShare uses 'amount' (if this fails, check if it's 'profit_amount')
        $totalProfit = (float) UserProfitShare::where('user_id', $user->id)->sum('amount');
        
        // 4. Calculate Portfolio Metrics
        $portfolioValue = $totalInvested + $totalProfit;

        $portfolioChangePercent = 0;
        $isPositive = true;
        if ($totalInvested > 0) {
            $portfolioChangePercent = (($portfolioValue - $totalInvested) / $totalInvested) * 100;
            $portfolioChangePercent = round($portfolioChangePercent, 2);
            $isPositive = $portfolioChangePercent >= 0;
        }

        // 5. Subscription Status
        $subscription = Subscription::where('user_id', $user->id)
            ->where('status', 'active')
            ->with('plan') 
            ->first();

        // 6. Referral Stats
        $referralStats = Referral::where('referrer_id', $user->id)
            ->selectRaw("count(*) as total, sum(case when status = 'active' then 1 else 0 end) as active")
            ->first();

        // 7. Recent Activity
        $activities = ActivityLog::where('user_id', $user->id)
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($log) {
                return [
                    'description' => $log->description,
                    'created_at'  => $log->created_at->toIso8601String(),
                    'type'        => $log->type ?? 'general', 
                    // Safe access to properties json
                    'amount'      => isset($log->properties['amount']) ? $log->properties['amount'] : null, 
                ];
            });

        // 8. Construct Response matched to Frontend
        return response()->json([
            'user' => [
                'first_name' => $user->first_name ?? $user->name,
                'email'      => $user->email,
            ],
            'stats' => [
                'portfolio_value'          => $portfolioValue,
                'total_invested'           => $totalInvested,
                'portfolio_change_percent' => $portfolioChangePercent,
                'is_positive'              => $isPositive,
                'unrealized_gain'          => number_format($totalProfit, 2, '.', ''), 
                'wallet_balance'           => $walletBalance,
                'total_bonuses'            => (float) BonusTransaction::where('user_id', $user->id)->sum('amount'),
            ],
            'status' => [
                'kyc' => $user->kyc ? $user->kyc->status : 'pending',
                'subscription' => [
                    'name'              => $subscription ? ($subscription->plan->name ?? 'Active Plan') : 'No Plan',
                    'status'            => $subscription ? $subscription->status : 'inactive',
                    'next_payment_date' => $subscription ? $subscription->next_payment_date : null,
                ],
                'notification_count' => $user->unreadNotifications()->count(),
                'referrals' => [
                    'total'  => (int) ($referralStats->total ?? 0),
                    'active' => (int) ($referralStats->active ?? 0),
                ]
            ],
            'activity' => $activities
        ]);
    }

    // ... (Keep existing announcements/offers methods if needed)

    /**
     * Get Latest Announcements
     * Returns format expected by UserTopNav: { text, link }
     */
    public function announcements()
    {
        // TODO: Replace with actual Announcement model query when implemented
        // For now, return a banner-compatible format
        return response()->json([
            'id' => 1,
            'text' => 'Diwali Investment Bonanza! Get 2% extra units on all investments above ₹50k',
            'link' => '/deals',
            'type' => 'info',
            'created_at' => now()->subDays(2)->toIso8601String(),
        ]);
    }

    /**
     * Get Active Offers
     */
    public function offers()
    {
        return response()->json([
            'data' => [
                [
                    'id' => 101,
                    'code' => 'WELCOME500',
                    'description' => 'Flat ₹500 off on your first SIP.',
                    'discount_amount' => 500,
                    'valid_until' => now()->addMonth()->toIso8601String(),
                ]
            ]
        ]);
    }
}