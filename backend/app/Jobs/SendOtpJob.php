<?php
// V-PHASE1-1730-022 (Created) | V-FINAL-1730-232 | V-FINAL-1730-324 | V-FINAL-1730-393 (Logging/Prefs)

namespace App\Jobs;

use App\Models\User;
use App\Models\Otp;
use App\Models\SmsTemplate; // <-- Import
use App\Services\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendOtpJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $user;
    public $type; 

    public function __construct(User $user, string $type)
    {
        $this->user = $user;
        $this->type = $type;
    }

    public function handle(SmsService $smsService): void
    {
        $otpCode = rand(100000, 999999);
        
        Otp::create([
            'user_id' => $this->user->id,
            'type' => $this->type,
            'otp_code' => $otpCode,
            'expires_at' => now()->addMinutes(10),
            'last_sent_at' => now(), // From our new migration
        ]);

        if ($this->type === 'email') {
            Log::info("EMAIL OTP for {$this->user->email}: {$otpCode}");
        } 
        elseif ($this->type === 'mobile') {
            // --- UPDATED LOGIC (TESTABLE) ---
            $templateSlug = 'auth.otp';
            $template = SmsTemplate::where('slug', $templateSlug)->first();
            
            $message = $template
                ? str_replace("{{otp_code}}", $otpCode, $template->body)
                : "Your OTP for PreIPO SIP is {$otpCode}.";
            
            $dltId = $template ? $template->dlt_template_id : null;
            
            // Send via the service
            $smsService->send($this->user, $message, $templateSlug, $dltId);
        }
    }
}