<?php
// V-FINAL-1730-TEST-84 (Created)

namespace Tests\Feature;

use Tests\FeatureTestCase;
use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;

class UserNotificationEndpointsTest extends FeatureTestCase
{
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Only seed once for this entire test suite run
        static $seeded = false;
        if (!$seeded) {
            $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
            $seeded = true;
        }
        
        $this->user = User::factory()->create();
        $this->user->assignRole('user');
    }

    /**
     * Helper to create a test notification
     */
    private function createNotification($read = false)
    {
        return DatabaseNotification::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'type' => 'TestNotification',
            'notifiable_type' => User::class,
            'notifiable_id' => $this->user->id,
            'data' => ['title' => 'Test', 'message' => 'This is a test'],
            'read_at' => $read ? now() : null,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function testGetNotificationsReturnsUserNotifications()
    {
        // 1. Clear auto-generated notifications from Seeders/Observers
            $this->user->notifications()->delete();

            // 2. Create exactly 2 for the test
            \App\Models\Notification::factory()->count(2)->create([
                'user_id' => $this->user->id,
                'notification_type' => 'email' // Priority #1 fix: ensure this matches schema
            ]);

        $response = $this->actingAs($this->user)->getJson('/api/v1/user/notifications');
        
        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function testMarkNotificationAsRead()
    {
        $notification = $this->createNotification(false); // Unread
        $this->assertNull($notification->read_at);

        $response = $this->actingAs($this->user)->postJson("/api/v1/user/notifications/{$notification->id}/read");
        
        $response->assertStatus(200);
        $this->assertNotNull($notification->fresh()->read_at);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function testMarkAllNotificationsAsRead()
    {
        $this->createNotification(false);
        $this->createNotification(false);
        
        $this->assertEquals(2, $this->user->unreadNotifications()->count());
        
        $response = $this->actingAs($this->user)->postJson("/api/v1/user/notifications/mark-all-read");
        
        $response->assertStatus(200);
        $this->assertEquals(0, $this->user->unreadNotifications()->count());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function testDeleteNotification()
    {
        $notification = $this->createNotification();
        $this->assertDatabaseHas('notifications', ['id' => $notification->id]);
        
        $response = $this->actingAs($this->user)->deleteJson("/api/v1/user/notifications/{$notification->id}");
        
        $response->assertStatus(200);
        $this->assertDatabaseMissing('notifications', ['id' => $notification->id]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function testNotificationsPaginated()
    {
        // Create 25 notifications
        for ($i = 0; $i < 25; $i++) {
            $this->createNotification();
        }

        $response = $this->actingAs($this->user)->getJson('/api/v1/user/notifications');
        
        $response->assertStatus(200);
        $response->assertJsonCount(20, 'data'); // Default page size is 20
        $response->assertJsonPath('meta.total', 25);
        $response->assertJsonPath('meta.per_page', 20);
    }
}
