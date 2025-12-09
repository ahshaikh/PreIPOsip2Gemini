<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\KnowledgeBaseCategory;
use Illuminate\Http\Request;

class KnowledgeBaseCategoryController extends Controller
{
    /**
     * List all categories
     * GET /api/v1/admin/knowledge-base/categories
     */
    public function index(Request $request)
    {
        $query = KnowledgeBaseCategory::with(['parent', 'children'])
            ->withCount('articles');

        // Filter by active status
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Filter by parent
        if ($request->filled('parent_id')) {
            if ($request->parent_id === 'root') {
                $query->whereNull('parent_id');
            } else {
                $query->where('parent_id', $request->parent_id);
            }
        }

        // Search
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        $categories = $query->orderBy('sort_order')->orderBy('name')->get();

        return response()->json(['data' => $categories]);
    }

    /**
     * Get category tree
     * GET /api/v1/admin/knowledge-base/categories/tree
     */
    public function tree()
    {
        $categories = KnowledgeBaseCategory::with(['children' => function ($query) {
            $query->active()->orderBy('sort_order');
        }])
        ->active()
        ->whereNull('parent_id')
        ->orderBy('sort_order')
        ->get();

        return response()->json(['data' => $categories]);
    }

    /**
     * Show single category
     * GET /api/v1/admin/knowledge-base/categories/{id}
     */
    public function show(KnowledgeBaseCategory $category)
    {
        $category->load(['parent', 'children', 'articles']);

        return response()->json(['data' => $category]);
    }

    /**
     * Create new category
     * POST /api/v1/admin/knowledge-base/categories
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|unique:knowledge_base_categories,slug',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer',
            'is_active' => 'boolean',
            'parent_id' => 'nullable|exists:knowledge_base_categories,id',
        ]);

        $category = KnowledgeBaseCategory::create($validated);

        return response()->json([
            'message' => 'Knowledge base category created successfully',
            'data' => $category,
        ], 201);
    }

    /**
     * Update category
     * PUT /api/v1/admin/knowledge-base/categories/{id}
     */
    public function update(Request $request, KnowledgeBaseCategory $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|unique:knowledge_base_categories,slug,' . $category->id,
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer',
            'is_active' => 'boolean',
            'parent_id' => 'nullable|exists:knowledge_base_categories,id',
        ]);

        // Prevent category from being its own parent
        if (isset($validated['parent_id']) && $validated['parent_id'] == $category->id) {
            return response()->json([
                'message' => 'A category cannot be its own parent',
            ], 422);
        }

        $category->update($validated);

        return response()->json([
            'message' => 'Knowledge base category updated successfully',
            'data' => $category,
        ]);
    }

    /**
     * Delete category
     * DELETE /api/v1/admin/knowledge-base/categories/{id}
     */
    public function destroy(KnowledgeBaseCategory $category)
    {
        // Check if category has articles
        if ($category->articles()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete category with articles. Please move or delete articles first.',
            ], 422);
        }

        // Check if category has children
        if ($category->children()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete category with subcategories. Please move or delete subcategories first.',
            ], 422);
        }

        $category->delete();

        return response()->json([
            'message' => 'Knowledge base category deleted successfully',
        ]);
    }

    /**
     * Reorder categories
     * POST /api/v1/admin/knowledge-base/categories/reorder
     */
    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'categories' => 'required|array',
            'categories.*.id' => 'required|exists:knowledge_base_categories,id',
            'categories.*.sort_order' => 'required|integer',
        ]);

        foreach ($validated['categories'] as $item) {
            KnowledgeBaseCategory::where('id', $item['id'])
                ->update(['sort_order' => $item['sort_order']]);
        }

        return response()->json([
            'message' => 'Categories reordered successfully',
        ]);
    }
}
