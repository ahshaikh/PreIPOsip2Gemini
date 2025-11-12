<?php
// V-REMEDIATE-1730-167 (Full Update Capability)

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PlanController extends Controller
{
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
            'features' => 'nullable|array',
            'features.*.feature_text' => 'required|string',
            'configs' => 'nullable|array', 
        ]);

        $plan = Plan::create($validated + ['slug' => Str::slug($validated['name'])]);

        if (!empty($validated['features'])) {
            $plan->features()->createMany($validated['features']);
        }
        
        if (!empty($validated['configs'])) {
            foreach ($validated['configs'] as $key => $value) {
                // Value is already an array/json from the request
                $plan->configs()->create(['config_key' => $key, 'value' => $value]);
            }
        }
        
        return response()->json($plan->load('configs', 'features'), 201);
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
            'configs' => 'nullable|array', // Expecting keyed array: 'progressive_config' => {...}
        ]);
        
        $plan->update($validated);

        // --- NEW: Handle Config Updates ---
        if ($request->has('configs')) {
            foreach ($request->input('configs') as $key => $value) {
                $plan->configs()->updateOrCreate(
                    ['config_key' => $key],
                    ['value' => $value] // Laravel casts this to JSON automatically due to model casts
                );
            }
        }
        // ----------------------------------

        return response()->json($plan->load('configs', 'features'));
    }

    public function destroy(Plan $plan)
    {
        // Prevent deletion if active subscriptions exist
        if ($plan->subscriptions()->exists()) { // Assuming relationship exists
             return response()->json(['message' => 'Cannot delete plan with active subscriptions.'], 409);
        }
        $plan->delete();
        return response()->noContent();
    }
}