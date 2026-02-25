<?php
// V-FINAL-1730-TEST-49 (Created)

namespace Tests\Unit;

use Tests\UnitTestCase;
use App\Models\User;
use App\Models\SupportTicket;
use App\Models\SupportMessage;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class SupportTicketTest extends UnitTestCase
{
    protected $user;
    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_ticket_belongs_to_user()
    {
        $ticket = SupportTicket::factory()->create(['user_id' => $this->user->id]);
        $this->assertInstanceOf(User::class, $ticket->user);
        $this->assertEquals($this->user->id, $ticket->user->id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_ticket_has_messages_relationship()
    {
        $ticket = SupportTicket::factory()->create(['user_id' => $this->user->id]);
        SupportMessage::factory()->create(['support_ticket_id' => $ticket->id]);

        $this->assertTrue($ticket->messages()->exists());
        $this->assertEquals(1, $ticket->messages->count());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_ticket_status_enum_validates()
    {
        $validStatuses = ['open', 'waiting_for_user', 'waiting_for_support', 'resolved'];
        
        $validator = Validator::make(
            ['status' => 'open'], 
            ['status' => 'in:' . implode(',', $validStatuses)]
        );
        $this->assertTrue($validator->passes());
        
        $validator = Validator::make(
            ['status' => 'closed'], // 'closed' is not a valid state, 'resolved' is
            ['status' => 'in:' . implode(',', $validStatuses)]
        );
        $this->assertFalse($validator->passes());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_ticket_priority_enum_validates()
    {
        $validPriorities = ['low', 'medium', 'high'];
        
        $validator = Validator::make(
            ['priority' => 'low'], 
            ['priority' => 'in:' . implode(',', $validPriorities)]
        );
        $this->assertTrue($validator->passes());
        
        $validator = Validator::make(
            ['priority' => 'critical'], // 'critical' is not in our base enum
            ['priority' => 'in:' . implode(',', $validPriorities)]
        );
        $this->assertFalse($validator->passes());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_ticket_tracks_resolved_by_admin()
    {
        $ticket = SupportTicket::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'resolved',
            'resolved_by' => $this->admin->id,
            'resolved_at' => now()
        ]);

        $this->assertInstanceOf(User::class, $ticket->resolvedBy);
        $this->assertEquals($this->admin->id, $ticket->resolvedBy->id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_ticket_auto_closes_after_inactivity()
    {
        // 1. Ticket resolved 8 days ago (Should be found)
        SupportTicket::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'resolved',
            'resolved_at' => now()->subDays(8)
        ]);

        // 2. Ticket resolved 3 days ago (Should NOT be found)
        SupportTicket::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'resolved',
            'resolved_at' => now()->subDays(3)
        ]);

        // 3. Open ticket (Should NOT be found)
        SupportTicket::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'open',
        ]);

        // Use the scope (default 7 days)
        $closableTickets = SupportTicket::AutoClose()->get();

        $this->assertEquals(1, $closableTickets->count());
    }
}
