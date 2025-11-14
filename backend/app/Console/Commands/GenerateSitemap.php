<?php
// V-FINAL-1730-279 (Created) | V-FINAL-1730-430 

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;
use App\Models\BlogPost;
use App\Models\Page;
use Illuminate\Support\Facades\File;

class GenerateSitemap extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'sitemap:generate';

    /**
     * The console command description.
     */
    protected $description = 'Generate the sitemap.xml file for SEO';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Generating sitemap.xml...');

        $baseUrl = rtrim(env('FRONTEND_URL'), '/');
        $urls = [];

        // --- 1. Static Pages ---
        $staticPages = [
            '/', 
            '/plans', 
            '/how-it-works', 
            '/calculator', 
            '/products', 
            '/blog', 
            '/faq', 
            '/contact',
            '/login',
            '/signup'
        ];

        foreach ($staticPages as $page) {
            $urls[] = [
                'loc' => $baseUrl . $page,
                'lastmod' => date('Y-m-d'),
                'priority' => $page === '/' ? '1.0' : '0.8',
                'changefreq' => 'weekly'
            ];
        }

        // --- 2. Dynamic CMS Pages ---
        $pages = Page::where('status', 'published')->get();
        foreach ($pages as $page) {
            $urls[] = [
                'loc' => $baseUrl . '/' . $page->slug,
                'lastmod' => $page->updated_at->format('Y-m-d'),
                'priority' => '0.7',
                'changefreq' => 'monthly'
            ];
        }

        // --- 3. Dynamic Product Pages ---
        $products = Product::where('status', 'active')->get();
        foreach ($products as $product) {
            $urls[] = [
                'loc' => $baseUrl . '/products/' . $product->slug,
                'lastmod' => $product->updated_at->format('Y-m-d'),
                'priority' => '0.9',
                'changefreq' => 'weekly'
            ];
        }

        // --- 4. Dynamic Blog Posts ---
        $posts = BlogPost::where('status', 'published')->get();
        foreach ($posts as $post) {
            $urls[] = [
                'loc' => $baseUrl . '/blog/' . $post->slug,
                'lastmod' => $post->updated_at->format('Y-m-d'),
                'priority' => '0.6',
                'changefreq' => 'weekly'
            ];
        }

        // --- Build XML ---
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        
        foreach ($urls as $url) {
            $xml .= '<url>';
            $xml .= "<loc>{$url['loc']}</loc>";
            $xml .= "<lastmod>{$url['lastmod']}</lastmod>";
            $xml .= "<changefreq>{$url['changefreq']}</changefreq>";
            $xml .= "<priority>{$url['priority']}</priority>";
            $xml .= '</url>';
        }
        
        $xml .= '</urlset>';

        // Save to public folder (accessible by Google)
        File::put(public_path('sitemap.xml'), $xml);

        $this->info('sitemap.xml generated successfully at ' . public_path('sitemap.xml'));
    }
}
