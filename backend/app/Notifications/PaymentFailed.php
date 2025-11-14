<?php
// V-FINAL-1730-318

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;

class PaymentFailed extends Notification implements ShouldQueue
{
    use Queueable;

    protected $amount;
    protected $reason;

    public function __construct($amount, $reason)
    {
        $this->amount = $amount;
        $this->reason = $reason;
    }

    public function via($notifiable)
    {
        return ['database']; // We handle email via a separate Job currently, but could merge here.
    }

    public function toArray($notifiable)
    {
        return [
            'title' => 'Payment Failed ❌',
            'message' => "Your payment of ₹{$this->amount} could not be processed. Reason: {$this->reason}",
            'type' => 'error',
            'link' => '/subscription', // Send them to fix it
        ];
    }
}