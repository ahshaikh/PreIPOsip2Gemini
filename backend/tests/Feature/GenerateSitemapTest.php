<?php
// V-FINAL-1730-TEST-76 (Created)

namespace Tests\Feature;

use Illuminate.Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\BlogPost;
use App\Models\Page;
use Illuminate\Support\Facades\File;

class GenerateSitemapTest extends TestCase
{
    use RefreshDatabase;

    protected $sitemapPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        
        $this->sitemapPath = public_path('sitemap.xml');
        
        // Clean up any old sitemap before starting
        if (File::exists($this->sitemapPath)) {
            File::delete($this->sitemapPath);
        }
    }

    protected function tearDown(): void
    {
        // Clean up after each test
        if (File::exists($this->sitemapPath)) {
            File::delete($this->sitemapPath);
        }
        parent::tearDown();
    }

    /** @test */
    public function test_generates_sitemap_xml()
    {
        $this->assertFileDoesNotExist($this->sitemapPath);

        // Run the Artisan command
        $this->artisan('sitemap:generate')
             ->expectsOutput('Generating sitemap...')
             ->expectsOutputToContain('sitemap.xml generated successfully')
             ->assertExitCode(0);

        // Check that the file was created
        $this->assertFileExists($this->sitemapPath);
    }

    /** @test */
    public function test_includes_all_public_pages()
    {
        $this->artisan('sitemap:generate');
        
        $content = File::get($this->sitemapPath);

        // Check for core static pages
        $this->assertStringContainsString('<loc>' . env('FRONTEND_URL') . '/</loc>', $content);
        $this->assertStringContainsString('<loc>' . env('FRONTEND_URL') . '/plans</loc>', $content);
        $this->assertStringContainsString('<loc>' . env('FRONTEND_URL') . '/contact</loc>', $content);
        $this->assertStringContainsString('<loc>' . env('FRONTEND_URL') . '/faq</loc>', $content);
        $this->assertStringContainsString('<loc>' . env('FRONTEND_URL') . '/login</loc>', $content);
    }

    /** @test */
    public function test_includes_dynamic_routes()
    {
        // 1. Create Published Content
        $publishedPage = Page::factory()->create([
            'slug' => 'published-page',
            'status' => 'published'
        ]);
        
        $publishedPost = BlogPost::factory()->create([
            'slug' => 'published-post',
            'status' => 'published',
            'author_id' => User::factory()->create()->id
        ]);

        // 2. Create Draft Content (Should NOT be included)
        Page::factory()->create([
            'slug' => 'draft-page',
            'status' => 'draft'
        ]);
        
        BlogPost::factory()->create([
            'slug' => 'draft-post',
            'status' => 'draft',
            'author_id' => User::factory()->create()->id
        ]);

        // 3. Run the command
        $this->artisan('sitemap:generate');
        
        // 4. Check the file content
        $content = File::get($this->sitemapPath);

        // Assert Published content IS included
        $this->assertStringContainsString($publishedPage->slug, $content);
        $this->assertStringContainsString($publishedPost->slug, $content);
        
        // Assert Draft content is NOT included
        $this->assertStringNotContainsString('draft-page', $content);
        $this->assertStringNotContainsString('draft-post', $content);
    }
}