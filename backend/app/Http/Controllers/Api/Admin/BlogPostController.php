<?php
// V-FINAL-1730-189 | V-CMS-ENHANCEMENT-006 (Updated)
// Updated: 2025-12-10 | Added support for new blog fields and categories

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Models\BlogCategory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BlogPostController extends Controller
{
    /**
     * Display a listing of blog posts (Admin)
     * GET /api/v1/admin/blog-posts
     */
    public function index(Request $request): JsonResponse
    {
        $query = BlogPost::with(['author:id,name,email', 'blogCategory:id,name,color'])
            ->latest();

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by category
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Search
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        // Filter by featured
        if ($request->filled('featured')) {
            $query->where('is_featured', filter_var($request->featured, FILTER_VALIDATE_BOOLEAN));
        }

        $posts = $query->get();

        return response()->json($posts);
    }

    /**
     * Store a newly created blog post
     * POST /api/v1/admin/blog-posts
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:blog_posts,slug',
            'content' => 'required|string',
            'excerpt' => 'nullable|string|max:500',
            'featured_image' => 'nullable|url|max:2048',
            'category_id' => 'nullable|exists:blog_categories,id',
            'category' => 'nullable|string|max:100', // Old field for backward compatibility
            'status' => 'required|in:draft,published',
            'is_featured' => 'nullable|boolean',
            'seo_title' => 'nullable|string|max:60',
            'seo_description' => 'nullable|string|max:160',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
        ]);

        // Auto-generate slug if not provided
        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['title']);
        }

        // Set author to current user
        $validated['author_id'] = $request->user()->id;

        // Set defaults
        $validated['is_featured'] = $validated['is_featured'] ?? false;

        $post = BlogPost::create($validated);

        return response()->json([
            'message' => 'Blog post created successfully',
            'data' => $post->load(['author:id,name', 'blogCategory:id,name,color'])
        ], 201);
    }

    /**
     * Display the specified blog post
     * GET /api/v1/admin/blog-posts/{id}
     */
    public function show(BlogPost $blogPost): JsonResponse
    {
        return response()->json([
            'data' => $blogPost->load(['author:id,name,email', 'blogCategory:id,name,color,slug'])
        ]);
    }

    /**
     * Update the specified blog post
     * PUT/PATCH /api/v1/admin/blog-posts/{id}
     */
    public function update(Request $request, BlogPost $blogPost): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'slug' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
                Rule::unique('blog_posts', 'slug')->ignore($blogPost->id)
            ],
            'content' => 'sometimes|required|string',
            'excerpt' => 'nullable|string|max:500',
            'featured_image' => 'nullable|url|max:2048',
            'category_id' => 'nullable|exists:blog_categories,id',
            'category' => 'nullable|string|max:100',
            'status' => 'sometimes|required|in:draft,published',
            'is_featured' => 'nullable|boolean',
            'seo_title' => 'nullable|string|max:60',
            'seo_description' => 'nullable|string|max:160',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
        ]);

        // Auto-update slug if title changed but slug wasn't provided
        if (isset($validated['title']) && !isset($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['title']);
        }

        $blogPost->update($validated);

        return response()->json([
            'message' => 'Blog post updated successfully',
            'data' => $blogPost->fresh()->load(['author:id,name', 'blogCategory:id,name,color'])
        ]);
    }

    /**
     * Remove the specified blog post
     * DELETE /api/v1/admin/blog-posts/{id}
     */
    public function destroy(BlogPost $blogPost): JsonResponse
    {
        $blogPost->delete();

        return response()->json([
            'message' => 'Blog post deleted successfully'
        ]);
    }

    /**
     * Get blog post statistics
     * GET /api/v1/admin/blog-posts/stats
     */
    public function stats(): JsonResponse
    {
        $stats = [
            'total_posts' => BlogPost::count(),
            'published_posts' => BlogPost::where('status', 'published')->count(),
            'draft_posts' => BlogPost::where('status', 'draft')->count(),
            'featured_posts' => BlogPost::where('is_featured', true)->count(),
            'posts_with_category' => BlogPost::whereNotNull('category_id')->count(),
            'posts_without_category' => BlogPost::whereNull('category_id')->count(),
        ];

        return response()->json($stats);
    }

    // ========== PUBLIC ENDPOINTS ==========

    /**
     * Get published blog posts for public display
     * GET /api/v1/public/blog
     */
    public function publicIndex(Request $request): JsonResponse
    {
        $query = BlogPost::with(['author:id,name', 'blogCategory:id,name,slug,color'])
            ->published()
            ->latest('published_at');

        // Filter by category slug
        if ($request->filled('category')) {
            $query->whereHas('blogCategory', function ($q) use ($request) {
                $q->where('slug', $request->category);
            });
        }

        // Search
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        // Only featured posts
        if ($request->filled('featured') && filter_var($request->featured, FILTER_VALIDATE_BOOLEAN)) {
            $query->featured();
        }

        // Pagination
        $perPage = $request->get('per_page', 12);
        $posts = $query->paginate($perPage);

        return response()->json($posts);
    }

    /**
     * Get a single published blog post by slug
     * GET /api/v1/public/blog/{slug}
     */
    public function publicShow(string $slug): JsonResponse
    {
        $post = BlogPost::with(['author:id,name', 'blogCategory:id,name,slug,color'])
            ->where('slug', $slug)
            ->published()
            ->firstOrFail();

        // Get related posts (same category, exclude current post)
        $relatedPosts = BlogPost::with(['author:id,name', 'blogCategory:id,name,color'])
            ->where('id', '!=', $post->id)
            ->where('category_id', $post->category_id)
            ->published()
            ->latest('published_at')
            ->limit(3)
            ->get();

        return response()->json([
            'post' => $post,
            'related_posts' => $relatedPosts
        ]);
    }
}
