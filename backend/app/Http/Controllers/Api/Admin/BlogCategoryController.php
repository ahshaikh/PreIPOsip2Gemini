<?php
// V-CMS-ENHANCEMENT-005 | BlogCategoryController
// Created: 2025-12-10 | Purpose: CRUD operations for blog categories

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogCategory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BlogCategoryController extends Controller
{
    /**
     * Display a listing of blog categories
     * GET /api/v1/admin/blog-categories
     */
    public function index(Request $request): JsonResponse
    {
        $query = BlogCategory::withCount('blogPosts')->ordered();

        // Filter by active status
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        // Search by name
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $categories = $query->get();

        return response()->json($categories);
    }

    /**
     * Store a newly created category
     * POST /api/v1/admin/blog-categories
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:blog_categories,name',
            'slug' => 'nullable|string|max:255|unique:blog_categories,slug',
            'description' => 'nullable|string|max:1000',
            'color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'icon' => 'nullable|string|max:50',
            'display_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        // Auto-generate slug if not provided
        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        // Set defaults
        $validated['color'] = $validated['color'] ?? '#667eea';
        $validated['display_order'] = $validated['display_order'] ?? 0;
        $validated['is_active'] = $validated['is_active'] ?? true;

        $category = BlogCategory::create($validated);

        return response()->json([
            'message' => 'Category created successfully',
            'data' => $category->load('blogPosts:id,category_id,title')
        ], 201);
    }

    /**
     * Display the specified category
     * GET /api/v1/admin/blog-categories/{id}
     */
    public function show(BlogCategory $blogCategory): JsonResponse
    {
        return response()->json([
            'data' => $blogCategory->load('blogPosts:id,category_id,title,status,published_at')
        ]);
    }

    /**
     * Update the specified category
     * PUT/PATCH /api/v1/admin/blog-categories/{id}
     */
    public function update(Request $request, BlogCategory $blogCategory): JsonResponse
    {
        $validated = $request->validate([
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('blog_categories', 'name')->ignore($blogCategory->id)
            ],
            'slug' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
                Rule::unique('blog_categories', 'slug')->ignore($blogCategory->id)
            ],
            'description' => 'nullable|string|max:1000',
            'color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'icon' => 'nullable|string|max:50',
            'display_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        // Auto-update slug if name changed but slug wasn't provided
        if (isset($validated['name']) && !isset($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $blogCategory->update($validated);

        return response()->json([
            'message' => 'Category updated successfully',
            'data' => $blogCategory->fresh()->load('blogPosts:id,category_id,title')
        ]);
    }

    /**
     * Remove the specified category
     * DELETE /api/v1/admin/blog-categories/{id}
     */
    public function destroy(BlogCategory $blogCategory): JsonResponse
    {
        // Check if category has posts
        $postsCount = $blogCategory->blogPosts()->count();

        if ($postsCount > 0) {
            return response()->json([
                'message' => "Cannot delete category. It has {$postsCount} blog post(s). Please reassign or delete the posts first.",
                'posts_count' => $postsCount
            ], 422);
        }

        $blogCategory->delete();

        return response()->json([
            'message' => 'Category deleted successfully'
        ]);
    }

    /**
     * Bulk reorder categories
     * POST /api/v1/admin/blog-categories/reorder
     */
    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'categories' => 'required|array',
            'categories.*.id' => 'required|exists:blog_categories,id',
            'categories.*.display_order' => 'required|integer|min:0',
        ]);

        foreach ($validated['categories'] as $categoryData) {
            BlogCategory::where('id', $categoryData['id'])
                ->update(['display_order' => $categoryData['display_order']]);
        }

        return response()->json([
            'message' => 'Categories reordered successfully'
        ]);
    }

    /**
     * Get active categories for dropdown (lightweight)
     * GET /api/v1/admin/blog-categories/active
     */
    public function active(): JsonResponse
    {
        $categories = BlogCategory::active()
            ->ordered()
            ->select('id', 'name', 'slug', 'color', 'icon')
            ->get();

        return response()->json($categories);
    }

    /**
     * Get category statistics
     * GET /api/v1/admin/blog-categories/stats
     */
    public function stats(): JsonResponse
    {
        $stats = [
            'total_categories' => BlogCategory::count(),
            'active_categories' => BlogCategory::where('is_active', true)->count(),
            'inactive_categories' => BlogCategory::where('is_active', false)->count(),
            'categories_with_posts' => BlogCategory::has('blogPosts')->count(),
            'most_used_category' => BlogCategory::withCount('blogPosts')
                ->orderBy('blog_posts_count', 'desc')
                ->first(),
        ];

        return response()->json($stats);
    }
}
