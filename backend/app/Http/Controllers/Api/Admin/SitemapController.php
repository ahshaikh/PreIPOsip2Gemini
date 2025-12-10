<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\BlogPost;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Artisan;

class SitemapController extends Controller
{
    /**
     * Get sitemap configuration
     */
    public function getConfig()
    {
        $config = [
            'enabled' => setting('seo_sitemap_enabled', true),
            'auto_generate' => setting('seo_sitemap_auto_generate', true),
            'frequency' => setting('seo_sitemap_frequency', 'daily'),
            'priorities' => [
                'home' => setting('seo_sitemap_priority_home', '1.0'),
                'pages' => setting('seo_sitemap_priority_pages', '0.8'),
                'products' => setting('seo_sitemap_priority_products', '0.9'),
                'blog' => setting('seo_sitemap_priority_blog', '0.7'),
            ],
            'include_images' => setting('seo_sitemap_include_images', true),
            'ping_google' => setting('seo_sitemap_ping_google', true),
            'ping_bing' => setting('seo_sitemap_ping_bing', true),
            'sitemap_exists' => File::exists(public_path('sitemap.xml')),
            'last_generated' => $this->getLastGeneratedTime(),
        ];

        return response()->json($config);
    }

    /**
     * Update sitemap configuration
     */
    public function updateConfig(Request $request)
    {
        $validated = $request->validate([
            'enabled' => 'boolean',
            'auto_generate' => 'boolean',
            'frequency' => 'in:hourly,daily,weekly,monthly',
            'priority_home' => 'numeric|min:0|max:1',
            'priority_pages' => 'numeric|min:0|max:1',
            'priority_products' => 'numeric|min:0|max:1',
            'priority_blog' => 'numeric|min:0|max:1',
            'include_images' => 'boolean',
            'ping_google' => 'boolean',
            'ping_bing' => 'boolean',
        ]);

        $settingsMap = [
            'enabled' => 'seo_sitemap_enabled',
            'auto_generate' => 'seo_sitemap_auto_generate',
            'frequency' => 'seo_sitemap_frequency',
            'priority_home' => 'seo_sitemap_priority_home',
            'priority_pages' => 'seo_sitemap_priority_pages',
            'priority_products' => 'seo_sitemap_priority_products',
            'priority_blog' => 'seo_sitemap_priority_blog',
            'include_images' => 'seo_sitemap_include_images',
            'ping_google' => 'seo_sitemap_ping_google',
            'ping_bing' => 'seo_sitemap_ping_bing',
        ];

        foreach ($validated as $key => $value) {
            if (isset($settingsMap[$key])) {
                setting([$settingsMap[$key] => $value]);
            }
        }

        return response()->json([
            'message' => 'Sitemap configuration updated successfully',
        ]);
    }

    /**
     * Generate sitemap manually
     */
    public function generate()
    {
        try {
            Artisan::call('sitemap:generate');

            $output = Artisan::output();

            return response()->json([
                'message' => 'Sitemap generated successfully',
                'path' => public_path('sitemap.xml'),
                'url' => url('sitemap.xml'),
                'output' => $output,
                'size' => File::exists(public_path('sitemap.xml'))
                    ? File::size(public_path('sitemap.xml'))
                    : 0,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to generate sitemap',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get sitemap content
     */
    public function view()
    {
        if (!File::exists(public_path('sitemap.xml'))) {
            return response()->json([
                'message' => 'Sitemap not found. Please generate it first.',
            ], 404);
        }

        $content = File::get(public_path('sitemap.xml'));

        return response($content, 200, [
            'Content-Type' => 'application/xml',
        ]);
    }

    /**
     * Get sitemap statistics
     */
    public function statistics()
    {
        $stats = [
            'exists' => File::exists(public_path('sitemap.xml')),
            'last_generated' => $this->getLastGeneratedTime(),
            'file_size' => File::exists(public_path('sitemap.xml'))
                ? File::size(public_path('sitemap.xml'))
                : 0,
            'url_counts' => [
                'static_pages' => 9, // Hardcoded from GenerateSitemap command
                'cms_pages' => Page::where('status', 'published')->count(),
                'products' => Product::where('status', 'active')->count(),
                'blog_posts' => BlogPost::where('status', 'published')->count(),
            ],
        ];

        $stats['url_counts']['total'] = array_sum($stats['url_counts']);

        return response()->json($stats);
    }

    /**
     * Submit sitemap to search engines
     */
    public function submit(Request $request)
    {
        $validated = $request->validate([
            'engines' => 'required|array',
            'engines.*' => 'in:google,bing',
        ]);

        if (!File::exists(public_path('sitemap.xml'))) {
            return response()->json([
                'message' => 'Sitemap not found. Please generate it first.',
            ], 404);
        }

        $sitemapUrl = url('sitemap.xml');
        $results = [];

        foreach ($validated['engines'] as $engine) {
            try {
                $result = $this->submitToEngine($engine, $sitemapUrl);
                $results[$engine] = $result;
            } catch (\Exception $e) {
                $results[$engine] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'message' => 'Sitemap submission completed',
            'sitemap_url' => $sitemapUrl,
            'results' => $results,
        ]);
    }

    /**
     * Test sitemap URL accessibility
     */
    public function test()
    {
        $sitemapUrl = url('sitemap.xml');

        try {
            $response = Http::timeout(10)->get($sitemapUrl);

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Sitemap is accessible',
                    'url' => $sitemapUrl,
                    'status_code' => $response->status(),
                    'content_type' => $response->header('Content-Type'),
                    'size' => strlen($response->body()),
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Sitemap returned non-200 status',
                'url' => $sitemapUrl,
                'status_code' => $response->status(),
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to access sitemap',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download sitemap
     */
    public function download()
    {
        if (!File::exists(public_path('sitemap.xml'))) {
            return response()->json([
                'message' => 'Sitemap not found. Please generate it first.',
            ], 404);
        }

        return response()->download(
            public_path('sitemap.xml'),
            'sitemap-' . date('Y-m-d') . '.xml',
            ['Content-Type' => 'application/xml']
        );
    }

    /**
     * Delete sitemap
     */
    public function delete()
    {
        if (File::exists(public_path('sitemap.xml'))) {
            File::delete(public_path('sitemap.xml'));

            return response()->json([
                'message' => 'Sitemap deleted successfully',
            ]);
        }

        return response()->json([
            'message' => 'Sitemap not found',
        ], 404);
    }

    /**
     * Helper: Get last generated time
     */
    private function getLastGeneratedTime()
    {
        if (File::exists(public_path('sitemap.xml'))) {
            return date('Y-m-d H:i:s', File::lastModified(public_path('sitemap.xml')));
        }

        return null;
    }

    /**
     * Helper: Submit to search engine
     */
    private function submitToEngine($engine, $sitemapUrl)
    {
        if ($engine === 'google') {
            $pingUrl = "https://www.google.com/ping?sitemap=" . urlencode($sitemapUrl);
        } elseif ($engine === 'bing') {
            $pingUrl = "https://www.bing.com/ping?sitemap=" . urlencode($sitemapUrl);
        } else {
            throw new \Exception("Unknown search engine: {$engine}");
        }

        $response = Http::timeout(15)->get($pingUrl);

        if ($response->successful()) {
            return [
                'success' => true,
                'message' => "Successfully pinged {$engine}",
                'status_code' => $response->status(),
            ];
        }

        return [
            'success' => false,
            'message' => "Failed to ping {$engine}",
            'status_code' => $response->status(),
            'error' => $response->body(),
        ];
    }

    /**
     * Validate sitemap XML structure
     */
    public function validate()
    {
        if (!File::exists(public_path('sitemap.xml'))) {
            return response()->json([
                'valid' => false,
                'message' => 'Sitemap not found',
            ], 404);
        }

        $content = File::get(public_path('sitemap.xml'));

        // Basic XML validation
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($content);

        if ($xml === false) {
            $errors = libxml_get_errors();
            libxml_clear_errors();

            return response()->json([
                'valid' => false,
                'message' => 'Invalid XML structure',
                'errors' => array_map(function ($error) {
                    return $error->message;
                }, $errors),
            ]);
        }

        // Check if it's a valid sitemap structure
        $namespace = $xml->getNamespaces(true);
        if (!isset($namespace['']) || $namespace[''] !== 'http://www.sitemaps.org/schemas/sitemap/0.9') {
            return response()->json([
                'valid' => false,
                'message' => 'Invalid sitemap namespace',
            ]);
        }

        // Count URLs
        $urlCount = count($xml->url);

        return response()->json([
            'valid' => true,
            'message' => 'Sitemap is valid',
            'url_count' => $urlCount,
            'namespace' => $namespace[''],
        ]);
    }
}
