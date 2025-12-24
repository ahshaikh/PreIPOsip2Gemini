<?php
// V-PHASE2-1730-055 (Created) | V-REMEDIATE-1730-189 (Auto-Debit Integrated) | V-FIX-TRANSACTION (Gemini) | V-AUDIT-FIX-REFACTOR | V-FIX-PERSISTENCE-2025

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Services\RazorpayService;
use App\Http\Requests\Admin\StorePlanRequest; // [AUDIT FIX]
use App\Http\Requests\Admin\UpdatePlanRequest; // [AUDIT FIX]
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PlanController extends Controller
{
    protected $razorpay;

    public function __construct(RazorpayService $razorpay)
    {
        $this->razorpay = $razorpay;
    }

    public function index()
    {
        return Plan::with('configs', 'features')
            ->withCount('subscriptions')
            ->latest()
            ->get();
    }
    /**
     * Store a newly created resource in storage.
     */
    
    public function store(StorePlanRequest $request)
    {
        $validated = $request->validated();

        // FIX: Wrap in transaction to prevent "Ghost Plans" if Razorpay fails
        try {
            $plan = DB::transaction(function () use ($validated) {
                $plan = Plan::create($validated + ['slug' => Str::slug($validated['name'])]);

                if (!empty($validated['features'])) {
                    $plan->features()->createMany($validated['features']);
                }
                
                if (!empty($validated['configs'])) {
                    foreach ($validated['configs'] as $key => $value) {
                        $plan->configs()->create(['config_key' => $key, 'value' => $value]);
                    }
                }

                // Sync with Razorpay inside transaction
                // If this throws an exception, DB::transaction will rollback everything
                $this->razorpay->createOrUpdatePlan($plan); // [AUDIT FIX] Method renamed in Interface refactor

                return $plan;
            });

            return response()->json($plan->load('configs', 'features'), 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create plan. Payment gateway synchronization error.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(Plan $plan)
    {
        return $plan->load('configs', 'features');
    }

    public function update(UpdatePlanRequest $request, Plan $plan)
    {
        $validated = $request->validated();

        // FIX: Critical check to prevent price/cycle changes on active plans
        // Only check for ACTIVE subscriptions (not cancelled, completed, etc.)
        // Valid statuses from migration: active, paused, cancelled, completed
        $activeSubscriptionCount = $plan->subscriptions()
            ->whereIn('status', ['active', 'paused'])
            ->count();

        if ($activeSubscriptionCount > 0) {
            // Check if price actually changed (with tolerance for floating point)
            $priceChanged = false;
            if (isset($validated['monthly_amount'])) {
                $oldPrice = (float)$plan->monthly_amount;
                $newPrice = (float)$validated['monthly_amount'];
                // Use epsilon comparison for floats (tolerance of 0.01)
                $priceChanged = abs($newPrice - $oldPrice) > 0.01;
            }

            // Check if billing_cycle actually changed
            // NULL and 'monthly' are treated as equivalent (monthly is the default)
            $billingChanged = false;
            if (isset($validated['billing_cycle'])) {
                $oldCycle = $plan->billing_cycle ?? 'monthly';
                $newCycle = $validated['billing_cycle'] ?? 'monthly';
                $billingChanged = $oldCycle !== $newCycle;
            }

            if ($priceChanged || $billingChanged) {
                return response()->json([
                    'message' => "Cannot modify price or billing cycle for a plan with {$activeSubscriptionCount} active subscription(s). All other fields can be edited."
                ], 409);
            }
        }

        // [PROTOCOL 7 FIX] Ensure Persistence
        // 1. Handle Slug Generation only if name changes
        if (isset($validated['name']) && $validated['name'] !== $plan->name) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        // 2. Explicitly handle boolean fields (is_active, is_featured, allow_pause)
        // If they are present in request, cast them. If missing, they might be unchecked checkboxes.
        if ($request->has('is_active')) $plan->is_active = $request->boolean('is_active');
        if ($request->has('is_featured')) $plan->is_featured = $request->boolean('is_featured');
        if ($request->has('allow_pause')) $plan->allow_pause = $request->boolean('allow_pause');

        // 3. Fill remaining attributes
        // Exclude features/configs from direct fill as they are relations
        $inputs = collect($validated)->except(['features', 'configs', 'is_active', 'is_featured', 'allow_pause'])->toArray();
        $plan->fill($inputs);

        // 4. Force Save
        $plan->save();

        // Sync features (must be done explicitly - not mass assignable)
        if ($request->has('features')) {
            // Delete all existing features
            $plan->features()->delete();
            // Create new features from array
            if (!empty($validated['features'])) {
                foreach ($validated['features'] as $featureText) {
                    $plan->features()->create([
                        'feature_text' => is_string($featureText) ? $featureText : $featureText['feature_text'] ?? '',
                    ]);
                }
            }
        }

        if ($request->has('configs')) {
            foreach ($request->input('configs') as $key => $value) {
                $plan->configs()->updateOrCreate(
                    ['config_key' => $key],
                    ['value' => $value]
                );
            }
        }

        Log::info("Plan ID {$plan->id} updated by Admin ID " . auth()->id());

        return response()->json($plan->load('configs', 'features'));
    }

    public function destroy(Plan $plan)
    {
        if ($plan->subscriptions()->exists()) {
             return response()->json(['message' => 'Cannot delete plan with active subscriptions.'], 409);
        }
        $plan->delete();
        return response()->noContent();
    }

    /**
     * Get plan statistics for admin dashboard
     */
    public function stats()
    {
        $totalSubscribers = DB::table('subscriptions')
            ->whereIn('status', ['active', 'paused'])
            ->distinct('user_id')
            ->count();

        $monthlyRevenue = DB::table('subscriptions')
            ->join('plans', 'subscriptions.plan_id', '=', 'plans.id')
            ->where('subscriptions.status', 'active')
            ->sum('plans.monthly_amount');

        $avgPlanValue = DB::table('plans')
            ->avg(DB::raw('monthly_amount * duration_months'));

        return response()->json([
            'total_subscribers' => $totalSubscribers,
            'monthly_revenue' => $monthlyRevenue,
            'avg_plan_value' => round($avgPlanValue, 2),
        ]);
    }
}