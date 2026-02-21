<?php
// V-FINAL-1730-TEST-52 (Created)

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\SupportService;
use App\Models\User;
use App\Models\SupportTicket;
use Carbon\Carbon;

class SupportServiceTest extends TestCase
{
    protected $service;
    protected $user;
    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SupportService();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this->seed(\Database\Seeders\SettingsSeeder::class);

        $this->user = User::factory()->create();
        $this->admin = User::factory()->create();
        $this->admin->assignRole('support');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_auto_assign_ticket_to_available_agent()
    {
        // 1. Create a ticket (Observer will fire)
        $ticket = SupportTicket::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'open'
        ]);
        
        // 2. Refresh from DB to see observer changes
        $ticket->refresh();

        // 3. Check it was assigned to our admin
        $this->assertEquals($this->admin->id, $ticket->assigned_to);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_escalate_ticket_after_sla_breach()
    {
        // 1. Create a ticket 25 hours ago (SLA=24)
        $this->travelTo(now()->subHours(25));
        $ticket = SupportTicket::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'open',
            'priority' => 'medium',
            'sla_hours' => 24
        ]);
        $this->travelBack();

        // 2. Run the service
        $this->service->escalateOverdueTickets();

        // 3. Check priority
        $this->assertEquals('high', $ticket->fresh()->priority);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_close_ticket_after_resolution()
    {
        // 1. Create a ticket resolved 8 days ago
        $this->travelTo(now()->subDays(8));
        $ticket = SupportTicket::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'resolved',
            'resolved_at' => now()
        ]);
        $this->travelBack();

        // 2. Run the service
        $this->service->autoCloseOldTickets();
        
        // 3. Check status
        $this->assertEquals('closed', $ticket->fresh()->status);
        $this->assertNotNull($ticket->fresh()->closed_at);
    }
}