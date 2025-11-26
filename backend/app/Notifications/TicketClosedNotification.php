<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\SupportTicket;

class TicketClosedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public SupportTicket $ticket
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
                    ->subject("Ticket [{$this->ticket->ticket_code}] has been closed")
                    ->line('Your support ticket has been automatically closed.')
                    ->line('**Ticket Details:**')
                    ->line('Subject: ' . $this->ticket->subject)
                    ->line('Resolved: ' . $this->ticket->resolved_at?->diffForHumans())
                    ->line('Closed: ' . $this->ticket->closed_at?->diffForHumans())
                    ->line('If you need further assistance, please feel free to open a new ticket.')
                    ->action('View Ticket History', url(env('FRONTEND_URL') . '/support/ticket/' . $this->ticket->id));
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => "Ticket Closed [{$this->ticket->ticket_code}]",
            'message' => "Your ticket '{$this->ticket->subject}' has been closed.",
            'link' => '/support/ticket/' . $this->ticket->id,
        ];
    }
}
