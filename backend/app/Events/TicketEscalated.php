<?php
// V-AUDIT-MODULE13-001 (HIGH): Event-driven escalation to prevent synchronous notification loops
// Created: 2025-12-17 | Dispatched when SLA breach occurs

namespace App\Events;

use App\Models\SupportTicket;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * TicketEscalated Event
 *
 * V-AUDIT-MODULE13-001 (HIGH): Remove synchronous notification loops in SupportService
 *
 * Previous Issue:
 * The escalateOverdueTickets() method in SupportService looped through overdue tickets
 * and sent notifications synchronously to all admins. With 100 tickets and 5 admins,
 * this caused 500 synchronous email/SMS calls, leading to timeouts and worker starvation.
 *
 * Fix:
 * Dispatch this event when a ticket breaches SLA. Listeners (queued) handle notifications
 * asynchronously, preventing scheduled task timeouts and improving scalability.
 *
 * Benefits:
 * - Scheduled tasks complete quickly without blocking
 * - Notifications processed in background queue
 * - System can handle mass escalations (100+ tickets)
 * - Failed notifications can retry without affecting SLA checks
 *
 * Usage:
 * ```php
 * event(new TicketEscalated($ticket));
 * ```
 */
class TicketEscalated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public SupportTicket $ticket;

    /**
     * Create a new event instance.
     *
     * @param  SupportTicket  $ticket
     * @return void
     */
    public function __construct(SupportTicket $ticket)
    {
        $this->ticket = $ticket;
    }
}
