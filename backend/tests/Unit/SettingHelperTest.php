<?php
// V-FINAL-1730-TEST-54 (Created)

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Setting;
use App\Models\User;
use App\Services\SmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class SettingHelperTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
        
        Cache::flush(); // Clear cache before each test
    }

    public function test_setting_key_is_unique()
    {
        Setting::create(['key' => 'unique_key', 'value' => 'a', 'type' => 'string']);
        $this->expectException(QueryException::class);
        Setting::create(['key' => 'unique_key', 'value' => 'b', 'type' => 'string']);
    }

    public function test_setting_value_casts_by_type()
    {
        Setting::create(['key' => 'test_bool', 'value' => 'true', 'type' => 'boolean']);
        Setting::create(['key' => 'test_num', 'value' => '123', 'type' => 'number']);
        Setting::create(['key' => 'test_json', 'value' => '{"a":1}', 'type' => 'json']);

        $this->assertIsBool(setting('test_bool'));
        $this->assertTrue(setting('test_bool'));

        $this->assertIsInt(setting('test_num'));
        $this->assertEquals(123, setting('test_num'));

        $this->assertIsArray(setting('test_json'));
        $this->assertEquals(1, setting('test_json')['a']);
    }

    public function test_setting_type_enum_validates()
    {
        $validTypes = ['string', 'number', 'boolean', 'json', 'text'];
        
        $validator = Validator::make(['type' => 'json'], ['type' => 'in:' . implode(',', $validTypes)]);
        $this->assertTrue($validator->passes());
        
        $validator = Validator::make(['type' => 'datetime'], ['type' => 'in:' . implode(',', $validTypes)]);
        $this->assertFalse($validator->passes());
    }

    public function test_setting_retrieves_by_key()
    {
        Setting::create(['key' => 'find_me', 'value' => 'Found!', 'type' => 'string']);
        $this->assertEquals('Found!', setting('find_me'));
    }

    public function test_setting_returns_default_if_not_found()
    {
        $this->assertEquals('default', setting('non_existent', 'default'));
    }

    public function test_helper_caches_frequently_accessed_settings()
    {
        Setting::create(['key' => 'cache_test', 'value' => 'cached', 'type' => 'string']);
        
        // 1. Enable query log to count DB hits
        DB::enableQueryLog();
        
        // 2. Call the helper 3 times
        $this->assertEquals('cached', setting('cache_test')); // Hit 1 (DB + Cache Set)
        $this->assertEquals('cached', setting('cache_test')); // Hit 2 (Cache Get)
        $this->assertEquals('cached', setting('cache_test')); // Hit 3 (Cache Get)
        
        // 3. Get the log
        $queryLog = DB::getQueryLog();
        
        // 4. Filter for only the 'select from settings' query
        $queries = array_filter($queryLog, function($q) {
            return str_contains($q['query'], 'from `settings`');
        });

        // 5. Assert it only ran ONCE
        $this->assertCount(1, $queries);
    }

    public function test_helper_invalidates_cache_on_update()
    {
        Setting::create(['key' => 'update_test', 'value' => 'old_value', 'type' => 'string']);

        // 1. Call, which populates the cache
        $this->assertEquals('old_value', setting('update_test'));
        
        // 2. Simulate Admin update using the API
        $this->actingAs($this->admin)->putJson('/api/v1/admin/settings', [
            'settings' => [
                ['key' => 'update_test', 'value' => 'new_value']
            ]
        ]);
        
        // 3. The helper function *should* now miss the cache and re-fetch
        $this->assertEquals('new_value', setting('update_test'));
    }

    public function test_setting_tracks_updated_by_admin()
    {
        Setting::create(['key' => 'admin_track', 'value' => 'v1']);

        $this->actingAs($this->admin)->putJson('/api/v1/admin/settings', [
            'settings' => [
                ['key' => 'admin_track', 'value' => 'v2']
            ]
        ]);

        $this->assertDatabaseHas('settings', [
            'key' => 'admin_track',
            'updated_by' => $this->admin->id
        ]);
    }
}
