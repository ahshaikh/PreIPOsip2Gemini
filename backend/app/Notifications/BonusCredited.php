<?php
// V-FINAL-1730-316

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class BonusCredited extends Notification implements ShouldQueue
{
    use Queueable;

    protected $amount;
    protected $type;

    public function __construct($amount, $type)
    {
        $this->amount = $amount;
        $this->type = $type;
    }

    public function via($notifiable)
    {
        return ['database']; // We can add 'mail' here later to unify systems
    }

    public function toArray($notifiable)
    {
        return [
            'title' => 'Bonus Credited! 🎉',
            'message' => "You've earned a ₹{$this->amount} {$this->type} bonus.",
            'type' => 'success',
            'link' => '/bonuses',
            'amount' => $this->amount
        ];
    }
}