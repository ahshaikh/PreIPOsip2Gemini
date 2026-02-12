<?php
// V-PHASE2-1730-050 (Created) | V-FINAL-1730-483 (Scheduling Enforced)

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
     * Accepts either slug or numeric ID.
     */
    public function show($identifier)
    {
        // --- LOGIC CHANGE ---
        // Accept both slug and numeric ID for flexibility
        $query = Plan::publiclyAvailable()
                    ->with('features', 'configs');

        if (is_numeric($identifier)) {
            $plan = $query->where('id', $identifier)->firstOrFail();
        } else {
            $plan = $query->where('slug', $identifier)->firstOrFail();
        }
        // --------------------

        return response()->json($plan);
    }
}