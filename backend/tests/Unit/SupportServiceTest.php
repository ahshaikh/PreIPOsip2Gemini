<?php
// V-FINAL-1730-TEST-52 (Created)

namespace Tests\Unit;

use Tests\UnitTestCase;
use App\Services\SupportService;
use App\Models\User;
use App\Models\SupportTicket;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class SupportServiceTest extends UnitTestCase
{
    use DatabaseTransactions;

    protected $service;
    protected $user;
    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SupportService();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this->seed(\Database\Seeders\SettingsSeeder::class);

        // Remove any existing support agents from seeders to ensure deterministic assignment
        \DB::table('model_has_roles')
            ->where('role_id', \Spatie\Permission\Models\Role::where('name', 'support')->first()->id)
            ->delete();

        $this->user = User::factory()->create();
        $this->admin = User::factory()->create();
        $this->admin->assignRole('support');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_auto_assign_ticket_to_available_agent()
    {
        // 1. Create a ticket
        $ticket = SupportTicket::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'open'
        ]);

        // 2. Manually trigger auto-assign (if observer failed)
        $this->service->autoAssignTicket($ticket);

        // 3. Refresh from DB to see changes
        $ticket->refresh();

        // 4. Check ticket was assigned to SOME support/admin agent (not null)
        // The specific agent depends on load balancing (agent with fewest tickets)
        $this->assertNotNull($ticket->assigned_to);
        $assignedUser = User::find($ticket->assigned_to);
        $this->assertTrue($assignedUser->hasRole(['admin', 'support']));
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
