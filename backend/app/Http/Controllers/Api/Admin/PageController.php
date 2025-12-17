<?php
// V-PHASE2-1730-059 (Created) | V-FINAL-1730-369 (Block Editor) | V-FINAL-1730-528 (SEO Analyzer) | V-FINAL-1730-559 (Versioning) | V-FIX-MODULE-17 (Gemini) | V-AUDIT-MODULE12-001 (Caching)

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Services\SeoAnalyzerService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Models\UserLegalAcceptance; // Ensure this is imported

class PageController extends Controller
{
    public function index()
    {
        // Now includes version history
        return Page::with('versions')->latest()->get();
    }

    /**
     * V-AUDIT-MODULE12-001 (HIGH): Clear page cache after creation
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'required|string|unique:pages,slug',
        ]);

        $page = DB::transaction(function() use ($validated, $request) {
            $page = Page::create($validated + [
                'status' => 'draft',
                'content' => [],
                'current_version' => 1
            ]);

            // Create the first version
            $page->versions()->create([
                'author_id' => $request->user()->id,
                'version' => 1,
                'title' => $page->title,
                'content' => $page->content,
            ]);

            return $page;
        });

        // V-AUDIT-MODULE12-001: Clear cache for the new page
        Cache::forget("cms_page_{$page->slug}");

        return response()->json($page, 201);
    }

    public function show(Page $page)
    {
        // Load versions for the editor
        return $page->load('versions');
    }

    /**
     * FSD-LEGAL-001: Update a page by publishing a new version.
     * V-AUDIT-MODULE12-001 (HIGH): Clear page cache after update
     */
    public function update(Request $request, Page $page)
    {
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'content' => 'nullable|array',
            'status' => 'sometimes|required|in:draft,published',
            'seo_meta' => 'nullable|array',
            'require_user_acceptance' => 'sometimes|boolean',
            'change_summary' => 'nullable|string|max:255', // e.g., "Updated clause 5.1"
        ]);

        // V-AUDIT-MODULE12-001: Store slug before update for cache invalidation
        $slug = $page->slug;

        DB::transaction(function() use ($page, $validated, $request) {

            $newVersionNumber = $page->current_version + 1;

            // 1. Create the new version history
            $page->versions()->create([
                'author_id' => $request->user()->id,
                'version' => $newVersionNumber,
                'title' => $validated['title'],
                'content' => $validated['content'],
                'change_summary' => $validated['change_summary']
            ]);

            // 2. Update the "live" page with the new content
            $page->update([
                'title' => $validated['title'],
                'content' => $validated['content'],
                'status' => $validated['status'],
                'seo_meta' => $validated['seo_meta'] ?? $page->seo_meta,
                'current_version' => $newVersionNumber,
                'require_user_acceptance' => $validated['require_user_acceptance'] ?? $page->require_user_acceptance,
            ]);

            // FIX: Module 17 - Fix Destructive Page Logic (Medium)
            // REMOVED: UserLegalAcceptance::where('page_id', $page->id)->delete();
            // Reason: Deleting acceptances destroys the audit trail.
            // The system should check if (user_last_accepted_version < current_page_version) to prompt re-acceptance.
            if ($page->require_user_acceptance) {
                // Logic is now purely version comparison on read, no destructive write needed here.
            }
        });

        // V-AUDIT-MODULE12-001: Clear cache for the updated page
        Cache::forget("cms_page_{$slug}");

        return response()->json($page->load('versions'));
    }

    /**
     * V-AUDIT-MODULE12-001 (HIGH): Clear page cache after deletion
     */
    public function destroy(Page $page)
    {
        // V-AUDIT-MODULE12-001: Store slug before deletion for cache invalidation
        $slug = $page->slug;

        $page->delete();

        // V-AUDIT-MODULE12-001: Clear cache for the deleted page
        Cache::forget("cms_page_{$slug}");

        return response()->noContent();
    }

    public function analyze(Page $page, SeoAnalyzerService $analyzer)
    {
        $report = $analyzer->analyze($page);
        return response()->json($report);
    }
}