<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

class RobotsTxtController extends Controller
{
    /**
     * Get robots.txt content
     */
    public function get()
    {
        // Check if custom robots.txt exists in public folder
        if (File::exists(public_path('robots.txt'))) {
            $content = File::get(public_path('robots.txt'));
            $source = 'file';
        } else {
            // Fall back to setting
            $content = setting('seo_robots_txt', $this->getDefaultContent());
            $source = 'setting';
        }

        return response()->json([
            'content' => $content,
            'source' => $source,
            'file_exists' => File::exists(public_path('robots.txt')),
            'url' => url('robots.txt'),
        ]);
    }

    /**
     * Update robots.txt content
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'content' => 'required|string|max:10000',
            'save_to_file' => 'boolean',
        ]);

        $content = $validated['content'];
        $saveToFile = $request->boolean('save_to_file', true);

        // Validate robots.txt syntax
        $validation = $this->validateContent($content);
        if (!$validation['valid']) {
            return response()->json([
                'message' => 'Invalid robots.txt syntax',
                'errors' => $validation['errors'],
            ], 422);
        }

        // Save to database setting
        setting(['seo_robots_txt' => $content]);

        // Optionally save to file
        if ($saveToFile) {
            try {
                File::put(public_path('robots.txt'), $content);
                $location = 'file';
            } catch (\Exception $e) {
                return response()->json([
                    'message' => 'Failed to write robots.txt file',
                    'error' => $e->getMessage(),
                ], 500);
            }
        } else {
            $location = 'setting';
        }

        return response()->json([
            'message' => 'robots.txt updated successfully',
            'location' => $location,
            'url' => url('robots.txt'),
        ]);
    }

    /**
     * Get robots.txt template presets
     */
    public function templates()
    {
        $templates = [
            [
                'name' => 'Allow All',
                'description' => 'Allow all robots to crawl all pages',
                'content' => "User-agent: *\nAllow: /\n\nSitemap: " . url('sitemap.xml'),
            ],
            [
                'name' => 'Block All',
                'description' => 'Block all robots from crawling any pages',
                'content' => "User-agent: *\nDisallow: /",
            ],
            [
                'name' => 'Default (Recommended)',
                'description' => 'Allow crawling but block admin and user areas',
                'content' => $this->getDefaultContent(),
            ],
            [
                'name' => 'Strict',
                'description' => 'Block admin, API, user areas, and specific file types',
                'content' => "User-agent: *\nDisallow: /admin/\nDisallow: /api/\nDisallow: /user/\nDisallow: /*.json$\nDisallow: /*.xml$\nDisallow: /tmp/\nAllow: /\n\nSitemap: " . url('sitemap.xml'),
            ],
            [
                'name' => 'Google Only',
                'description' => 'Allow only Google to crawl, block all others',
                'content' => "User-agent: Googlebot\nAllow: /\n\nUser-agent: *\nDisallow: /\n\nSitemap: " . url('sitemap.xml'),
            ],
            [
                'name' => 'Block Bad Bots',
                'description' => 'Block known bad bots while allowing good bots',
                'content' => "# Block bad bots\nUser-agent: AhrefsBot\nDisallow: /\n\nUser-agent: SemrushBot\nDisallow: /\n\nUser-agent: MJ12bot\nDisallow: /\n\n# Allow good bots\nUser-agent: Googlebot\nAllow: /\n\nUser-agent: Bingbot\nAllow: /\n\nUser-agent: *\nDisallow: /admin/\nDisallow: /api/\nDisallow: /user/\nAllow: /\n\nSitemap: " . url('sitemap.xml'),
            ],
        ];

        return response()->json(['templates' => $templates]);
    }

    /**
     * Test robots.txt accessibility
     */
    public function test()
    {
        $robotsUrl = url('robots.txt');

        try {
            $response = Http::timeout(10)->get($robotsUrl);

            if ($response->successful()) {
                $content = $response->body();

                return response()->json([
                    'success' => true,
                    'message' => 'robots.txt is accessible',
                    'url' => $robotsUrl,
                    'status_code' => $response->status(),
                    'content_type' => $response->header('Content-Type'),
                    'size' => strlen($content),
                    'preview' => substr($content, 0, 500),
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'robots.txt returned non-200 status',
                'url' => $robotsUrl,
                'status_code' => $response->status(),
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to access robots.txt',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Validate robots.txt syntax
     */
    public function validate(Request $request)
    {
        $validated = $request->validate([
            'content' => 'required|string',
        ]);

        $result = $this->validateContent($validated['content']);

        return response()->json($result);
    }

    /**
     * Delete robots.txt file (will fall back to setting)
     */
    public function delete()
    {
        if (File::exists(public_path('robots.txt'))) {
            File::delete(public_path('robots.txt'));

            return response()->json([
                'message' => 'robots.txt file deleted. Will fall back to database setting.',
            ]);
        }

        return response()->json([
            'message' => 'No robots.txt file to delete',
        ], 404);
    }

    /**
     * Test URL against robots.txt rules
     */
    public function testUrl(Request $request)
    {
        $validated = $request->validate([
            'url' => 'required|string',
            'user_agent' => 'nullable|string',
        ]);

        $url = $validated['url'];
        $userAgent = $validated['user_agent'] ?? 'Googlebot';

        // Get robots.txt content
        if (File::exists(public_path('robots.txt'))) {
            $content = File::get(public_path('robots.txt'));
        } else {
            $content = setting('seo_robots_txt', $this->getDefaultContent());
        }

        // Parse robots.txt and check if URL is allowed
        $allowed = $this->isUrlAllowed($content, $url, $userAgent);

        return response()->json([
            'url' => $url,
            'user_agent' => $userAgent,
            'allowed' => $allowed,
            'message' => $allowed
                ? "URL is allowed for {$userAgent}"
                : "URL is disallowed for {$userAgent}",
        ]);
    }

    /**
     * Helper: Get default robots.txt content
     */
    private function getDefaultContent()
    {
        return "User-agent: *\nDisallow: /admin/\nDisallow: /api/\nDisallow: /user/\nAllow: /\n\nSitemap: " . url('sitemap.xml');
    }

    /**
     * Helper: Validate robots.txt content
     */
    private function validateContent($content)
    {
        $errors = [];
        $warnings = [];
        $lines = explode("\n", $content);

        $hasUserAgent = false;
        $hasSitemap = false;

        foreach ($lines as $lineNumber => $line) {
            $line = trim($line);

            // Skip empty lines and comments
            if (empty($line) || str_starts_with($line, '#')) {
                continue;
            }

            // Check for User-agent
            if (stripos($line, 'User-agent:') === 0) {
                $hasUserAgent = true;
            }

            // Check for Sitemap
            if (stripos($line, 'Sitemap:') === 0) {
                $hasSitemap = true;

                // Validate sitemap URL
                $sitemapUrl = trim(substr($line, 8));
                if (!filter_var($sitemapUrl, FILTER_VALIDATE_URL)) {
                    $errors[] = "Line " . ($lineNumber + 1) . ": Invalid sitemap URL";
                }
            }

            // Check for valid directives
            $validDirectives = ['User-agent', 'Disallow', 'Allow', 'Sitemap', 'Crawl-delay'];
            $foundValid = false;

            foreach ($validDirectives as $directive) {
                if (stripos($line, $directive . ':') === 0) {
                    $foundValid = true;
                    break;
                }
            }

            if (!$foundValid && !str_starts_with($line, '#')) {
                $warnings[] = "Line " . ($lineNumber + 1) . ": Unknown directive or invalid syntax";
            }
        }

        if (!$hasUserAgent) {
            $errors[] = "Missing User-agent directive";
        }

        if (!$hasSitemap) {
            $warnings[] = "No Sitemap directive found (recommended to include)";
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * Helper: Check if URL is allowed by robots.txt
     */
    private function isUrlAllowed($robotsContent, $url, $userAgent)
    {
        $lines = explode("\n", $robotsContent);
        $currentUserAgent = null;
        $rules = [];

        // Parse robots.txt
        foreach ($lines as $line) {
            $line = trim($line);

            if (empty($line) || str_starts_with($line, '#')) {
                continue;
            }

            if (stripos($line, 'User-agent:') === 0) {
                $currentUserAgent = trim(substr($line, 11));
            } elseif ($currentUserAgent && (stripos($line, 'Disallow:') === 0 || stripos($line, 'Allow:') === 0)) {
                $type = stripos($line, 'Disallow:') === 0 ? 'disallow' : 'allow';
                $path = trim(substr($line, strpos($line, ':') + 1));

                if (!isset($rules[$currentUserAgent])) {
                    $rules[$currentUserAgent] = [];
                }

                $rules[$currentUserAgent][] = [
                    'type' => $type,
                    'path' => $path,
                ];
            }
        }

        // Check rules for specific user agent or wildcard
        $applicableRules = $rules[$userAgent] ?? $rules['*'] ?? [];

        // Default is allow
        $allowed = true;

        // Parse URL path
        $urlPath = parse_url($url, PHP_URL_PATH) ?? '/';

        // Apply rules (more specific rules take precedence)
        foreach ($applicableRules as $rule) {
            $pattern = $rule['path'];

            // Convert robots.txt pattern to regex
            $pattern = str_replace('*', '.*', $pattern);
            $pattern = str_replace('?', '.', $pattern);
            $pattern = '/^' . str_replace('/', '\/', $pattern) . '/';

            if (preg_match($pattern, $urlPath)) {
                $allowed = ($rule['type'] === 'allow');
            }
        }

        return $allowed;
    }

    /**
     * Get robots.txt analysis
     */
    public function analyze()
    {
        if (File::exists(public_path('robots.txt'))) {
            $content = File::get(public_path('robots.txt'));
        } else {
            $content = setting('seo_robots_txt', $this->getDefaultContent());
        }

        $lines = explode("\n", $content);
        $analysis = [
            'total_lines' => count($lines),
            'user_agents' => [],
            'disallowed_paths' => [],
            'allowed_paths' => [],
            'sitemaps' => [],
            'comments' => 0,
            'empty_lines' => 0,
        ];

        $currentUserAgent = null;

        foreach ($lines as $line) {
            $line = trim($line);

            if (empty($line)) {
                $analysis['empty_lines']++;
                continue;
            }

            if (str_starts_with($line, '#')) {
                $analysis['comments']++;
                continue;
            }

            if (stripos($line, 'User-agent:') === 0) {
                $currentUserAgent = trim(substr($line, 11));
                if (!in_array($currentUserAgent, $analysis['user_agents'])) {
                    $analysis['user_agents'][] = $currentUserAgent;
                }
            } elseif (stripos($line, 'Disallow:') === 0) {
                $path = trim(substr($line, 9));
                if (!empty($path)) {
                    $analysis['disallowed_paths'][] = [
                        'user_agent' => $currentUserAgent,
                        'path' => $path,
                    ];
                }
            } elseif (stripos($line, 'Allow:') === 0) {
                $path = trim(substr($line, 6));
                if (!empty($path)) {
                    $analysis['allowed_paths'][] = [
                        'user_agent' => $currentUserAgent,
                        'path' => $path,
                    ];
                }
            } elseif (stripos($line, 'Sitemap:') === 0) {
                $sitemap = trim(substr($line, 8));
                $analysis['sitemaps'][] = $sitemap;
            }
        }

        return response()->json($analysis);
    }
}
