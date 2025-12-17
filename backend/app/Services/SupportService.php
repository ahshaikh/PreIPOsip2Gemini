<?php
// V-FINAL-1730-382 (Created) | V-FIX-MODULE-14-LOAD-BALANCING (Gemini) | V-AUDIT-MODULE13-001 (Event-Driven) | V-AUDIT-MODULE13-002 (Load Balancing)

namespace App\Services;

use App\Models\SupportTicket;
use App\Models\User;
use App\Events\TicketEscalated;
use App\Events\TicketClosed;
use Illuminate\Support\Facades\Log;

class SupportService
{
    // V-AUDIT-MODULE13-001: Removed NotificationService dependency (using events now)
    public function __construct()
    {
        // No dependencies needed - using event-driven notifications
    }

    /**
     * Test: test_auto_assign_ticket_to_available_agent
     * Logic: Find the support agent with the fewest open tickets and assign.
     * FIX: Module 14 - Implemented Load Balancing
     * V-AUDIT-MODULE13-002 (MEDIUM): Include waiting_for_user in agent workload calculation
     */
    public function autoAssignTicket(SupportTicket $ticket): void
    {
        // FIX: Replaced simple 'first()' with load balancing logic.
        // We look for users with 'admin' or 'support' roles.
        // We use 'withCount' on 'assignedTickets' (assuming relationship exists on User model)
        // to find the agent with the minimum workload.

        // V-AUDIT-MODULE13-002: Previous Issue - Only counted 'open' and 'in_progress' tickets,
        // ignoring 'waiting_for_user'. Agents with 50 tickets waiting for replies were treated as "free".
        // Fix: Count all tickets NOT closed, including waiting_for_user status.
        $agent = User::role(['admin', 'support'])
            ->where('status', 'active')
            ->withCount(['assignedTickets' => function ($query) {
                // V-AUDIT-MODULE13-002: Count all non-closed tickets (includes waiting_for_user)
                $query->where('status', '!=', 'closed');
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
     * V-AUDIT-MODULE13-001 (HIGH): Refactored to use event-driven notifications
     */
    public function escalateOverdueTickets(): int
    {
        $overdueTickets = SupportTicket::where('status', 'open')
            ->whereRaw('created_at < NOW() - INTERVAL sla_hours HOUR')
            ->where('priority', '!=', 'high') // Don't escalate what's already high
            ->get();

        foreach ($overdueTickets as $ticket) {
            $ticket->update(['priority' => 'high']);

            // V-AUDIT-MODULE13-001: Dispatch event instead of synchronous notification loop
            // Previous Issue: Looping through admins and sending notifications synchronously
            // caused timeouts with 100 tickets × 5 admins = 500 email/SMS calls.
            // Fix: Dispatch TicketEscalated event. Queued listener handles notifications asynchronously.
            // Benefits: Scheduled task completes in seconds, notifications processed in background.
            event(new TicketEscalated($ticket));

            Log::warning("Ticket #{$ticket->id} breached SLA and was escalated to HIGH priority.");
        }

        return $overdueTickets->count();
    }

    /**
     * Test: test_close_ticket_after_resolution
     * Logic: Finds tickets that were resolved 7+ days ago.
     * V-AUDIT-MODULE13-001 (HIGH): Refactored to use event-driven notifications
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

            // V-AUDIT-MODULE13-001: Dispatch event instead of synchronous notification
            // Previous Issue: Sending notifications synchronously within loop caused timeouts
            // when batch-closing many tickets (e.g., after system downtime).
            // Fix: Dispatch TicketClosed event. Queued listener handles notification asynchronously.
            // Benefits: Fast scheduled task, reliable delivery via queue, automatic retries.
            event(new TicketClosed($ticket));
        }

        return $closableTickets->count();
    }
}