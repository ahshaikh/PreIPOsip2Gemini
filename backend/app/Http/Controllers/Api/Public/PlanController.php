<?php
// V-PHASE4-1730-101 (Created) | V-FINAL-1730-483 (Scheduling Enforced)

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\Request;

class PlanController extends Controller
{
    /**
     * Display a listing of publicly available plans.
     */
    public function index()
    {
        // --- LOGIC CHANGE ---
        // Use the new scope to respect start/end dates
        $plans = Plan::publiclyAvailable()
                     ->with('features')
                     ->orderBy('display_order', 'asc')
                     ->get();
        // --------------------
        
        return response()->json($plans);
    }

    /**
     * Display a single publicly available plan.
     */
    public function show($slug)
    {
        // --- LOGIC CHANGE ---
        $plan = Plan::publiclyAvailable()
                    ->where('slug', $slug)
                    ->with('features', 'configs')
                    ->firstOrFail();
        // --------------------

        return response()->json($plan);
    }
}