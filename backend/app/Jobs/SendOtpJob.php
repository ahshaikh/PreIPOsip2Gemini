// V-PHASE1-1730-022
<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\Otp;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendOtpJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected User $user,
        protected string $type // 'email' or 'mobile'
    ) {}

    public function handle(): void
    {
        // In a real app, use a service for this
        $otpCode = rand(100000, 999999);
        
        Otp::create([
            'user_id' => $this->user->id,
            'type' => $this->type,
            'otp_code' => $otpCode,
            'expires_at' => now()->addMinutes(10),
        ]);

        if ($this->type === 'email') {
            // TODO: Integrate Mail::send Mailable
            Log::info("Sending EMAIL OTP to {$this->user->email}: {$otpCode}");
        } elseif ($this->type === 'mobile') {
            // TODO: Integrate MSG91 or Twilio API
            Log::info("Sending SMS OTP to {$this->user->mobile}: {$otpCode}");
        }
    }
}