<?php
// V-FINAL-1730-317

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;

class KycVerified extends Notification implements ShouldQueue
{
    use Queueable;

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        return [
            'title' => 'KYC Verified ✅',
            'message' => 'Your identity verification is complete. You can now invest freely.',
            'type' => 'success',
            'link' => '/profile',
        ];
    }
}