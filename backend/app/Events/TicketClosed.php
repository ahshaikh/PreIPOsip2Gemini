<?php
// V-AUDIT-MODULE13-001 (HIGH): Event-driven closure to prevent synchronous notification loops
// Created: 2025-12-17 | Dispatched when ticket is auto-closed after resolution

namespace App\Events;

use App\Models\SupportTicket;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * TicketClosed Event
 *
 * V-AUDIT-MODULE13-001 (HIGH): Remove synchronous notification loops in SupportService
 *
 * Previous Issue:
 * The autoCloseOldTickets() method in SupportService sent closure notifications
 * synchronously within a loop. Under high load, this caused scheduled task timeouts.
 *
 * Fix:
 * Dispatch this event when a ticket is auto-closed. Listeners (queued) handle
 * notifications asynchronously, allowing the scheduled task to complete quickly.
 *
 * Benefits:
 * - Fast scheduled task execution
 * - Asynchronous notification delivery
 * - Retry capability for failed notifications
 * - Scalable for batch closures
 *
 * Usage:
 * ```php
 * event(new TicketClosed($ticket));
 * ```
 */
class TicketClosed
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
