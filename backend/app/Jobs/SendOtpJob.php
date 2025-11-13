<?php
// V-FINAL-1730-232

namespace App\Jobs;

use App\Models\User;
use App\Models\Otp;
use App\Services\SmsService; // <-- IMPORT
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendOtpJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected User $user,
        protected string $type // 'email' or 'mobile'
    ) {}

    public function handle(SmsService $smsService): void
    {
        $otpCode = rand(100000, 999999);
        
        Otp::create([
            'user_id' => $this->user->id,
            'type' => $this->type,
            'otp_code' => $otpCode,
            'expires_at' => now()->addMinutes(10),
        ]);

        if ($this->type === 'email') {
            // In a real app, create a Mailable for this
            // Mail::to($this->user->email)->send(new OtpMail($otpCode));
            Log::info("EMAIL OTP for {$this->user->email}: {$otpCode}");
        } 
        elseif ($this->type === 'mobile') {
            // --- UPDATED LOGIC ---
            $message = "Your OTP for PreIPO SIP is {$otpCode}. Valid for 10 mins.";
            // Pass a specific DLT template ID if you have one in settings
            $templateId = setting('msg91_otp_template_id', null); 
            
            $smsService->send($this->user->mobile, $message, $templateId);
            // ---------------------
        }
    }
}