<?php
// V-FINAL-1730-486 (Created)

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\CannedResponse;
use Illuminate\Http\Request;

class CannedResponseController extends Controller
{
    /**
     * List all canned responses
     * GET /api/v1/admin/canned-responses
     */
    public function index(Request $request)
    {
        $query = CannedResponse::query();

        // Filter by category
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        // Filter by active status
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Search
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                  ->orWhere('content', 'like', '%' . $request->search . '%');
            });
        }

        return response()->json([
            'data' => $query->orderBy('category')->orderBy('title')->get(),
        ]);
    }

    /**
     * Get all canned response categories
     * GET /api/v1/admin/canned-responses/categories
     */
    public function categories()
    {
        $categories = CannedResponse::select('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        return response()->json(['categories' => $categories]);
    }

    /**
     * Create new canned response
     * POST /api/v1/admin/canned-responses
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'category' => 'required|string|max:100',
            'is_active' => 'boolean',
        ]);

        $response = CannedResponse::create($validated);

        return response()->json([
            'message' => 'Canned response created successfully',
            'data' => $response,
        ], 201);
    }

    /**
     * Show single canned response
     * GET /api/v1/admin/canned-responses/{id}
     */
    public function show(CannedResponse $cannedResponse)
    {
        return response()->json(['data' => $cannedResponse]);
    }

    /**
     * Update canned response
     * PUT /api/v1/admin/canned-responses/{id}
     */
    public function update(Request $request, CannedResponse $cannedResponse)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'category' => 'required|string|max:100',
            'is_active' => 'boolean',
        ]);

        $cannedResponse->update($validated);

        return response()->json([
            'message' => 'Canned response updated successfully',
            'data' => $cannedResponse,
        ]);
    }

    /**
     * Delete canned response
     * DELETE /api/v1/admin/canned-responses/{id}
     */
    public function destroy(CannedResponse $cannedResponse)
    {
        $cannedResponse->delete();

        return response()->json([
            'message' => 'Canned response deleted successfully',
        ]);
    }
}