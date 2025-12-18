<?php

namespace App\Http\Controllers\Api\Calculator;

use App\Http\Controllers\Controller;
use App\Traits\CalculatesReturns;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * SimulationController
 * * [AUDIT FIX]: Provides backend-validated math for the frontend UI.
 */
class SimulationController extends Controller
{
    use CalculatesReturns;

    public function simulate(Request $request): JsonResponse
    {
        $request->validate([
            'principal' => 'required|numeric|min:100',
            'annual_rate' => 'required|numeric',
            'tenure_months' => 'required|integer|min:1',
        ]);

        // Convert to Paise for high-precision calculation
        $principalPaise = (int) ($request->principal * 100);
        
        $results = $this->calculateProjectedBonus(
            $principalPaise,
            $request->annual_rate,
            $request->tenure_months
        );

        return response()->json($results);
    }
}