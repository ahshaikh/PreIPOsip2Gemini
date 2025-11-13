<?php
// V-FINAL-1730-231

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    public function send($mobile, $message, $templateId = null)
    {
        $provider = setting('sms_provider', 'log');

        try {
            switch ($provider) {
                case 'msg91':
                    return $this->sendViaMsg91($mobile, $message, $templateId);
                case 'twilio':
                    return $this->sendViaTwilio($mobile, $message);
                case 'log':
                default:
                    Log::info("SMS to {$mobile}: {$message}");
                    return true;
            }
        } catch (\Exception $e) {
            Log::error("SMS Failed ({$provider}): " . $e->getMessage());
            return false;
        }
    }

    private function sendViaMsg91($mobile, $message, $templateId)
    {
        $authKey = setting('msg91_auth_key');
        $senderId = setting('msg91_sender_id');
        
        // MSG91 Flow ID (Template ID) is preferred over raw message
        $payload = [
            'flow_id' => $templateId,
            'sender' => $senderId,
            'mobiles' => '91' . $mobile, // Assuming India
            'message' => $message, // Fallback if flow_id not used
        ];

        $response = Http::withHeaders([
            'authkey' => $authKey,
            'content-type' => 'application/json'
        ])->post('https://api.msg91.com/api/v5/flow/', $payload);

        if ($response->failed()) {
            throw new \Exception($response->body());
        }

        return true;
    }

    private function sendViaTwilio($mobile, $message)
    {
        $sid = setting('twilio_sid');
        $token = setting('twilio_token');
        $from = setting('twilio_from');

        $url = "https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json";

        $response = Http::withBasicAuth($sid, $token)
            ->asForm()
            ->post($url, [
                'To' => '+91' . $mobile,
                'From' => $from,
                'Body' => $message
            ]);

        if ($response->failed()) {
            throw new \Exception($response->body());
        }

        return true;
    }
}