<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CompanyController extends Controller
{
    public function index(Request $request)
    {
        $query = Company::query();

        if ($request->filled('sector')) {
            $query->bySector($request->sector);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $companies = $query->orderBy('name')->paginate($request->get('per_page', 15));

        return response()->json($companies);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'sector' => 'required|string|max:255',
            'description' => 'nullable|string',
            'logo' => 'nullable|string',
            'website' => 'nullable|url',
            'founded_year' => 'nullable|string|max:4',
            'headquarters' => 'nullable|string|max:255',
            'ceo_name' => 'nullable|string|max:255',
            'latest_valuation' => 'nullable|numeric|min:0',
            'funding_stage' => 'nullable|string|max:255',
            'total_funding' => 'nullable|numeric|min:0',
            'linkedin_url' => 'nullable|url',
            'twitter_url' => 'nullable|url',
            'facebook_url' => 'nullable|url',
            'key_metrics' => 'nullable|array',
            'investors' => 'nullable|array',
            'is_featured' => 'boolean',
            'status' => 'required|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $data['slug'] = Str::slug($data['name']) . '-' . Str::random(6);

        $company = Company::create($data);

        return response()->json([
            'message' => 'Company created successfully',
            'company' => $company
        ], 201);
    }

    public function show($id)
    {
        $company = Company::with('deals')->findOrFail($id);
        return response()->json($company);
    }

    public function update(Request $request, $id)
    {
        $company = Company::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'sector' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'logo' => 'nullable|string',
            'website' => 'nullable|url',
            'founded_year' => 'nullable|string|max:4',
            'headquarters' => 'nullable|string|max:255',
            'ceo_name' => 'nullable|string|max:255',
            'latest_valuation' => 'nullable|numeric|min:0',
            'funding_stage' => 'nullable|string|max:255',
            'total_funding' => 'nullable|numeric|min:0',
            'linkedin_url' => 'nullable|url',
            'twitter_url' => 'nullable|url',
            'facebook_url' => 'nullable|url',
            'key_metrics' => 'nullable|array',
            'investors' => 'nullable|array',
            'is_featured' => 'boolean',
            'status' => 'sometimes|required|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        if (isset($data['name']) && $data['name'] !== $company->name) {
            $data['slug'] = Str::slug($data['name']) . '-' . Str::random(6);
        }

        $company->update($data);

        return response()->json([
            'message' => 'Company updated successfully',
            'company' => $company
        ]);
    }

    public function destroy($id)
    {
        $company = Company::findOrFail($id);
        $company->delete();

        return response()->json(['message' => 'Company deleted successfully']);
    }
}
