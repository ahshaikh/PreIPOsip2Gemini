<?php
// V-FINAL-1730-TEST-85 (Created)

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;

class UserNotificationEndpointsTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        
        $this->user = User::factory()->create();
        $this->user->assignRole('user');
    }

    /**
     * Helper to create a test notification for our user.
     */
    private function createNotification($read = false): DatabaseNotification
    {
        return DatabaseNotification::create([
            'id' => Str::uuid(),
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
        // Arrange
        $this->createNotification();
        $this->createNotification();

        // Act
        $response = $this->actingAs($this->user)->getJson('/api/v1/user/notifications');

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data'); // Check that 2 notifications are returned
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function testMarkNotificationAsRead()
    {
        // Arrange
        $notification = $this->createNotification(false); // Unread
        $this->assertNull($notification->read_at);

        // Act
        $response = $this->actingAs($this->user)->postJson("/api/v1/user/notifications/{$notification->id}/read");
        
        // Assert
        $response->assertStatus(200);
        $this->assertNotNull($notification->fresh()->read_at);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function testMarkAllNotificationsAsRead()
    {
        // Arrange
        $this->createNotification(false);
        $this->createNotification(false);
        $this->createNotification(false);
        
        $this->assertEquals(3, $this->user->unreadNotifications()->count());
        
        // Act
        $response = $this->actingAs($this->user)->postJson("/api/v1/user/notifications/mark-all-read");
        
        // Assert
        $response->assertStatus(200);
        $this->assertEquals(0, $this->user->unreadNotifications()->count());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function testDeleteNotification()
    {
        // Arrange
        $notification = $this->createNotification();
        $this->assertDatabaseHas('notifications', ['id' => $notification->id]);
        
        // Act
        $response = $this->actingAs($this->user)->deleteJson("/api/v1/user/notifications/{$notification->id}");
        
        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseMissing('notifications', ['id' => $notification->id]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function testNotificationsPaginated()
    {
        // Arrange: Create 25 notifications
        // The controller paginates at 20
        for ($i = 0; $i < 25; $i++) {
            $this->createNotification();
        }

        // Act
        $response = $this->actingAs($this->user)->getJson('/api/v1/user/notifications');
        
        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(20, 'data'); // Page 1 has 20 items
        $response->assertJsonPath('meta.total', 25);
        $response->assertJsonPath('meta.per_page', 20);
        $response->assertJsonPath('meta.current_page', 1);
    }
}