// V-PHASE2-1730-050
<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\Request;

class PublicPlanController extends Controller
{
    /**
     * Display a listing of active plans.
     */
    public function index()
    {
        $plans = Plan::where('is_active', true)
            ->with('features')
            ->orderBy('display_order')
            ->get();
            
        return response()->json($plans);
    }

    /**
     * Display the specified plan.
     */
    public function show($slug)
    {
        $plan = Plan::where('slug', $slug)
            ->where('is_active', true)
            ->with('features', 'configs')
            ->firstOrFail();
            
        return response()->json($plan);
    }
}