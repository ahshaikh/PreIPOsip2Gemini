<?php
// V-FINAL-1730-TEST-51 (Created)

namespace Tests\Unit;

use Tests\UnitTestCase;
use App\Models\User;
use App\Models\SupportTicket;
use App\Models\SupportMessage;
class SupportMessageTest extends UnitTestCase
{
    protected $user;
    protected $admin;
    protected $ticket;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->admin = User::factory()->create();
        $this->ticket = SupportTicket::factory()->create(['user_id' => $this->user->id]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_message_belongs_to_ticket()
    {
        $message = SupportMessage::factory()->create([
            'support_ticket_id' => $this->ticket->id,
            'user_id' => $this->user->id
        ]);

        $this->assertInstanceOf(SupportTicket::class, $message->ticket);
        $this->assertEquals($this->ticket->id, $message->ticket->id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_message_belongs_to_sender()
    {
        $message = SupportMessage::factory()->create([
            'support_ticket_id' => $this->ticket->id,
            'user_id' => $this->user->id
        ]);

        // Using the 'sender' relationship alias
        $this->assertInstanceOf(User::class, $message->sender);
        $this->assertEquals($this->user->id, $message->sender->id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_message_tracks_is_admin_reply()
    {
        // 1. User's message
        $userMessage = SupportMessage::factory()->create([
            'support_ticket_id' => $this->ticket->id,
            'user_id' => $this->user->id,
            'is_admin_reply' => false
        ]);

        // 2. Admin's message
        $adminMessage = SupportMessage::factory()->create([
            'support_ticket_id' => $this->ticket->id,
            'user_id' => $this->admin->id,
            'is_admin_reply' => true
        ]);

        $this->assertFalse($userMessage->is_admin_reply);
        $this->assertTrue($adminMessage->is_admin_reply);
    }
}
