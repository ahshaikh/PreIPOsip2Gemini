<?php
// V-FINAL-1730-383 (Created)

namespace App\Observers;

use App\Models\SupportTicket;
use App\Services\SupportService;

class SupportTicketObserver
{
    protected $service;
    public function __construct(SupportService $service)
    {
        $this->service = $service;
    }

    /**
     * Handle the SupportTicket "created" event.
     */
    public function created(SupportTicket $ticket): void
    {
        if (setting('support_auto_assign', true)) {
            $this->service->autoAssignTicket($ticket);
        }
    }
}