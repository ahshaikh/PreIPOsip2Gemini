// V-PHASE2-1730-055
<?php

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
            'configs' => 'nullable|array', // e.g., ['progressive_rate' => 0.5]
        ]);

        $plan = Plan::create($validated + ['slug' => Str::slug($validated['name'])]);

        if (!empty($validated['features'])) {
            $plan->features()->createMany($validated['features']);
        }
        
        if (!empty($validated['configs'])) {
            foreach ($validated['configs'] as $key => $value) {
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
        // Simplified update - a real app would handle configs/features update/delete
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'monthly_amount' => 'sometimes|required|numeric|min:0',
            // ... other plan fields
        ]);
        
        $plan->update($validated);

        return response()->json($plan->load('configs', 'features'));
    }

    public function destroy(Plan $plan)
    {
        // TODO: Add check for active subscriptions before deleting
        $plan->delete();
        return response()->noContent();
    }
}