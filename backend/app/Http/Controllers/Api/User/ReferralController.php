<?php
// V-PHASE3-1730-093 (Created) | V-FINAL-1730-464 | V-AUDIT-FIX-MODULE9

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Carbon;

class ReferralController extends Controller
{
    /**
     * Get User Referral Stats & Code
     * Endpoint: /api/v1/user/referrals
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            // Basic Info
            $referralCode = $user->referral_code ?? 'GENERATE';
            $referralLink = url('/register?ref=' . $referralCode);

            // Stats Defaults
            $totalInvited = 0;
            $totalEarned = 0;
            $history = [];

            // 1. Count Invites
            if (Schema::hasTable('referrals')) {
                $totalInvited = DB::table('referrals')
                    ->where('referrer_id', $user->id)
                    ->count();
            }

            // 2. Calculate Earnings (Optimized: DB Sum)
            // MODULE 9 FIX: Use DB facade to sum directly in SQL engine, avoiding model hydration overhead.
            if (Schema::hasTable('bonus_transactions')) {
                $totalEarned = DB::table('bonus_transactions')
                    ->where('user_id', $user->id)
                    ->where('type', 'referral')
                    ->sum('amount');
            }

            // 3. Get Recent Referral History
            if (Schema::hasTable('referrals')) {
                $history = DB::table('referrals')
                    ->join('users', 'referrals.referred_id', '=', 'users.id')
                    ->where('referrals.referrer_id', $user->id)
                    ->select(
                        'users.username', 
                        'users.first_name',
                        'users.email', 
                        'referrals.status', 
                        'referrals.created_at'
                    )
                    ->latest('referrals.created_at')
                    ->limit(10)
                    ->get()
                    ->map(function($ref) {
                        $email = $ref->email ?? '';
                        $maskedEmail = strlen($email) > 4 ? substr($email, 0, 2) . '***' . substr($email, strpos($email, '@')) : '***';

                        return [
                            'user' => $ref->first_name ?? $ref->username ?? 'User',
                            'email_masked' => $maskedEmail,
                            'status' => ucfirst($ref->status),
                            'date' => Carbon::parse($ref->created_at)->format('d M Y'),
                        ];
                    });
            }

            return response()->json([
                'referral_code' => $referralCode,
                'referral_link' => $referralLink,
                'stats' => [
                    'total_invited' => $totalInvited,
                    'total_earned' => (float) $totalEarned,
                ],
                'recent_referrals' => $history
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'referral_code' => $request->user()->referral_code ?? 'ERROR', 
                'referral_link' => '', 
                'stats' => ['total_invited' => 0, 'total_earned' => 0],
                'recent_referrals' => []
            ]);
        }
    }

    /**
     * Get Paginated Referral List
     * Endpoint: /api/v1/user/referrals/list
     * [PROTOCOL 7 IMPLEMENTATION]
     */
    public function list(Request $request): JsonResponse
    {
        $request->validate([
            'status' => 'nullable|string',
            'page' => 'nullable|integer',
        ]);

        try {
            $user = $request->user();

            if (!Schema::hasTable('referrals')) {
                return response()->json([
                    'data' => [],
                    'current_page' => 1,
                    'last_page' => 1,
                    'total' => 0,
                ]);
            }

            $query = DB::table('referrals')
                ->join('users', 'referrals.referred_id', '=', 'users.id')
                ->where('referrals.referrer_id', $user->id)
                ->select(
                    'referrals.id',
                    'users.username',
                    'users.first_name',
                    'users.email',
                    'referrals.status',
                    'referrals.created_at'
                );

            // Apply status filter
            if ($request->has('status') && $request->status !== 'all') {
                $query->where('referrals.status', $request->status);
            }

            // Dynamic Pagination
            $perPage = function_exists('setting') ? (int) setting('records_per_page', 15) : 15;

            $referrals = $query->latest('referrals.created_at')
                ->paginate($perPage)
                ->appends($request->query());

            return response()->json($referrals);

        } catch (\Throwable $e) {
            return response()->json([
                'data' => [],
                'current_page' => 1,
                'last_page' => 1,
                'total' => 0,
            ]);
        }
    }

    /**
     * Get Referral Rewards History
     * Endpoint: /api/v1/user/referrals/rewards
     */
    public function rewards(Request $request): JsonResponse
    {
        $request->validate([
            'page' => 'nullable|integer',
        ]);

        try {
            $user = $request->user();

            if (!Schema::hasTable('bonus_transactions')) {
                return response()->json(['data' => [], 'meta' => ['total' => 0]]);
            }

            // Dynamic Pagination (Protocol 7)
            $perPage = function_exists('setting') ? (int) setting('records_per_page', 15) : 15;

            $rewards = DB::table('bonus_transactions')
                ->where('user_id', $user->id)
                ->where('type', 'referral')
                ->orderBy('created_at', 'desc')
                ->paginate($perPage)
                ->appends($request->query());

            return response()->json($rewards);

        } catch (\Throwable $e) {
            return response()->json(['data' => [], 'meta' => ['total' => 0]]);
        }
    }
}