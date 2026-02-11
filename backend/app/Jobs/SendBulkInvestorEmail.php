<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use App\Mail\InvestorNewsletter;


class SendBulkInvestorEmail implements ShouldQueue
{
    /**
     * [AUDIT FIX]: Throttled bulk delivery to prevent SMTP blacklisting.
     */
    public function handle()
    {
        User::where('is_subscribed', true)->chunk(100, function ($users) {
            foreach ($users as $user) {
                // Ensure no specific PII like bank accounts are in the template
                Mail::to($user->email)->queue(new InvestorNewsletter($user));
                
                // [AUDIT FIX]: Sleep 1s every 10 emails to satisfy rate limits
                if ($loop->iteration % 10 === 0) sleep(1);
            }
        });
    }
}