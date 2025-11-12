<?php
// V-REMEDIATE-1730-160 (Full Implementation)

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProfitShare;
use App\Models\UserProfitShare;
use App\Models\Subscription;
use App\Models\BonusTransaction;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProfitShareController extends Controller
{
    /**
     * Display a listing of all profit share periods.
     */
    public function index()
    {
        return ProfitShare::latest()->paginate(25);
    }

    /**
     * Create a new profit share period.
     */
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
            'status' => 'pending', // Pending calculation
        ]);

        return response()->json($period, 201);
    }

    /**
     * Display the specified profit share period.
     */
    public function show(ProfitShare $profitShare)
    {
        return $profitShare->load('distributions.user:id,username');
    }

    /**
     * Step 2: Calculate the distribution for a 'pending' period.
     */
    public function calculate(Request $request, ProfitShare $profitShare)
    {
        if ($profitShare->status !== 'pending') {
            return response()->json(['message' => 'This period is not pending calculation.'], 400);
        }

        // Get all eligible subscriptions active during this period
        $subscriptions = Subscription::where('status', 'active')
            ->where('start_date', '<=', $profitShare->end_date)
            ->with('plan.configs', 'user')
            ->get();

        $totalInvestment = 0;
        $eligibleUsers = [];

        foreach ($subscriptions as $sub) {
            // TODO: This is simplified. Real logic would check *how much* was invested
            // during the period, not just the plan amount.
            $investmentAmount = $sub->plan->monthly_amount * 3; // Simple proxy for 3 months
            $totalInvestment += $investmentAmount;
            
            // Get plan-specific profit share %
            $config = $sub->plan->configs->where('config_key', 'profit_share')->first();
            $sharePercent = $config ? ($config->value['percentage'] / 100) : 0.05; // Default 5%
            
            $eligibleUsers[] = [
                'user_id' => $sub->user_id,
                'subscription_id' => $sub->id,
                'investment_amount' => $investmentAmount,
                'share_percent' => $sharePercent,
            ];
        }

        if ($totalInvestment == 0) {
            return response()->json(['message' => 'No eligible investments found for this period.'], 400);
        }

        DB::beginTransaction();
        try {
            // Clear any previous calculations
            $profitShare->distributions()->delete();
            
            $totalDistributed = 0;

            foreach ($eligibleUsers as $data) {
                // User's share of the pool, weighted by their plan's %
                $investmentRatio = $data['investment_amount'] / $totalInvestment;
                $userShare = $profitShare->total_pool * $investmentRatio * $data['share_percent'];
                
                if ($userShare > 0) {
                    $profitShare->distributions()->create([
                        'user_id' => $data['user_id'],
                        'amount' => $userShare,
                    ]);
                    $totalDistributed += $userShare;
                }
            }

            $profitShare->status = 'calculated';
            $profitShare->save();
            DB::commit();

            return response()->json([
                'message' => 'Distribution calculated successfully.',
                'total_distributed' => $totalDistributed,
                'eligible_users' => count($eligibleUsers),
                'distributions' => $profitShare->distributions()->with('user:id,username')->get(),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Profit Share Calculation Failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'An error occurred during calculation.'], 500);
        }
    }

    /**
     * Step 3: Distribute the funds for a 'calculated' period.
     */
    public function distribute(Request $request, ProfitShare $profitShare)
    {
        if ($profitShare->status !== 'calculated') {
            return response()->json(['message' => 'This period has not been calculated or is already distributed.'], 400);
        }

        $distributions = $profitShare->distributions()->with('user.wallet')->get();
        if ($distributions->isEmpty()) {
            return response()->json(['message' => 'No distributions to process.'], 400);
        }

        DB::beginTransaction();
        try {
            foreach ($distributions as $dist) {
                $user = $dist->user;
                $wallet = $user->wallet;
                $amount = $dist->amount;
                
                // 1. Create the Bonus Transaction
                $bonus = BonusTransaction::create([
                    'user_id' => $user->id,
                    'subscription_id' => $user->subscription->id, // Assumes one active sub
                    'type' => 'profit_share',
                    'amount' => $amount,
                    'multiplier_applied' => 1,
                    'description' => "Quarterly Profit Share: {$profitShare->period_name}",
                ]);

                // 2. Credit their wallet
                $wallet->balance += $amount;
                $wallet->save();
                
                // 3. Create the master Transaction
                Transaction::create([
                   'user_id' => $user->id,
                   'wallet_id' => $wallet->id,
                   'type' => 'bonus_credit',
                   'amount' => $amount,
                   'balance_after' => $wallet->balance,
                   'description' => "Profit Share: {$profitShare->period_name}",
                   'reference_type' => BonusTransaction::class,
                   'reference_id' => $bonus->id,
                ]);
                
                // 4. Link the bonus transaction to the distribution
                $dist->update(['bonus_transaction_id' => $bonus->id]);
            }

            $profitShare->update(['status' => 'distributed']);
            DB::commit();
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Profit Share Distribution Failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'An error occurred during distribution.'], 500);
        }
        
        // TODO: Dispatch jobs to notify all users

        return response()->json(['message' => 'Profit share distributed successfully to ' . $distributions->count() . ' users.']);
    }
}