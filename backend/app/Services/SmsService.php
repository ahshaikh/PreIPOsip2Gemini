<?php
// V-FINAL-1730-231 (Created) | V-FINAL-1730-392 (Logging & Preferences Added)

namespace App\Services;

use App\Models\User;
use App\Models\SmsLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    /**
     * Main entry point for sending an SMS.
     */
    public function send(User $user, string $message, ?string $templateSlug = null, ?string $dltTemplateId = null): ?SmsLog
    {
        // 1. Check Preferences
        if (!$this->canSendSms($user, $templateSlug)) {
            Log::info("SMS (User Opt-out): {$user->mobile} for {$templateSlug}");
            return null;
        }
        
        // 2. Check & Fix Length
        if (strlen($message) > 160) {
            $message = substr($message, 0, 157) . '...';
            Log::warning("SMS Warning: Message truncated for {$templateSlug}");
        }

        // 3. Create Log
        $log = SmsLog::create([
            'user_id' => $user->id,
            'recipient_mobile' => $user->mobile,
            'template_slug' => $templateSlug,
            'dlt_template_id' => $dltTemplateId,
            'message' => $message,
            'status' => 'queued',
        ]);

        // 4. Send via Active Provider
        $provider = setting('sms_provider', 'log');
        try {
            $log->update(['status' => 'sending']);
            
            $gateway_message_id = match ($provider) {
                'msg91' => $this->sendViaMsg91($user->mobile, $message, $dltTemplateId),
                // 'twilio' => $this->sendViaTwilio($user->mobile, $message),
                default => $this->sendViaLog($user->mobile, $message)
            };

            $log->update(['status' => 'sent', 'gateway_message_id' => $gateway_message_id]);

        } catch (\Exception $e) {
            $log->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
            Log::error("SMS Failed ({$provider}): " . $e->getMessage());
        }
        
        return $log;
    }

    /**
     * Check user's notification preferences.
     */
    private function canSendSms(User $user, ?string $templateSlug = null): bool
    {
        if (!$templateSlug) return true; // Default to send
        
        // Map slug to preference (e.g., "otp_sms", "bonus_sms")
        $key = explode('.', $templateSlug)[0] . '_sms'; 
        
        $preference = $user->notificationPreferences()
                           ->where('preference_key', $key)
                           ->first();
                           
        // Default to TRUE (opt-out).
        return $preference ? $preference->is_enabled : true;
    }

    private function sendViaMsg91($mobile, $message, $dltTemplateId)
    {
        $response = Http::withHeaders([
            'authkey' => setting('msg91_auth_key'),
            'content-type' => 'application/json'
        ])->post('https://api.msg91.com/api/v5/flow/', [
            'flow_id' => $dltTemplateId,
            'sender' => setting('msg91_sender_id'),
            'mobiles' => '91' . $mobile,
            'message' => $message, // Fallback variable
        ]);

        if ($response->failed()) {
            throw new \Exception($response->body());
        }
        
        return $response->json('message_id') ?? 'msg91_success';
    }
    
    private function sendViaLog($mobile, $message)
    {
        Log::info("[SMS LOG] To: {$mobile} | Message: {$message}");
        return 'log_success';
    }
}