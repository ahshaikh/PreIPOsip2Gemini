<?php
// V-PHASE2-1730-055 (Created) | V-REMEDIATE-1730-189 (Auto-Debit Integrated) | V-FIX-TRANSACTION (Gemini)

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Services\RazorpayService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB; // FIX: Import DB Facade

class PlanController extends Controller
{
    protected $razorpay;

    public function __construct(RazorpayService $razorpay)
    {
        $this->razorpay = $razorpay;
    }

    public function index()
    {
        return Plan::with('configs', 'features')->latest()->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'monthly_amount' => 'required|numeric|min:0',
            'duration_months' => 'required|integer|min:1',
            'description' => 'nullable|string',
            'is_active' => 'required|boolean',
            'is_featured' => 'required|boolean',
            'available_from' => 'nullable|date',
            'available_until' => 'nullable|date',
            'allow_pause' => 'nullable|boolean',
            'max_pause_count' => 'nullable|integer|min:0',
            'max_pause_duration_months' => 'nullable|integer|min:1',
            'max_subscriptions_per_user' => 'nullable|integer|min:1',
            'min_investment' => 'nullable|numeric|min:0',
            'max_investment' => 'nullable|numeric|min:0',
            'display_order' => 'nullable|integer',
            'billing_cycle' => 'nullable|in:weekly,bi-weekly,monthly,quarterly,yearly',
            'trial_period_days' => 'nullable|integer|min:0',
            'metadata' => 'nullable|json',
            'features' => 'nullable|array',
            'features.*.feature_text' => 'required|string',
            'configs' => 'nullable|array',
        ]);

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
                $this->razorpay->createPlan($plan);

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

    public function update(Request $request, Plan $plan)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'monthly_amount' => 'sometimes|required|numeric|min:0',
            'duration_months' => 'sometimes|required|integer|min:1',
            'description' => 'nullable|string',
            'is_active' => 'sometimes|required|boolean',
            'is_featured' => 'sometimes|required|boolean',
            'available_from' => 'nullable|date',
            'available_until' => 'nullable|date',
            'allow_pause' => 'nullable|boolean',
            'max_pause_count' => 'nullable|integer|min:0',
            'max_pause_duration_months' => 'nullable|integer|min:1',
            'max_subscriptions_per_user' => 'nullable|integer|min:1',
            'min_investment' => 'nullable|numeric|min:0',
            'max_investment' => 'nullable|numeric|min:0',
            'display_order' => 'nullable|integer',
            'billing_cycle' => 'nullable|in:weekly,bi-weekly,monthly,quarterly,yearly',
            'trial_period_days' => 'nullable|integer|min:0',
            'metadata' => 'nullable|json',
            'configs' => 'nullable|array',
        ]);

        // FIX: Critical check to prevent price/cycle changes on active plans
        if ($plan->subscriptions()->exists()) {
            if (
                ($request->has('monthly_amount') && $request->monthly_amount != $plan->monthly_amount) ||
                ($request->has('billing_cycle') && $request->billing_cycle != $plan->billing_cycle)
            ) {
                return response()->json([
                    'message' => 'Cannot modify price or billing cycle for a plan with active subscriptions. Please archive this plan and create a new one.'
                ], 409);
            }
        }

        $plan->update($validated);

        if ($request->has('configs')) {
            foreach ($request->input('configs') as $key => $value) {
                $plan->configs()->updateOrCreate(
                    ['config_key' => $key],
                    ['value' => $value]
                );
            }
        }

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
}