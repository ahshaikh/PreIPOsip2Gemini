<?php
// V-PHASE2-1730-051 | V-AUDIT-MODULE12-001 (Caching Layer)

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * V-AUDIT-MODULE12-001 (HIGH): Implement caching for public CMS endpoints
 *
 * Previous Issue:
 * - Public pages queried database on every request
 * - CMS content is "Read-Heavy, Write-Rare"
 * - Under high traffic (marketing push), DB queries were wasteful and slow
 * - No caching for static content like Terms of Service or Homepage
 *
 * Fix:
 * - Use Cache::remember() to cache page content
 * - Cache for 1 hour (configurable via settings)
 * - Cache key format: cms_page_{slug}
 * - Admin panel can clear cache on page update
 *
 * Benefits:
 * - Instant response for cached pages
 * - Reduced database load
 * - Better performance under high traffic
 */
class PageController extends Controller
{
    /**
     * Display the specified CMS page.
     *
     * V-AUDIT-MODULE12-001: Cached for performance
     * V-FIX-RESILIENCE: Added error handling to prevent 500 errors
     */
    public function show($slug)
    {
        try {
            // Check if pages table exists before querying
            if (!DB::getSchemaBuilder()->hasTable('pages')) {
                Log::warning('Pages table missing', ['slug' => $slug]);
                return response()->json([
                    'content' => null,
                    'message' => 'CMS not configured yet'
                ], 200);
            }

            // V-AUDIT-MODULE12-001: Cache duration from settings (default 60 minutes)
            $cacheDuration = (int) setting('cms_cache_duration', 60);

            // V-AUDIT-MODULE12-001: Cache the page query
            $page = Cache::remember("cms_page_{$slug}", $cacheDuration * 60, function () use ($slug) {
                return Page::where('slug', $slug)
                    ->where('status', 'published')
                    ->first(); // Changed from firstOrFail to first
            });

            // Return null content if page not found (instead of 404)
            // This allows frontend to use default content gracefully
            if (!$page) {
                return response()->json([
                    'content' => null,
                    'message' => 'Page not found, using defaults'
                ], 200);
            }

            return response()->json($page);

        } catch (\Throwable $e) {
            // Return null instead of 500 error
            Log::error("Page fetch error: " . $e->getMessage(), [
                'slug' => $slug,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'content' => null,
                'message' => 'Page temporarily unavailable'
            ], 200);
        }
    }
}