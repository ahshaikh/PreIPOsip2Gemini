<?php

namespace App\Events;

use App\Models\SupportTicket;
use App\Models\SupportMessage;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TicketReplied
{
    use Dispatchable, SerializesModels;

    public SupportTicket $ticket;
    public SupportMessage $message;

    public function __construct(SupportTicket $ticket, SupportMessage $message)
    {
        $this->ticket = $ticket;
        $this->message = $message;
    }
}
