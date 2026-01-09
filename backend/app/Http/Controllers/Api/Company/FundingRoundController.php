<?php

namespace App\Http\Controllers\Api\Company;

use App\Http\Controllers\Controller;
use App\Models\CompanyFundingRound;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FundingRoundController extends Controller
{
    /**
     * Get all funding rounds
     */
    public function index(Request $request)
    {
        $companyUser = $request->user();
        $company = $companyUser->company;

        // FIX: Add null check to prevent crash if company relationship missing
        if (!$company) {
            return response()->json([
                'success' => false,
                'message' => 'Company not found',
            ], 404);
        }


        $fundingRounds = CompanyFundingRound::where('company_id', $company->id)
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $fundingRounds,
        ], 200);
    }

    /**
     * Create a new funding round
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'round_name' => 'required|string|max:255',
            'amount_raised' => 'nullable|numeric',
            'currency' => 'sometimes|string|max:10',
            'valuation' => 'nullable|numeric',
            'round_date' => 'nullable|date',
            'investors' => 'nullable|array',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $companyUser = $request->user();
        $company = $companyUser->company;

        // FIX: Add null check to prevent crash if company relationship missing
        if (!$company) {
            return response()->json([
                'success' => false,
                'message' => 'Company not found',
            ], 404);
        }


        try {
            $data = $request->only(['round_name', 'amount_raised', 'currency', 'valuation', 'round_date', 'investors', 'description']);
            $data['company_id'] = $company->id;

            $fundingRound = CompanyFundingRound::create($data);

            // Update company's total funding and latest valuation
            if ($request->filled('amount_raised')) {
                $totalFunding = $company->fundingRounds()->sum('amount_raised');
                $company->update(['total_funding' => $totalFunding]);
            }

            if ($request->filled('valuation')) {
                $company->update(['latest_valuation' => $request->valuation]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Funding round added successfully',
                'funding_round' => $fundingRound,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add funding round',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update a funding round
     */
    public function update(Request $request, $id)
    {
        $companyUser = $request->user();
        $company = $companyUser->company;

        // FIX: Add null check to prevent crash if company relationship missing
        if (!$company) {
            return response()->json([
                'success' => false,
                'message' => 'Company not found',
            ], 404);
        }


        $fundingRound = CompanyFundingRound::where('company_id', $company->id)
            ->where('id', $id)
            ->first();

        if (!$fundingRound) {
            return response()->json([
                'success' => false,
                'message' => 'Funding round not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'round_name' => 'sometimes|string|max:255',
            'amount_raised' => 'nullable|numeric',
            'currency' => 'sometimes|string|max:10',
            'valuation' => 'nullable|numeric',
            'round_date' => 'nullable|date',
            'investors' => 'nullable|array',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $data = $request->only(['round_name', 'amount_raised', 'currency', 'valuation', 'round_date', 'investors', 'description']);
            $fundingRound->update($data);

            // Update company's total funding
            if ($request->filled('amount_raised')) {
                $totalFunding = $company->fundingRounds()->sum('amount_raised');
                $company->update(['total_funding' => $totalFunding]);
            }

            // Update company's latest valuation if this is the most recent round
            if ($request->filled('valuation')) {
                $latestRound = $company->fundingRounds()->latest()->first();
                if ($latestRound && $latestRound->id === $fundingRound->id) {
                    $company->update(['latest_valuation' => $request->valuation]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Funding round updated successfully',
                'funding_round' => $fundingRound->fresh(),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update funding round',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a funding round
     */
    public function destroy(Request $request, $id)
    {
        $companyUser = $request->user();
        $company = $companyUser->company;

        // FIX: Add null check to prevent crash if company relationship missing
        if (!$company) {
            return response()->json([
                'success' => false,
                'message' => 'Company not found',
            ], 404);
        }


        $fundingRound = CompanyFundingRound::where('company_id', $company->id)
            ->where('id', $id)
            ->first();

        if (!$fundingRound) {
            return response()->json([
                'success' => false,
                'message' => 'Funding round not found',
            ], 404);
        }

        try {
            $fundingRound->delete();

            // Update company's total funding
            $totalFunding = $company->fundingRounds()->sum('amount_raised');
            $company->update(['total_funding' => $totalFunding]);

            return response()->json([
                'success' => true,
                'message' => 'Funding round deleted successfully',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete funding round',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
