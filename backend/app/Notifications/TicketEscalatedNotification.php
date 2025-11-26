<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\SupportTicket;

class TicketEscalatedNotification extends Notification implements ShouldQueue
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
                    ->subject("[ESCALATED] Ticket [{$this->ticket->ticket_code}] requires attention")
                    ->line('A support ticket has been escalated to HIGH priority due to SLA breach.')
                    ->line('**Ticket Details:**')
                    ->line('Subject: ' . $this->ticket->subject)
                    ->line('Customer: ' . $this->ticket->user->name)
                    ->line('Created: ' . $this->ticket->created_at->diffForHumans())
                    ->action('View Ticket', url(env('FRONTEND_URL') . '/admin/support/ticket/' . $this->ticket->id))
                    ->line('Please address this ticket as soon as possible.');
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => "Ticket Escalated [{$this->ticket->ticket_code}]",
            'message' => "SLA breach: {$this->ticket->subject}",
            'link' => '/admin/support/ticket/' . $this->ticket->id,
            'priority' => 'high',
        ];
    }
}
