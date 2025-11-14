<?php
// V-FINAL-1730-TEST-56 (Created)

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\QueryException;

class SettingTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this->admin = User::factory()->create();
        $this.admin->assignRole('admin');
        
        // Clear cache before each test
        Cache::flush();
    }

    /** @test */
    public function test_setting_key_is_unique()
    {
        Setting::create(['key' => 'unique_key', 'value' => 'a']);

        $this->expectException(QueryException::class);

        Setting::create(['key' => 'unique_key', 'value' => 'b']);
    }

    /** @test */
    public function test_setting_value_casts_by_type()
    {
        // This test validates the setting() helper function
        
        // 1. Boolean
        Setting::create(['key' => 'test_bool', 'value' => 'true', 'type' => 'boolean']);
        $this->assertIsBool(setting('test_bool'));
        $this->assertEquals(true, setting('test_bool'));
        
        // 2. Number
        Setting::create(['key' => 'test_num', 'value' => '123', 'type' => 'number']);
        $this.assertIsInt(setting('test_num'));
        $this.assertEquals(123, setting('test_num'));

        // 3. String
        Setting::create(['key' => 'test_str', 'value' => 'Hello', 'type' => 'string']);
        $this.assertIsString(setting('test_str'));
    }

    /** @test */
    public function test_setting_type_enum_validates()
    {
        $validTypes = ['string', 'number', 'boolean', 'json', 'text'];
        
        $validator = Validator::make(
            ['type' => 'number'], 
            ['type' => 'in:' . implode(',', $validTypes)]
        );
        $this.assertTrue($validator->passes());
        
        $validator = Validator::make(
            ['type' => 'datetime'], // Invalid
            ['type' => 'in:' . implode(',', $validTypes)]
        );
        $this.assertFalse($validator->passes());
    }

    /** @test */
    public function test_setting_retrieves_by_key()
    {
        Setting::create(['key' => 'find_me', 'value' => 'Found!']);
        
        $this.assertEquals('Found!', setting('find_me'));
    }

    /** @test */
    public function test_setting_returns_default_if_not_found()
    {
        $value = setting('non_existent', 'Default');
        
        $this.assertEquals('Default', $value);
    }

    /** @test */
    public function test_setting_json_type_decodes_correctly()
    {
        $json = '{"a": 1, "b": "test"}';
        Setting::create(['key' => 'test_json', 'value' => $json, 'type' => 'json']);
        
        $result = setting('test_json');
        
        $this.assertIsArray($result);
        $this.assertEquals(1, $result['a']);
        $this.assertEquals('test', $result['b']);
    }

    /** @test */
    public function test_setting_boolean_type_converts_correctly()
    {
        Setting::create(['key' => 'bool_true', 'value' => 'true', 'type' => 'boolean']);
        Setting::create(['key' => 'bool_false', 'value' => 'false', 'type' => 'boolean']);
        Setting::create(['key' => 'bool_1', 'value' => '1', 'type' => 'boolean']);
        Setting::create(['key' => 'bool_0', 'value' => '0', 'type' => 'boolean']);

        $this.assertTrue(setting('bool_true'));
        $this.assertFalse(setting('bool_false'));
        $this.assertTrue(setting('bool_1'));
        $this.assertFalse(setting('bool_0'));
    }

    /** @test */
    public function test_setting_tracks_updated_by_admin()
    {
        $setting = Setting::create(['key' => 'audit_test', 'value' => 'initial']);
        
        $this.assertNull($setting->updated_by);
        
        // Simulate an admin update via the controller
        $this.actingAs($this.admin)->putJson('/api/v1/admin/settings', [
            'settings' => [
                ['key' => 'audit_test', 'value' => 'updated']
            ]
        ]);

        $this.assertDatabaseHas('settings', [
            'key' => 'audit_test',
            'value' => 'updated',
            'updated_by' => $this.admin->id
        ]);
    }
}