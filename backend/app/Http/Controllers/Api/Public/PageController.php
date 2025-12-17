<?php
// V-PHASE2-1730-051 | V-AUDIT-MODULE12-001 (Caching Layer)

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

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
     */
    public function show($slug)
    {
        // V-AUDIT-MODULE12-001: Cache duration from settings (default 60 minutes)
        $cacheDuration = (int) setting('cms_cache_duration', 60);

        // V-AUDIT-MODULE12-001: Cache the page query
        $page = Cache::remember("cms_page_{$slug}", $cacheDuration * 60, function () use ($slug) {
            return Page::where('slug', $slug)
                ->where('status', 'published')
                ->firstOrFail();
        });

        return response()->json($page);
    }
}