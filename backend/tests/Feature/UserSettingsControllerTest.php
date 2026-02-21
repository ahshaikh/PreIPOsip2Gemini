<?php
// [AUDIT FIX] Created comprehensive tests for UserSettingsController
// Module 2: User Management - User Settings Tests

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UserSettingsControllerTest extends TestCase
{
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedDatabase();

        $this->user = User::factory()->create();
        $this->user->assignRole('user');
    }

    private function seedDatabase()
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this->seed(\Database\Seeders\SettingsSeeder::class);
    }

    // ==================== GET SETTINGS TESTS ====================

    #[\PHPUnit\Framework\Attributes\Test]
    public function unauthenticated_user_cannot_access_settings()
    {
        // No actingAs()
        $response = $this->getJson('/api/v1/user/settings');
        $response->assertStatus(401); // Unauthorized
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_can_get_default_settings_when_none_exist()
    {
        $response = $this->actingAs($this->user)->getJson('/api/v1/user/settings');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'notifications',
                'security',
                'preferences'
            ]
        ]);

        // Verify default values are returned
        $response->assertJsonPath('data.notifications.email_notifications', true);
        $response->assertJsonPath('data.notifications.sms_notifications', true);
        $response->assertJsonPath('data.security.two_factor_enabled', false);
        $response->assertJsonPath('data.preferences.language', 'en');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_can_get_saved_settings()
    {
        // Insert custom settings for the user
        DB::table('user_settings')->insert([
            'user_id' => $this->user->id,
            'settings' => json_encode([
                'notifications' => [
                    'email_notifications' => false,
                    'sms_notifications' => true,
                ],
                'security' => [
                    'two_factor_enabled' => true,
                ],
                'preferences' => [
                    'language' => 'hi',
                    'theme' => 'dark',
                ]
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/v1/user/settings');

        $response->assertStatus(200);
        $response->assertJsonPath('data.notifications.email_notifications', false);
        $response->assertJsonPath('data.notifications.sms_notifications', true);
        $response->assertJsonPath('data.security.two_factor_enabled', true);
        $response->assertJsonPath('data.preferences.language', 'hi');
        $response->assertJsonPath('data.preferences.theme', 'dark');
    }

    // ==================== UPDATE SETTINGS TESTS ====================

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_can_update_notification_settings()
    {
        $response = $this->actingAs($this->user)->putJson('/api/v1/user/settings', [
            'notifications' => [
                'email_notifications' => false,
                'sms_notifications' => false,
                'push_notifications' => true,
                'payment_alerts' => true,
            ]
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Settings updated successfully'
        ]);

        // Verify settings were saved in database
        $settings = DB::table('user_settings')
            ->where('user_id', $this->user->id)
            ->first();

        $this->assertNotNull($settings);
        $data = json_decode($settings->settings, true);
        $this->assertFalse($data['notifications']['email_notifications']);
        $this->assertFalse($data['notifications']['sms_notifications']);
        $this->assertTrue($data['notifications']['push_notifications']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_can_update_security_settings()
    {
        $response = $this->actingAs($this->user)->putJson('/api/v1/user/settings', [
            'security' => [
                'login_alerts' => true,
                'session_timeout' => 60,
            ]
        ]);

        $response->assertStatus(200);

        // Verify settings were saved
        $settings = DB::table('user_settings')
            ->where('user_id', $this->user->id)
            ->first();

        $data = json_decode($settings->settings, true);
        $this->assertTrue($data['security']['login_alerts']);
        $this->assertEquals(60, $data['security']['session_timeout']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_can_update_preference_settings()
    {
        $response = $this->actingAs($this->user)->putJson('/api/v1/user/settings', [
            'preferences' => [
                'language' => 'hi',
                'currency' => 'USD',
                'timezone' => 'Asia/Kolkata',
                'theme' => 'dark',
            ]
        ]);

        $response->assertStatus(200);

        // Verify settings were saved
        $settings = DB::table('user_settings')
            ->where('user_id', $this->user->id)
            ->first();

        $data = json_decode($settings->settings, true);
        $this->assertEquals('hi', $data['preferences']['language']);
        $this->assertEquals('USD', $data['preferences']['currency']);
        $this->assertEquals('dark', $data['preferences']['theme']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function update_merges_with_existing_settings()
    {
        // First, create initial settings
        $this->actingAs($this->user)->putJson('/api/v1/user/settings', [
            'notifications' => [
                'email_notifications' => false,
                'sms_notifications' => true,
            ],
            'preferences' => [
                'language' => 'en',
                'theme' => 'light',
            ]
        ]);

        // Now update only notification settings
        $response = $this->actingAs($this->user)->putJson('/api/v1/user/settings', [
            'notifications' => [
                'push_notifications' => true,
            ]
        ]);

        $response->assertStatus(200);

        // Verify new notification setting was added, and existing ones preserved
        $settings = DB::table('user_settings')
            ->where('user_id', $this->user->id)
            ->first();

        $data = json_decode($settings->settings, true);

        // Old notification settings should be preserved
        $this->assertFalse($data['notifications']['email_notifications']);
        $this->assertTrue($data['notifications']['sms_notifications']);

        // New notification setting should be added
        $this->assertTrue($data['notifications']['push_notifications']);

        // Preferences should remain unchanged
        $this->assertEquals('en', $data['preferences']['language']);
        $this->assertEquals('light', $data['preferences']['theme']);
    }

    // ==================== AUTHORIZATION TESTS ====================
    // [AUDIT FIX] Critical: Verify user cannot access/modify another user's settings

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_cannot_access_another_users_settings()
    {
        // Create another user with settings
        $otherUser = User::factory()->create();
        $otherUser->assignRole('user');

        DB::table('user_settings')->insert([
            'user_id' => $otherUser->id,
            'settings' => json_encode([
                'notifications' => [
                    'email_notifications' => false,
                ],
                'preferences' => [
                    'language' => 'hi',
                ]
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Authenticated as $this->user, try to get settings
        // The controller uses $request->user() which automatically ensures
        // the authenticated user only gets their own settings
        $response = $this->actingAs($this->user)->getJson('/api/v1/user/settings');

        $response->assertStatus(200);

        // Should return default settings for $this->user, not $otherUser's settings
        $data = $response->json('data');

        // If we got default settings, language should be 'en' not 'hi'
        // This proves we're not accessing the other user's settings
        $this->assertEquals('en', $data['preferences']['language']);
        $this->assertTrue($data['notifications']['email_notifications']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_can_only_update_their_own_settings()
    {
        // Create another user
        $otherUser = User::factory()->create();
        $otherUser->assignRole('user');

        // Authenticated as $this->user, update settings
        $response = $this->actingAs($this->user)->putJson('/api/v1/user/settings', [
            'preferences' => [
                'language' => 'hi',
            ]
        ]);

        $response->assertStatus(200);

        // Verify only authenticated user's settings were updated
        $thisUserSettings = DB::table('user_settings')
            ->where('user_id', $this->user->id)
            ->first();

        $thisData = json_decode($thisUserSettings->settings, true);
        $this->assertEquals('hi', $thisData['preferences']['language']);

        // Verify other user has no settings
        $otherUserSettings = DB::table('user_settings')
            ->where('user_id', $otherUser->id)
            ->first();

        $this->assertNull($otherUserSettings);
    }

    // ==================== DATA INTEGRITY TESTS ====================

    #[\PHPUnit\Framework\Attributes\Test]
    public function settings_timestamps_are_updated()
    {
        // Create initial settings
        $this->actingAs($this->user)->putJson('/api/v1/user/settings', [
            'preferences' => ['language' => 'en']
        ]);

        $initialSettings = DB::table('user_settings')
            ->where('user_id', $this->user->id)
            ->first();

        $initialUpdatedAt = $initialSettings->updated_at;

        // Wait a moment and update again
        sleep(1);

        $this->actingAs($this->user)->putJson('/api/v1/user/settings', [
            'preferences' => ['language' => 'hi']
        ]);

        $updatedSettings = DB::table('user_settings')
            ->where('user_id', $this->user->id)
            ->first();

        // Verify updated_at timestamp changed
        $this->assertNotEquals($initialUpdatedAt, $updatedSettings->updated_at);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function settings_are_stored_as_valid_json()
    {
        $this->actingAs($this->user)->putJson('/api/v1/user/settings', [
            'notifications' => [
                'email_notifications' => true,
            ],
            'preferences' => [
                'language' => 'en',
            ]
        ]);

        $settings = DB::table('user_settings')
            ->where('user_id', $this->user->id)
            ->first();

        // Verify settings column contains valid JSON
        $this->assertNotNull($settings->settings);
        $decoded = json_decode($settings->settings, true);
        $this->assertNotNull($decoded);
        $this->assertIsArray($decoded);
    }
}
