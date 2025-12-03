<?php
// V-FINAL-1730-382 (Created)

namespace App\Services;

use App\Models\SupportTicket;
use App\Models\User;
use App\Notifications\TicketEscalatedNotification;
use App\Notifications\TicketClosedNotification;
use Illuminate\Support\Facades\Log;
<<<<<<< HEAD
use Illuminate\Support\Facades\Notification;
=======
use App\Services\NotificationService;
>>>>>>> 5a046271830c8a9f8526dde5fea7b414a73819b6

class SupportService
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Test: test_auto_assign_ticket_to_available_agent
     * Logic: Find the support agent with the fewest open tickets and assign.
     */
    public function autoAssignTicket(SupportTicket $ticket): void
    {
        // Find an active admin/support agent
        // In V2, this logic would be "least busy". For V1, we find the first.
        $agent = User::role(['admin', 'support'])
            ->where('status', 'active')
            ->first();

        if ($agent) {
            $ticket->update(['assigned_to' => $agent->id]);
            Log::info("Ticket #{$ticket->id} auto-assigned to Agent #{$agent->id}");
        } else {
            Log::warning("No active support agents found to assign Ticket #{$ticket->id}");
        }
    }

    /**
     * Test: test_escalate_ticket_after_sla_breach
     * Logic: Finds all open tickets past their SLA and flags them.
     */
    public function escalateOverdueTickets(): int
    {
        $overdueTickets = SupportTicket::where('status', 'open')
            ->whereRaw('created_at < NOW() - INTERVAL sla_hours HOUR')
            ->where('priority', '!=', 'high') // Don't escalate what's already high
            ->get();

        foreach ($overdueTickets as $ticket) {
            $ticket->update(['priority' => 'high']);

            // Dispatch notification to admin team
            $admins = User::role('admin')->get();
            foreach ($admins as $admin) {
                $this->notificationService->send($admin, 'support.sla_breach', [
                    'ticket_code' => $ticket->ticket_code,
                    'subject' => $ticket->subject,
                    'priority' => 'high',
                    'sla_hours' => $ticket->sla_hours,
                    'user_name' => $ticket->user->username ?? 'N/A',
                ]);
            }
            Log::warning("Ticket #{$ticket->id} breached SLA and was escalated to HIGH priority.");
        }
        
        return $overdueTickets->count();
    }

    /**
     * Test: test_close_ticket_after_resolution
     * Logic: Finds tickets that were resolved 7+ days ago.
     */
    public function autoCloseOldTickets(): int
    {
        // FSD-SUPPORT-005: Auto-close 7 days after 'resolved'
        $closableTickets = SupportTicket::where('status', 'resolved')
            ->where('resolved_at', '<=', now()->subDays(7))
            ->get();
            
        foreach ($closableTickets as $ticket) {
            $ticket->update([
                'status' => 'closed',
                'closed_at' => now()
            ]);

<<<<<<< HEAD
            // Notify the user that their ticket has been closed
            $ticket->user->notify(new TicketClosedNotification($ticket));
=======
            // Send final "Ticket Closed" notification to user
            if ($ticket->user) {
                $this->notificationService->send($ticket->user, 'support.ticket_closed', [
                    'ticket_code' => $ticket->ticket_code,
                    'subject' => $ticket->subject,
                    'resolved_at' => $ticket->resolved_at->format('Y-m-d H:i:s'),
                    'closed_at' => now()->format('Y-m-d H:i:s'),
                ]);
            }
>>>>>>> 5a046271830c8a9f8526dde5fea7b414a73819b6
        }
        
        return $closableTickets->count();
    }
}