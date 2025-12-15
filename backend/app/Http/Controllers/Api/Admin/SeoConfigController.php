<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\Product;
use App\Models\BlogPost;
use App\Services\SeoAnalyzerService;
use Illuminate\Http\Request;

class SeoConfigController extends Controller
{
    /**
     * Get global SEO configuration
     */
    public function getGlobalConfig()
    {
        $config = [
            'enabled' => setting('seo_enabled', true),
            'meta' => [
                'title_suffix' => setting('seo_meta_title_suffix', ' | PreIPO SIP'),
                'title_separator' => setting('seo_meta_title_separator', '|'),
                'default_title' => setting('seo_default_title', 'PreIPO SIP'),
                'default_description' => setting('seo_default_description', ''),
                'default_keywords' => setting('seo_default_keywords', ''),
                'default_author' => setting('seo_default_author', 'PreIPO SIP Team'),
            ],
            'features' => [
                'canonical_enabled' => setting('seo_canonical_enabled', true),
                'auto_meta_description' => setting('seo_auto_meta_description', true),
                'auto_meta_keywords' => setting('seo_auto_meta_keywords', true),
            ],
            'open_graph' => [
                'enabled' => setting('seo_og_enabled', true),
                'site_name' => setting('seo_og_site_name', 'PreIPO SIP'),
                'default_image' => setting('seo_og_default_image', ''),
                'default_type' => setting('seo_og_default_type', 'website'),
                'locale' => setting('seo_og_locale', 'en_US'),
            ],
            'twitter' => [
                'enabled' => setting('seo_twitter_enabled', true),
                'card_type' => setting('seo_twitter_card_type', 'summary_large_image'),
                'site' => setting('seo_twitter_site', ''),
                'creator' => setting('seo_twitter_creator', ''),
            ],
            'schema' => [
                'enabled' => setting('seo_schema_enabled', true),
                'type' => setting('seo_schema_type', 'FinancialService'),
                'organization_name' => setting('seo_schema_organization_name', 'PreIPO SIP'),
                'logo' => setting('seo_schema_logo', ''),
                'contact_type' => setting('seo_schema_contact_type', 'customer support'),
            ],
            'robots' => [
                'meta_default' => setting('seo_robots_meta_default', 'index, follow'),
                'noindex_users' => setting('seo_robots_noindex_users', true),
                'noindex_admin' => setting('seo_robots_noindex_admin', true),
            ],
            'analysis' => [
                'enabled' => setting('seo_analysis_enabled', true),
                'min_score' => setting('seo_analysis_min_score', 70),
                'auto_suggestions' => setting('seo_analysis_auto_suggestions', true),
                'check_images' => setting('seo_analysis_check_images', true),
                'check_links' => setting('seo_analysis_check_links', true),
                'check_readability' => setting('seo_analysis_check_readability', true),
            ],
        ];

        return response()->json($config);
    }

    /**
     * Update global SEO configuration
     */
    public function updateGlobalConfig(Request $request)
    {
        $validated = $request->validate([
            'enabled' => 'boolean',
            'meta_title_suffix' => 'nullable|string|max:100',
            'meta_title_separator' => 'nullable|string|max:5',
            'default_title' => 'nullable|string|max:255',
            'default_description' => 'nullable|string|max:500',
            'default_keywords' => 'nullable|string|max:500',
            'default_author' => 'nullable|string|max:100',
            'canonical_enabled' => 'boolean',
            'auto_meta_description' => 'boolean',
            'auto_meta_keywords' => 'boolean',
            'og_enabled' => 'boolean',
            'og_site_name' => 'nullable|string|max:100',
            'og_default_image' => 'nullable|url',
            'og_default_type' => 'nullable|string',
            'og_locale' => 'nullable|string|max:10',
            'twitter_enabled' => 'boolean',
            'twitter_card_type' => 'nullable|in:summary,summary_large_image,app,player',
            'twitter_site' => 'nullable|string|max:50',
            'twitter_creator' => 'nullable|string|max:50',
            'schema_enabled' => 'boolean',
            'schema_type' => 'nullable|string',
            'schema_organization_name' => 'nullable|string|max:100',
            'schema_logo' => 'nullable|url',
            'schema_contact_type' => 'nullable|string|max:100',
            'robots_meta_default' => 'nullable|string',
            'robots_noindex_users' => 'boolean',
            'robots_noindex_admin' => 'boolean',
            'analysis_enabled' => 'boolean',
            'analysis_min_score' => 'nullable|integer|min:0|max:100',
            'analysis_auto_suggestions' => 'boolean',
            'analysis_check_images' => 'boolean',
            'analysis_check_links' => 'boolean',
            'analysis_check_readability' => 'boolean',
        ]);

        // Map request keys to setting keys
        $settingsMap = [
            'enabled' => 'seo_enabled',
            'meta_title_suffix' => 'seo_meta_title_suffix',
            'meta_title_separator' => 'seo_meta_title_separator',
            'default_title' => 'seo_default_title',
            'default_description' => 'seo_default_description',
            'default_keywords' => 'seo_default_keywords',
            'default_author' => 'seo_default_author',
            'canonical_enabled' => 'seo_canonical_enabled',
            'auto_meta_description' => 'seo_auto_meta_description',
            'auto_meta_keywords' => 'seo_auto_meta_keywords',
            'og_enabled' => 'seo_og_enabled',
            'og_site_name' => 'seo_og_site_name',
            'og_default_image' => 'seo_og_default_image',
            'og_default_type' => 'seo_og_default_type',
            'og_locale' => 'seo_og_locale',
            'twitter_enabled' => 'seo_twitter_enabled',
            'twitter_card_type' => 'seo_twitter_card_type',
            'twitter_site' => 'seo_twitter_site',
            'twitter_creator' => 'seo_twitter_creator',
            'schema_enabled' => 'seo_schema_enabled',
            'schema_type' => 'seo_schema_type',
            'schema_organization_name' => 'seo_schema_organization_name',
            'schema_logo' => 'seo_schema_logo',
            'schema_contact_type' => 'seo_schema_contact_type',
            'robots_meta_default' => 'seo_robots_meta_default',
            'robots_noindex_users' => 'seo_robots_noindex_users',
            'robots_noindex_admin' => 'seo_robots_noindex_admin',
            'analysis_enabled' => 'seo_analysis_enabled',
            'analysis_min_score' => 'seo_analysis_min_score',
            'analysis_auto_suggestions' => 'seo_analysis_auto_suggestions',
            'analysis_check_images' => 'seo_analysis_check_images',
            'analysis_check_links' => 'seo_analysis_check_links',
            'analysis_check_readability' => 'seo_analysis_check_readability',
        ];

        foreach ($validated as $key => $value) {
            if (isset($settingsMap[$key])) {
                setting([$settingsMap[$key] => $value]);
            }
        }

        return response()->json([
            'message' => 'Global SEO configuration updated successfully',
        ]);
    }

    /**
     * Get per-page SEO settings for a specific page
     */
    public function getPageSeo(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:page,product,blog',
            'id' => 'required|integer',
        ]);

        $model = $this->getModel($validated['type'], $validated['id']);

        if (!$model) {
            return response()->json([
                'message' => 'Resource not found',
            ], 404);
        }

        $seoMeta = $model->seo_meta ?? [];

        return response()->json([
            'type' => $validated['type'],
            'id' => $model->id,
            'title' => $model->title ?? $model->name,
            'slug' => $model->slug,
            'seo_meta' => [
                'title' => $seoMeta['title'] ?? '',
                'description' => $seoMeta['description'] ?? '',
                'keywords' => $seoMeta['keywords'] ?? '',
                'robots' => $seoMeta['robots'] ?? 'index, follow',
                'canonical_url' => $seoMeta['canonical_url'] ?? '',
                'og_title' => $seoMeta['og_title'] ?? '',
                'og_description' => $seoMeta['og_description'] ?? '',
                'og_image' => $seoMeta['og_image'] ?? '',
                'og_type' => $seoMeta['og_type'] ?? 'article',
                'twitter_title' => $seoMeta['twitter_title'] ?? '',
                'twitter_description' => $seoMeta['twitter_description'] ?? '',
                'twitter_image' => $seoMeta['twitter_image'] ?? '',
            ],
        ]);
    }

    /**
     * Update per-page SEO settings
     */
    public function updatePageSeo(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:page,product,blog',
            'id' => 'required|integer',
            'seo_meta' => 'required|array',
            'seo_meta.title' => 'nullable|string|max:255',
            'seo_meta.description' => 'nullable|string|max:500',
            'seo_meta.keywords' => 'nullable|string|max:500',
            'seo_meta.robots' => 'nullable|string',
            'seo_meta.canonical_url' => 'nullable|url',
            'seo_meta.og_title' => 'nullable|string|max:255',
            'seo_meta.og_description' => 'nullable|string|max:500',
            'seo_meta.og_image' => 'nullable|url',
            'seo_meta.og_type' => 'nullable|string',
            'seo_meta.twitter_title' => 'nullable|string|max:255',
            'seo_meta.twitter_description' => 'nullable|string|max:500',
            'seo_meta.twitter_image' => 'nullable|url',
        ]);

        $model = $this->getModel($validated['type'], $validated['id']);

        if (!$model) {
            return response()->json([
                'message' => 'Resource not found',
            ], 404);
        }

        $model->update([
            'seo_meta' => $validated['seo_meta'],
        ]);

        return response()->json([
            'message' => 'SEO metadata updated successfully',
            'data' => $model,
        ]);
    }

    /**
     * Analyze SEO for a specific page
     */
    public function analyzePage(Request $request, SeoAnalyzerService $analyzer)
    {
        $validated = $request->validate([
            'type' => 'required|in:page,product,blog',
            'id' => 'required|integer',
        ]);

        $model = $this->getModel($validated['type'], $validated['id']);

        if (!$model) {
            return response()->json([
                'message' => 'Resource not found',
            ], 404);
        }

        // Only Page model is currently supported by SeoAnalyzerService
        if ($validated['type'] !== 'page') {
            return response()->json([
                'message' => 'SEO analysis is currently only available for pages',
            ], 501);
        }

        $report = $analyzer->analyze($model);

        return response()->json($report);
    }

    /**
     * Bulk analyze SEO for all pages
     */
    public function bulkAnalyze(Request $request, SeoAnalyzerService $analyzer)
    {
        $validated = $request->validate([
            'type' => 'required|in:page,product,blog',
        ]);

        $models = $this->getModels($validated['type']);

        $results = [];

        foreach ($models as $model) {
            if ($validated['type'] === 'page') {
                $report = $analyzer->analyze($model);
                $results[] = [
                    'id' => $model->id,
                    'title' => $model->title,
                    'slug' => $model->slug,
                    'score' => $report['score'],
                    'recommendations_count' => count($report['recommendations']),
                ];
            }
        }

        // Sort by score (lowest first - needs most attention)
        usort($results, function ($a, $b) {
            return $a['score'] - $b['score'];
        });

        return response()->json([
            'total' => count($results),
            'average_score' => count($results) > 0 ? array_sum(array_column($results, 'score')) / count($results) : 0,
            'results' => $results,
        ]);
    }

    /**
     * Get SEO preview for a page
     */
    public function preview(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:page,product,blog',
            'id' => 'required|integer',
        ]);

        $model = $this->getModel($validated['type'], $validated['id']);

        if (!$model) {
            return response()->json([
                'message' => 'Resource not found',
            ], 404);
        }

        $seoMeta = $model->seo_meta ?? [];
        $title = $seoMeta['title'] ?? ($model->title ?? $model->name);
        $description = $seoMeta['description'] ?? '';

        // Generate previews
        $preview = [
            'google' => [
                'title' => $title . setting('seo_meta_title_suffix', ''),
                'url' => url($model->slug),
                'description' => $description,
            ],
            'facebook' => [
                'title' => $seoMeta['og_title'] ?? $title,
                'description' => $seoMeta['og_description'] ?? $description,
                'image' => $seoMeta['og_image'] ?? setting('seo_og_default_image'),
                'site_name' => setting('seo_og_site_name', 'PreIPO SIP'),
            ],
            'twitter' => [
                'title' => $seoMeta['twitter_title'] ?? $title,
                'description' => $seoMeta['twitter_description'] ?? $description,
                'image' => $seoMeta['twitter_image'] ?? setting('seo_og_default_image'),
                'card_type' => setting('seo_twitter_card_type', 'summary_large_image'),
            ],
        ];

        return response()->json($preview);
    }

    /**
     * Helper: Get model by type and ID
     */
    private function getModel($type, $id)
    {
        switch ($type) {
            case 'page':
                return Page::find($id);
            case 'product':
                return Product::find($id);
            case 'blog':
                return BlogPost::find($id);
            default:
                return null;
        }
    }

    /**
     * Helper: Get all models by type
     */
    private function getModels($type)
    {
        switch ($type) {
            case 'page':
                return Page::where('status', 'published')->get();
            case 'product':
                return Product::where('status', 'active')->get();
            case 'blog':
                return BlogPost::where('status', 'published')->get();
            default:
                return collect([]);
        }
    }
}
