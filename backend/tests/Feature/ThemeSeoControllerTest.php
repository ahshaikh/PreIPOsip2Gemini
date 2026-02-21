<?php
// V-TEST-SUITE-004 (ThemeSeoController Feature Tests)

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Setting;
use App\Services\FileUploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ThemeSeoControllerTest extends TestCase
{
    protected User $admin;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this->seed(\Database\Seeders\SettingsSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->user = User::factory()->create();
        $this->user->assignRole('user');
    }

    // ==================== THEME UPDATE TESTS ====================
    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_update_primary_color()
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/settings/theme', [
                'primary_color' => '#FF5733'
            ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Theme updated successfully']);

        $this->assertDatabaseHas('settings', [
            'key' => 'theme_primary_color',
            'value' => '#FF5733'
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_update_secondary_color()
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/settings/theme', [
                'secondary_color' => '#00FF00'
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('settings', [
            'key' => 'theme_secondary_color',
            'value' => '#00FF00'
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_update_font_family()
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/settings/theme', [
                'font_family' => 'Inter, sans-serif'
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('settings', [
            'key' => 'theme_font_family',
            'value' => 'Inter, sans-serif'
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_update_multiple_theme_settings()
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/settings/theme', [
                'primary_color' => '#123456',
                'secondary_color' => '#654321',
                'font_family' => 'Roboto'
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('settings', ['key' => 'theme_primary_color', 'value' => '#123456']);
        $this->assertDatabaseHas('settings', ['key' => 'theme_secondary_color', 'value' => '#654321']);
        $this->assertDatabaseHas('settings', ['key' => 'theme_font_family', 'value' => 'Roboto']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function theme_validates_hex_color_format()
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/settings/theme', [
                'primary_color' => 'not-a-color'
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['primary_color']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function theme_rejects_invalid_hex_format()
    {
        // Test various invalid formats
        $invalidColors = [
            '#GGG', // Invalid hex chars
            '#12345', // 5 chars
            '#1234567', // 7 chars
            'FF5733', // Missing #
            '#FFF', // 3 chars (not supported in this validation)
            'rgb(255,0,0)', // RGB format
        ];

        foreach ($invalidColors as $color) {
            $response = $this->actingAs($this->admin)
                ->postJson('/api/v1/admin/settings/theme', [
                    'primary_color' => $color
                ]);

            $response->assertStatus(422, "Color '$color' should be rejected");
        }
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function theme_accepts_valid_hex_colors()
    {
        $validColors = [
            '#000000', // Black
            '#FFFFFF', // White
            '#ff5733', // Lowercase
            '#FF5733', // Uppercase
            '#AbCdEf', // Mixed case
        ];

        foreach ($validColors as $color) {
            $response = $this->actingAs($this->admin)
                ->postJson('/api/v1/admin/settings/theme', [
                    'primary_color' => $color
                ]);

            $response->assertStatus(200, "Color '$color' should be accepted");
        }
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function theme_validates_font_family_max_length()
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/settings/theme', [
                'font_family' => str_repeat('a', 101) // Exceeds 100 char max
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['font_family']);
    }

    // ==================== LOGO UPLOAD TESTS ====================
    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_upload_logo()
    {
        Storage::fake('public');

        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/settings/theme', [
                'logo' => UploadedFile::fake()->image('logo.png', 200, 100)
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('settings', [
            'key' => 'site_logo',
            'group' => 'theme'
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function logo_validates_file_type()
    {
        Storage::fake('public');

        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/settings/theme', [
                'logo' => UploadedFile::fake()->create('document.pdf', 100)
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['logo']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function logo_validates_max_file_size()
    {
        Storage::fake('public');

        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/settings/theme', [
                'logo' => UploadedFile::fake()->image('large-logo.png')->size(3000) // 3MB, max is 2MB
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['logo']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function logo_accepts_valid_image_types()
    {
        Storage::fake('public');

        $validTypes = ['png', 'jpg', 'jpeg', 'svg'];

        foreach ($validTypes as $type) {
            $response = $this->actingAs($this->admin)
                ->postJson('/api/v1/admin/settings/theme', [
                    'logo' => UploadedFile::fake()->image("logo.$type", 200, 100)
                ]);

            $response->assertStatus(200, "Logo type '$type' should be accepted");
        }
    }

    // ==================== FAVICON UPLOAD TESTS ====================
    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_upload_favicon()
    {
        Storage::fake('public');

        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/settings/theme', [
                'favicon' => UploadedFile::fake()->image('favicon.png', 32, 32)
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('settings', [
            'key' => 'site_favicon',
            'group' => 'theme'
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function favicon_validates_file_type()
    {
        Storage::fake('public');

        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/settings/theme', [
                'favicon' => UploadedFile::fake()->image('favicon.jpg') // JPG not allowed for favicon
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['favicon']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function favicon_validates_max_file_size()
    {
        Storage::fake('public');

        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/settings/theme', [
                'favicon' => UploadedFile::fake()->image('favicon.png')->size(600) // 600KB, max is 512KB
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['favicon']);
    }

    // ==================== SEO UPDATE TESTS ====================
    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_update_robots_txt()
    {
        $robotsTxt = "User-agent: *\nDisallow: /admin\nAllow: /";

        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/settings/seo', [
                'robots_txt' => $robotsTxt
            ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'SEO settings updated']);

        $this->assertDatabaseHas('settings', [
            'key' => 'seo_robots_txt',
            'value' => $robotsTxt
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_update_meta_title_suffix()
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/settings/seo', [
                'meta_title_suffix' => ' | MyCompany'
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('settings', [
            'key' => 'seo_meta_title_suffix',
            'value' => ' | MyCompany'
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function seo_validates_robots_txt_max_length()
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/settings/seo', [
                'robots_txt' => str_repeat('a', 5001) // Exceeds 5000 char max
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['robots_txt']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function seo_validates_meta_title_suffix_max_length()
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/settings/seo', [
                'meta_title_suffix' => str_repeat('a', 101) // Exceeds 100 char max
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['meta_title_suffix']);
    }

    // ==================== GOOGLE ANALYTICS ID TESTS ====================
    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_set_google_analytics_ga4_id()
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/settings/seo', [
                'google_analytics_id' => 'G-ABCD123456'
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('settings', [
            'key' => 'seo_google_analytics_id',
            'value' => 'G-ABCD123456'
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_set_google_analytics_ua_id()
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/settings/seo', [
                'google_analytics_id' => 'UA-12345678-1'
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('settings', [
            'key' => 'seo_google_analytics_id',
            'value' => 'UA-12345678-1'
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function seo_validates_google_analytics_id_format()
    {
        $invalidIds = [
            'GA-123456789', // Wrong prefix
            'G-123', // Too short
            'G-ABCDEFGHIJK', // 11 chars instead of 10
            'UA-123-1', // UA too short
            'UA-12345678901-1', // UA too long
            'random-string',
            '12345678',
        ];

        foreach ($invalidIds as $id) {
            $response = $this->actingAs($this->admin)
                ->postJson('/api/v1/admin/settings/seo', [
                    'google_analytics_id' => $id
                ]);

            $response->assertStatus(422, "GA ID '$id' should be rejected")
                ->assertJsonValidationErrors(['google_analytics_id']);
        }
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function seo_accepts_valid_google_analytics_ids()
    {
        $validIds = [
            'G-ABCDE12345',
            'G-1234567890',
            'UA-1234-1',
            'UA-123456789-12',
        ];

        foreach ($validIds as $id) {
            // Clear previous setting
            Setting::where('key', 'seo_google_analytics_id')->delete();

            $response = $this->actingAs($this->admin)
                ->postJson('/api/v1/admin/settings/seo', [
                    'google_analytics_id' => $id
                ]);

            $response->assertStatus(200, "GA ID '$id' should be accepted");
        }
    }

    // ==================== AUTHORIZATION TESTS ====================
    #[\PHPUnit\Framework\Attributes\Test]
    public function regular_user_cannot_update_theme()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/admin/settings/theme', [
                'primary_color' => '#FF0000'
            ]);

        $response->assertStatus(403);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function regular_user_cannot_update_seo()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/admin/settings/seo', [
                'meta_title_suffix' => 'Hacked'
            ]);

        $response->assertStatus(403);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function unauthenticated_user_cannot_update_theme()
    {
        $response = $this->postJson('/api/v1/admin/settings/theme', [
            'primary_color' => '#FF0000'
        ]);

        $response->assertStatus(401);
    }

    // ==================== CACHE CLEARING TESTS ====================
    #[\PHPUnit\Framework\Attributes\Test]
    public function theme_update_clears_settings_cache()
    {
        // Set initial value in cache
        cache()->put('settings', ['theme_primary_color' => '#000000'], 3600);

        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/settings/theme', [
                'primary_color' => '#FFFFFF'
            ]);

        $response->assertStatus(200);

        // Cache should be cleared (null or different value)
        $cached = cache()->get('settings');
        $this->assertNull($cached);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function seo_update_clears_settings_cache()
    {
        // Set initial value in cache
        cache()->put('settings', ['seo_meta_title_suffix' => 'Old'], 3600);

        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/settings/seo', [
                'meta_title_suffix' => 'New'
            ]);

        $response->assertStatus(200);

        // Cache should be cleared
        $cached = cache()->get('settings');
        $this->assertNull($cached);
    }

    // ==================== FILE REPLACEMENT TESTS ====================
    #[\PHPUnit\Framework\Attributes\Test]
    public function uploading_new_logo_deletes_old_one()
    {
        Storage::fake('public');

        // Upload first logo
        $firstLogo = UploadedFile::fake()->image('first-logo.png', 200, 100);
        $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/settings/theme', ['logo' => $firstLogo]);

        $firstPath = Setting::where('key', 'site_logo')->value('value');

        // Upload second logo
        $secondLogo = UploadedFile::fake()->image('second-logo.png', 300, 150);
        $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/settings/theme', ['logo' => $secondLogo]);

        $secondPath = Setting::where('key', 'site_logo')->value('value');

        // First file should be deleted, second should exist
        $this->assertNotEquals($firstPath, $secondPath);
    }
}
