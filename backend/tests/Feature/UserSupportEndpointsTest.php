<?php
// V-FINAL-1730-TEST-89 (Created)

namespace Tests\Feature;

use Tests\FeatureTestCase;
use App\Models\User;
use App\Models\SupportTicket;
use App\Notifications\SupportReplyNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class UserSupportEndpointsTest extends FeatureTestCase
{
    protected $user;
    protected $otherUser;
    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedDatabase();
        
        $this->user = User::factory()->create();
        $this->user->assignRole('user');
        
        $this->otherUser = User::factory()->create();
        $this->otherUser->assignRole('user');
        
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }
    
    private function seedDatabase()
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this->seed(\Database\Seeders\SettingsSeeder::class);
    }

    private function getValidTicketData()
    {
        return [
            'subject' => 'Test Ticket Subject',
            'category' => 'technical',
            'priority' => 'medium',
            'message' => 'This is a test message with more than 20 characters.',
        ];
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function testUserCanCreateTicket()
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/user/support-tickets', 
            $this->getValidTicketData()
        );

        $response->assertStatus(201);
        $this->assertDatabaseHas('support_tickets', [
            'user_id' => $this->user->id,
            'subject' => 'Test Ticket Subject'
        ]);
        $this->assertDatabaseHas('support_messages', [
            'message' => 'This is a test message with more than 20 characters.'
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function testUserCanViewOwnTickets()
    {
        SupportTicket::factory()->create(['user_id' => $this->user->id]);
        
        $response = $this->actingAs($this->user)->getJson('/api/v1/user/support-tickets');
        
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function testUserCanReplyToTicket()
    {
        $ticket = SupportTicket::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->postJson("/api/v1/user/support-tickets/{$ticket->id}/reply", [
            'message' => 'This is my reply.'
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('support_messages', [
            'support_ticket_id' => $ticket->id,
            'message' => 'This is my reply.',
            'is_admin_reply' => false
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function testUserCanCloseTicket()
    {
        $ticket = SupportTicket::factory()->create(['user_id' => $this->user->id, 'status' => 'open']);

        $response = $this->actingAs($this->user)->postJson("/api/v1/user/support-tickets/{$ticket->id}/close");
        
        $response->assertStatus(200);
        $this->assertEquals('resolved', $ticket->fresh()->status);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function testUserCannotViewOtherUsersTickets()
    {
        $otherTicket = SupportTicket::factory()->create(['user_id' => $this->otherUser->id]);

        $response = $this->actingAs($this->user)->getJson("/api/v1/user/support-tickets/{$otherTicket->id}");

        $response->assertStatus(403); // Forbidden
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function testUserCanAttachFilesToTicket()
    {
        Storage::fake('public');
        
        $payload = $this->getValidTicketData() + [
            'attachment' => UploadedFile::fake()->create('proof.pdf', 1000)
        ];

        $response = $this->actingAs($this->user)->postJson('/api/v1/user/support-tickets', $payload);

        $response->assertStatus(201);
        $this->assertDatabaseHas('support_messages', [
            'attachments' => '["support\/' . $response->json('id') . '\/proof.pdf"]'
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function testUserReceivesNotificationOnReply()
    {
        Notification::fake();
        $ticket = SupportTicket::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->admin)->postJson("/api/v1/admin/support-tickets/{$ticket->id}/reply", [
            'message' => 'This is an admin reply.'
        ]);

        $response->assertStatus(201);
        Notification::assertSentTo($this->user, SupportReplyNotification::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function testUserCanRateTicketResolution()
    {
        $ticket = SupportTicket::factory()->create(['user_id' => $this->user->id, 'status' => 'resolved']);

        $response = $this->actingAs($this->user)->postJson("/api/v1/user/support-tickets/{$ticket->id}/rate", [
            'rating' => 5,
            'rating_feedback' => 'Great service!'
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('support_tickets', [
            'id' => $ticket->id,
            'rating' => 5,
            'rating_feedback' => 'Great service!'
        ]);
    }
}
