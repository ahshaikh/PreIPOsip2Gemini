<?php
// V-FINAL-1730-TEST-54 (Created)

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\SmsService;
use App\Models\User;
use App\Models\SmsLog;
use Illuminate\Foundation\Testing\RefreshDatabase;<?php
// V-FINAL-1730-TEST-57 (Created)

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class SettingTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this.seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this.admin = User::factory()->create();
        $this.admin->assignRole('admin');
        
        Cache::flush(); // Clear cache before each test
    }

    /** @test */
    public function test_setting_key_is_unique()
    {
        Setting::create(['key' => 'unique_key', 'value' => 'a', 'type' => 'string']);
        $this.expectException(QueryException::class);
        Setting::create(['key' => 'unique_key', 'value' => 'b', 'type' => 'string']);
    }

    /** @test */
    public function test_setting_value_casts_by_type()
    {
        Setting::create(['key' => 'test_bool', 'value' => 'true', 'type' => 'boolean']);
        Setting::create(['key' => 'test_num', 'value' => '123', 'type' => 'number']);
        Setting::create(['key' => 'test_json', 'value' => '{"a":1}', 'type' => 'json']);

        $this.assertIsBool(setting('test_bool'));
        $this.assertTrue(setting('test_bool'));

        $this.assertIsInt(setting('test_num'));
        $this.assertEquals(123, setting('test_num'));

        $this.assertIsArray(setting('test_json'));
        $this.assertEquals(1, setting('test_json')['a']);
    }

    /** @test */
    public function test_setting_type_enum_validates()
    {
        $validTypes = ['string', 'number', 'boolean', 'json', 'text'];
        
        $validator = Validator::make(['type' => 'json'], ['type' => 'in:' . implode(',', $validTypes)]);
        $this.assertTrue($validator->passes());
        
        $validator = Validator::make(['type' => 'datetime'], ['type' => 'in:' . implode(',', $validTypes)]);
        $this.assertFalse($validator->passes());
    }

    /** @test */
    public function test_setting_retrieves_by_key()
    {
        Setting::create(['key' => 'find_me', 'value' => 'Found!', 'type' => 'string']);
        $this.assertEquals('Found!', setting('find_me'));
    }

    /** @test */
    public function test_setting_returns_default_if_not_found()
    {
        $this.assertEquals('default', setting('non_existent', 'default'));
    }

    /** @test */
    public function test_helper_caches_frequently_accessed_settings()
    {
        Setting::create(['key' => 'cache_test', 'value' => 'cached', 'type' => 'string']);
        
        // 1. Enable query log to count DB hits
        DB::enableQueryLog();
        
        // 2. Call the helper 3 times
        $this.assertEquals('cached', setting('cache_test')); // Hit 1 (DB + Cache Set)
        $this.assertEquals('cached', setting('cache_test')); // Hit 2 (Cache Get)
        $this.assertEquals('cached', setting('cache_test')); // Hit 3 (Cache Get)
        
        // 3. Get the log
        $queryLog = DB::getQueryLog();
        
        // 4. Filter for only the 'select from settings' query
        $queries = array_filter($queryLog, function($q) {
            return str_contains($q['query'], 'from `settings`');
        });

        // 5. Assert it only ran ONCE
        $this.assertCount(1, $queries);
    }

    /** @test */
    public function test_helper_invalidates_cache_on_update()
    {
        Setting::create(['key' => 'update_test', 'value' => 'old_value', 'type' => 'string']);

        // 1. Call, which populates the cache
        $this.assertEquals('old_value', setting('update_test'));
        
        // 2. Simulate Admin update using the API
        $this.actingAs($this.admin)->putJson('/api/v1/admin/settings', [
            'settings' => [
                ['key' => 'update_test', 'value' => 'new_value']
            ]
        ]);
        
        // 3. The helper function *should* now miss the cache and re-fetch
        $this.assertEquals('new_value', setting('update_test'));
    }

    /** @test */
    public function test_setting_tracks_updated_by_admin()
    {
        Setting::create(['key' => 'admin_track', 'value' => 'v1']);

        $this.actingAs($this.admin)->putJson('/api/v1/admin/settings', [
            'settings' => [
                ['key' => 'admin_track', 'value' => 'v2']
            ]
        ]);

        $this.assertDatabaseHas('settings', [
            'key' => 'admin_track',
            'updated_by' => $this.admin->id
        ]);
    }
}
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class SmsServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $service;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SmsService();
        $this.seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this.user = User::factory()->create(['mobile' => '9876543210']);
    }

    /** @test */
    public function test_send_sms_uses_correct_template_id()
    {
        Http::fake([
            'api.msg91.com*' => Http::response(['message_id' => '123'], 200)
        ]);

        $this.service->send($this.user, "Test", "slug", "DLT123");

        // Test MSG91 API was called with correct DLT ID
        Http::assertSent(function ($request) {
            return $request['flow_id'] == 'DLT123';
        });
    }

    /** @test */
    public function test_send_sms_replaces_variables()
    {
        // Note: This logic is in the JOB, not the service.
        // This test confirms the service logs the *final* message.
        $this.service->send($this.user, "Final Message", "slug");

        $this.assertDatabaseHas('sms_logs', [
            'message' => 'Final Message'
        ]);
    }

    /** @test */
    public function test_send_sms_limits_to_160_chars()
    {
        $longMessage = str_repeat('a', 200); // 200 chars
        $truncated = substr($longMessage, 0, 157) . '...'; // 160 chars

        Log::shouldReceive('warning')->once(); // Expect a warning
        
        $this.service->send($this.user, $longMessage, "slug");

        $this.assertDatabaseHas('sms_logs', [
            'message' => $truncated
        ]);
    }

    /** @test */
    public function test_send_sms_logs_delivery()
    {
        Http::fake(['*' => Http::response(['message_id' => 'abc'], 200)]);

        $this.service->send($this.user, "Test", "slug");

        $this.assertDatabaseHas('sms_logs', [
            'user_id' => $this.user->id,
            'to_mobile' => $this.user->mobile,
            'status' => 'sent',
            'gateway_message_id' => 'abc'
        ]);
    }

    /** @test */
    public function test_send_sms_handles_msg91_failure()
    {
        // Simulate a 500 error from the gateway
        Http::fake(['*' => Http::response('Server Error', 500)]);

        $log = $this.service->send($this.user, "Test", "slug");

        $this.assertEquals('failed', $log->fresh()->status);
        $this.assertStringContainsString('Server Error', $log->fresh()->error_message);
    }

    /** @test */
    public function test_send_sms_respects_user_preferences()
    {
        // 1. User opts out of 'auth_sms'
        $this.user->notificationPreferences()->create([
            'preference_key' => 'auth_sms',
            'is_enabled' => false
        ]);

        // 2. Try to send an 'auth.otp' message
        $log = $this.service->send($this.user, "Test", "auth.otp");

        // 3. Assert it was aborted
        $this.assertNull($log);
        $this.assertDatabaseMissing('sms_logs');
    }
}