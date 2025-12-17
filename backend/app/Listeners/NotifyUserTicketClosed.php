<?php
// V-AUDIT-MODULE13-001 (HIGH): Queued listener for ticket closure notifications
// Created: 2025-12-17 | Handles asynchronous notification delivery to users

namespace App\Listeners;

use App\Events\TicketClosed;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * NotifyUserTicketClosed Listener
 *
 * V-AUDIT-MODULE13-001 (HIGH): Queued listener to prevent synchronous notification loops
 *
 * Previous Issue:
 * Closure notifications were sent synchronously within autoCloseOldTickets() loop,
 * causing timeouts when batch-closing many tickets (e.g., after a system downtime).
 *
 * Fix:
 * This queued listener processes closure notifications in the background.
 * Each ticket closure dispatches one job that notifies the user asynchronously.
 *
 * Benefits:
 * - Scheduled task completes quickly
 * - Failed notifications can retry without affecting closure process
 * - System can handle batch closures (100+ tickets)
 * - Notifications delivered reliably via queue
 */
class NotifyUserTicketClosed implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $backoff = 60;

    protected NotificationService $notificationService;

    /**
     * Create the event listener.
     */
    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Handle the event.
     *
     * @param  TicketClosed  $event
     * @return void
     */
    public function handle(TicketClosed $event): void
    {
        $ticket = $event->ticket;

        // V-AUDIT-MODULE13-001: Load ticket user for notification
        $ticket->load('user');

        // V-AUDIT-MODULE13-001: Only notify if user exists
        if (!$ticket->user) {
            Log::warning("Ticket #{$ticket->id} has no associated user for closure notification");
            return;
        }

        try {
            $this->notificationService->send($ticket->user, 'support.ticket_closed', [
                'ticket_code' => $ticket->ticket_code,
                'subject' => $ticket->subject,
                'resolved_at' => $ticket->resolved_at?->format('Y-m-d H:i:s'),
                'closed_at' => $ticket->closed_at?->format('Y-m-d H:i:s'),
            ]);

            Log::info("Closure notification sent to User #{$ticket->user->id} for Ticket #{$ticket->id}");
        } catch (\Exception $e) {
            // V-AUDIT-MODULE13-001: Log failure and let queue retry
            Log::error("Failed to notify user {$ticket->user->id} about closed ticket {$ticket->id}: " . $e->getMessage());
            throw $e; // Re-throw to trigger queue retry
        }
    }

    /**
     * Handle a job failure.
     *
     * @param  TicketClosed  $event
     * @param  \Throwable  $exception
     * @return void
     */
    public function failed(TicketClosed $event, \Throwable $exception): void
    {
        Log::error("Failed to process closure notification for Ticket #{$event->ticket->id}: " . $exception->getMessage());
    }
}
