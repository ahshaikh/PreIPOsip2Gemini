<?php
// V-AUDIT-MODULE13-001 (HIGH): Queued listener for ticket escalation notifications
// Created: 2025-12-17 | Handles asynchronous notification delivery to admins

namespace App\Listeners;

use App\Events\TicketEscalated;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * NotifyAdminsTicketEscalated Listener
 *
 * V-AUDIT-MODULE13-001 (HIGH): Queued listener to prevent synchronous notification loops
 *
 * Previous Issue:
 * Notifications were sent synchronously in a loop within escalateOverdueTickets(),
 * causing timeouts when processing many tickets (100 tickets × 5 admins = 500 calls).
 *
 * Fix:
 * This queued listener processes notifications in the background. Each ticket escalation
 * dispatches one job that notifies all admins asynchronously.
 *
 * Benefits:
 * - Scheduled task completes in seconds instead of minutes
 * - Failed notifications can retry automatically (via queue)
 * - System remains responsive during mass escalations
 * - Queue workers can be scaled horizontally
 */
class NotifyAdminsTicketEscalated implements ShouldQueue
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
     * @param  TicketEscalated  $event
     * @return void
     */
    public function handle(TicketEscalated $event): void
    {
        $ticket = $event->ticket;

        // V-AUDIT-MODULE13-001: Load ticket user for notification data
        $ticket->load('user');

        // V-AUDIT-MODULE13-001: Notify all admins about SLA breach
        $admins = User::role('admin')->get();

        foreach ($admins as $admin) {
            try {
                $this->notificationService->send($admin, 'support.sla_breach', [
                    'ticket_code' => $ticket->ticket_code,
                    'subject' => $ticket->subject,
                    'priority' => 'high',
                    'sla_hours' => $ticket->sla_hours,
                    'user_name' => $ticket->user->username ?? 'N/A',
                ]);
            } catch (\Exception $e) {
                // V-AUDIT-MODULE13-001: Log individual failures but don't stop processing others
                Log::error("Failed to notify admin {$admin->id} about escalated ticket {$ticket->id}: " . $e->getMessage());
            }
        }

        Log::info("Escalation notifications sent for Ticket #{$ticket->id} to {$admins->count()} admins");
    }

    /**
     * Handle a job failure.
     *
     * @param  TicketEscalated  $event
     * @param  \Throwable  $exception
     * @return void
     */
    public function failed(TicketEscalated $event, \Throwable $exception): void
    {
        Log::error("Failed to process escalation notifications for Ticket #{$event->ticket->id}: " . $exception->getMessage());
    }
}
