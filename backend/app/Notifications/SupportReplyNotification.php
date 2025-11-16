<?php
// V-FINAL-1730-593 (Created)

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\SupportTicket;
use App\Models\SupportMessage;

class SupportReplyNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public SupportTicket $ticket,
        public SupportMessage $message
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
                    ->subject("Re: Ticket [{$this->ticket->ticket_code}] {$this->ticket->subject}")
                    ->line('A support agent has replied to your ticket.')
                    ->line('Message: ' . $this->message->message)
                    ->action('View Ticket', url(env('FRONTEND_URL') . '/support/ticket/' . $this->ticket->id));
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => "New reply on Ticket [{$this->ticket->ticket_code}]",
            'message' => Str::limit($this->message->message, 100),
            'link' => '/support/ticket/' . $this->ticket->id,
        ];
    }
}