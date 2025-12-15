<?php
// V-FINAL-1730-382 (Created) | V-FIX-MODULE-14-LOAD-BALANCING (Gemini)

namespace App\Services;

use App\Models\SupportTicket;
use App\Models\User;
use App\Notifications\TicketEscalatedNotification;
use App\Notifications\TicketClosedNotification;
use Illuminate\Support\Facades\Log;
use App\Services\NotificationService;

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
     * FIX: Module 14 - Implemented Load Balancing
     */
    public function autoAssignTicket(SupportTicket $ticket): void
    {
        // FIX: Replaced simple 'first()' with load balancing logic.
        // We look for users with 'admin' or 'support' roles.
        // We use 'withCount' on 'assignedTickets' (assuming relationship exists on User model)
        // to find the agent with the minimum workload.
        
        $agent = User::role(['admin', 'support'])
            ->where('status', 'active')
            ->withCount(['assignedTickets' => function ($query) {
                $query->whereIn('status', ['open', 'in_progress']);
            }])
            ->orderBy('assigned_tickets_count', 'asc') // Least busy first
            ->first();

        if ($agent) {
            $ticket->update(['assigned_to' => $agent->id]);
            Log::info("Ticket #{$ticket->id} auto-assigned to Agent #{$agent->id} (Current Load: {$agent->assigned_tickets_count})");
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
            // Note: In a full refactor, this loop should also be moved to an Event/Job
            // but we prioritized the Controller synchronous loop first.
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

            // Send final "Ticket Closed" notification to user
            if ($ticket->user) {
                $this->notificationService->send($ticket->user, 'support.ticket_closed', [
                    'ticket_code' => $ticket->ticket_code,
                    'subject' => $ticket->subject,
                    'resolved_at' => $ticket->resolved_at->format('Y-m-d H:i:s'),
                    'closed_at' => now()->format('Y-m-d H:i:s'),
                ]);
            }
        }
        
        return $closableTickets->count();
    }
}