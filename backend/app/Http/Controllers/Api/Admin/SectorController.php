<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sector;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SectorController extends Controller
{
    public function index(Request $request)
    {
        $query = Sector::query();

        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        $sectors = $query->withCount(['companies', 'deals', 'products'])
                         ->orderBy('sort_order')
                         ->paginate(20);

        return response()->json($sectors);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:sectors,name',
            'description' => 'nullable|string',
            'icon' => 'nullable|string',
            'color' => 'nullable|string|max:7',
            'sort_order' => 'nullable|integer',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $sector = Sector::create($validator->validated());

        return response()->json([
            'message' => 'Sector created successfully',
            'sector' => $sector
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $sector = Sector::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:sectors,name,' . $id,
            'description' => 'nullable|string',
            'icon' => 'nullable|string',
            'color' => 'nullable|string|max:7',
            'sort_order' => 'nullable|integer',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $sector->update($validator->validated());

        return response()->json([
            'message' => 'Sector updated successfully',
            'sector' => $sector
        ]);
    }

    public function destroy($id)
    {
        try {
            $sector = Sector::findOrFail($id);
            $sector->delete();

            return response()->json([
                'message' => 'Sector deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 422);
        }
    }
}
