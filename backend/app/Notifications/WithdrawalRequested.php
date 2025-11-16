<?php
// V-FINAL-1730-581 (Created)

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Withdrawal;

class WithdrawalRequested extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Withdrawal $withdrawal)
    {
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
                    ->subject('Withdrawal Request Received')
                    ->line('We have received your withdrawal request for ₹' . $this.withdrawal->amount . '.')
                    ->line('It is now pending approval and processing. You will be notified once it is complete.')
                    ->action('View Status', url(env('FRONTEND_URL') . '/wallet'));
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => 'Withdrawal Request Received',
            'message' => 'Your request for ₹' . $this.withdrawal->amount . ' is pending approval.',
            'link' => '/wallet',
        ];
    }
}