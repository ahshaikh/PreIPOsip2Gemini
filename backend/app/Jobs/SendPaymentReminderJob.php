<?php
// V-FINAL-1730-340

namespace App\Jobs;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
// use App\Mail\PaymentReminderMail; // Assumed existing or generic

class SendPaymentReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Subscription $subscription)
    {
    }

    public function handle(): void
    {
        $user = $this->subscription->user;
        
        // Simple log for now, or send actual email
        if ($user && $user->email) {
            // Mail::to($user->email)->send(new PaymentReminderMail($this->subscription));
            // Log for verification
            logger("Reminder sent to {$user->email} for Sub #{$this->subscription->id}");
        }
    }
}