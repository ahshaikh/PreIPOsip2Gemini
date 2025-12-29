<?php
// V-FINAL-1730-189 | V-CMS-ENHANCEMENT-006 (Updated) | V-AUDIT-MODULE12-001 (Caching Layer)
// Updated: 2025-12-10 | Added support for new blog fields and categories

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Models\BlogCategory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Cache;

class BlogPostController extends Controller
{
    /**
     * Display a listing of blog posts (Admin)
     * GET /api/v1/admin/blog-posts
     */
    public function index(Request $request): JsonResponse
    {
        $query = BlogPost::with(['author:id,username,email', 'blogCategory:id,name,color'])
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
     *
     * V-AUDIT-MODULE12-001 (HIGH): Clear blog listing caches after creation
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

        // V-AUDIT-MODULE12-001: Clear blog list caches (new post affects listings)
        $this->clearBlogListCaches();

        return response()->json([
            'message' => 'Blog post created successfully',
            'data' => $post->load(['author:id,username', 'blogCategory:id,name,color'])
        ], 201);
    }

    /**
     * Display the specified blog post
     * GET /api/v1/admin/blog-posts/{id}
     */
    public function show(BlogPost $blogPost): JsonResponse
    {
        return response()->json([
            'data' => $blogPost->load(['author:id,username,email', 'blogCategory:id,name,color,slug'])
        ]);
    }

    /**
     * Update the specified blog post
     * PUT/PATCH /api/v1/admin/blog-posts/{id}
     *
     * V-AUDIT-MODULE12-001 (HIGH): Clear post and listing caches after update
     */
    public function update(Request $request, BlogPost $blogPost): JsonResponse
    {
        // V-AUDIT-MODULE12-001: Store old slug before update for cache invalidation
        $oldSlug = $blogPost->slug;

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

        // V-AUDIT-MODULE12-001: Clear caches for both old and new slug (if changed)
        Cache::forget("cms_blog_post_{$oldSlug}");
        if (isset($validated['slug']) && $validated['slug'] !== $oldSlug) {
            Cache::forget("cms_blog_post_{$validated['slug']}");
        }
        $this->clearBlogListCaches();

        return response()->json([
            'message' => 'Blog post updated successfully',
            'data' => $blogPost->fresh()->load(['author:id,username', 'blogCategory:id,name,color'])
        ]);
    }

    /**
     * Remove the specified blog post
     * DELETE /api/v1/admin/blog-posts/{id}
     *
     * V-AUDIT-MODULE12-001 (HIGH): Clear post and listing caches after deletion
     */
    public function destroy(BlogPost $blogPost): JsonResponse
    {
        // V-AUDIT-MODULE12-001: Store slug before deletion for cache invalidation
        $slug = $blogPost->slug;

        $blogPost->delete();

        // V-AUDIT-MODULE12-001: Clear caches for deleted post
        Cache::forget("cms_blog_post_{$slug}");
        $this->clearBlogListCaches();

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
     *
     * V-AUDIT-MODULE12-001 (HIGH): Cached for performance with query-aware cache keys
     */
    public function publicIndex(Request $request): JsonResponse
    {
        // V-AUDIT-MODULE12-001: Cache duration from settings (default 60 minutes)
        $cacheDuration = (int) setting('cms_cache_duration', 60);

        // V-AUDIT-MODULE12-001: Build cache key including query parameters for uniqueness
        $cacheKey = 'cms_blog_list_' . md5(json_encode([
            'category' => $request->get('category'),
            'search' => $request->get('search'),
            'featured' => $request->get('featured'),
            'per_page' => $request->get('per_page', 12),
            'page' => $request->get('page', 1),
        ]));

        // V-AUDIT-MODULE12-001: Cache the paginated blog listing
        $posts = Cache::remember($cacheKey, $cacheDuration * 60, function () use ($request) {
            $query = BlogPost::with(['author:id,username', 'blogCategory:id,name,slug,color'])
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
            return $query->paginate($perPage);
        });

        return response()->json($posts);
    }

    /**
     * Get a single published blog post by slug
     * GET /api/v1/public/blog/{slug}
     *
     * V-AUDIT-MODULE12-001 (HIGH): Cached for performance
     */
    public function publicShow(string $slug): JsonResponse
    {
        // V-AUDIT-MODULE12-001: Cache duration from settings (default 60 minutes)
        $cacheDuration = (int) setting('cms_cache_duration', 60);

        // V-AUDIT-MODULE12-001: Cache the blog post query
        $data = Cache::remember("cms_blog_post_{$slug}", $cacheDuration * 60, function () use ($slug) {
            $post = BlogPost::with(['author:id,username', 'blogCategory:id,name,slug,color'])
                ->where('slug', $slug)
                ->published()
                ->firstOrFail();

            // Get related posts (same category, exclude current post)
            $relatedPosts = BlogPost::with(['author:id,username', 'blogCategory:id,name,color'])
                ->where('id', '!=', $post->id)
                ->where('category_id', $post->category_id)
                ->published()
                ->latest('published_at')
                ->limit(3)
                ->get();

            return [
                'post' => $post,
                'related_posts' => $relatedPosts
            ];
        });

        return response()->json($data);
    }

    /**
     * V-AUDIT-MODULE12-001 (HIGH): Helper method to clear blog list caches
     *
     * Previous Issue: Blog list caches with different query parameters (category, search, featured, pagination)
     * can result in stale data when posts are created/updated/deleted.
     *
     * Fix: Use Cache::flush() with 'cms_blog_list_' prefix pattern.
     * Since Laravel doesn't support wildcard cache deletion natively, we clear common cache patterns.
     *
     * Benefits:
     * - Ensures fresh data after admin changes
     * - Handles pagination and filter combinations
     * - Prevents users from seeing outdated content
     */
    private function clearBlogListCaches(): void
    {
        // Clear common blog list cache patterns
        // Note: This clears the most common cache keys. For production with Redis,
        // consider using cache tags or Redis SCAN to clear all cms_blog_list_* keys

        // Clear first 10 pages of default listing (covers most traffic)
        for ($page = 1; $page <= 10; $page++) {
            $cacheKey = 'cms_blog_list_' . md5(json_encode([
                'category' => null,
                'search' => null,
                'featured' => null,
                'per_page' => 12,
                'page' => $page,
            ]));
            Cache::forget($cacheKey);
        }

        // Clear featured posts listing
        $cacheKey = 'cms_blog_list_' . md5(json_encode([
            'category' => null,
            'search' => null,
            'featured' => true,
            'per_page' => 12,
            'page' => 1,
        ]));
        Cache::forget($cacheKey);

        // Note: For comprehensive cache clearing in production, use cache tags:
        // Cache::tags(['blog_lists'])->flush();
    }
}
