<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailTemplate;
use App\Models\SmsTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Http;

class NotificationTestingController extends Controller
{
    /**
     * Test email configuration and send test email
     */
    public function testEmail(Request $request)
    {
        $validated = $request->validate([
            'to_email' => 'required|email',
            'template_id' => 'nullable|exists:email_templates,id',
            'subject' => 'required_without:template_id|string',
            'body' => 'required_without:template_id|string',
            'sample_data' => 'nullable|array',
        ]);

        try {
            $subject = $validated['subject'] ?? 'Test Email';
            $body = $validated['body'] ?? '<p>This is a test email from ' . config('app.name') . '</p>';

            // If template is specified, use it
            if (isset($validated['template_id'])) {
                $template = EmailTemplate::find($validated['template_id']);
                $sampleData = $validated['sample_data'] ?? $this->getDefaultEmailSampleData();

                $subject = $this->replaceVariables($template->subject, $sampleData);
                $body = $this->replaceVariables($template->body, $sampleData);
            }

            Mail::html($body, function ($message) use ($validated, $subject) {
                $message->to($validated['to_email'])
                    ->subject('[TEST] ' . $subject);
            });

            return response()->json([
                'success' => true,
                'message' => 'Test email sent successfully',
                'to' => $validated['to_email'],
                'subject' => $subject,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send test email',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Test SMTP connection
     */
    public function testSmtpConnection(Request $request)
    {
        $validated = $request->validate([
            'host' => 'required|string',
            'port' => 'required|integer',
            'encryption' => 'required|in:tls,ssl,none',
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        try {
            // Temporarily override mail configuration
            config([
                'mail.mailers.smtp.host' => $validated['host'],
                'mail.mailers.smtp.port' => $validated['port'],
                'mail.mailers.smtp.encryption' => $validated['encryption'] === 'none' ? null : $validated['encryption'],
                'mail.mailers.smtp.username' => $validated['username'],
                'mail.mailers.smtp.password' => $validated['password'],
            ]);

            // Attempt to send a test connection
            $transport = app('mailer')->getSwiftMailer()->getTransport();

            if (method_exists($transport, 'start')) {
                $transport->start();
            }

            return response()->json([
                'success' => true,
                'message' => 'SMTP connection successful',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'SMTP connection failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Test SMS configuration and send test SMS
     */
    public function testSms(Request $request)
    {
        $validated = $request->validate([
            'to_mobile' => 'required|string|regex:/^[0-9]{10,15}$/',
            'template_id' => 'nullable|exists:sms_templates,id',
            'message' => 'required_without:template_id|string|max:160',
            'sample_data' => 'nullable|array',
        ]);

        try {
            $message = $validated['message'] ?? 'This is a test SMS from ' . config('app.name');

            // If template is specified, use it
            if (isset($validated['template_id'])) {
                $template = SmsTemplate::find($validated['template_id']);
                $sampleData = $validated['sample_data'] ?? $this->getDefaultSmsSampleData();

                $message = $this->replaceVariables($template->body, $sampleData);
            }

            $provider = setting('sms_provider', 'log');

            if ($provider === 'log') {
                // Just log it
                return response()->json([
                    'success' => true,
                    'message' => 'SMS logged successfully (log mode)',
                    'to' => $validated['to_mobile'],
                    'body' => '[TEST] ' . $message,
                    'provider' => 'log',
                ]);
            }

            // Send via actual provider
            $result = $this->sendSmsViaProvider($provider, $validated['to_mobile'], $message);

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send test SMS',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Test SMS provider connection
     */
    public function testSmsProvider(Request $request)
    {
        $validated = $request->validate([
            'provider' => 'required|in:msg91,twilio',
        ]);

        $provider = $validated['provider'];

        try {
            if ($provider === 'msg91') {
                return $this->testMsg91Connection();
            } elseif ($provider === 'twilio') {
                return $this->testTwilioConnection();
            }

            return response()->json([
                'success' => false,
                'message' => 'Invalid provider',
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Provider connection test failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Test push notification
     */
    public function testPush(Request $request)
    {
        $validated = $request->validate([
            'device_token' => 'required|string',
            'device_type' => 'nullable|in:ios,android,web',
            'title' => 'required|string',
            'body' => 'required|string',
        ]);

        try {
            $provider = setting('push_provider', 'fcm');

            $result = $this->sendPushViaProvider($provider, $validated['device_token'], [
                'title' => '[TEST] ' . $validated['title'],
                'body' => $validated['body'],
                'data' => ['test' => true],
            ]);

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send test push notification',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Test all notification channels
     */
    public function testAll(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'mobile' => 'required|string|regex:/^[0-9]{10,15}$/',
            'device_token' => 'nullable|string',
        ]);

        $results = [
            'email' => ['tested' => false],
            'sms' => ['tested' => false],
            'push' => ['tested' => false],
        ];

        // Test Email
        try {
            Mail::html('<p>This is a test email from the notification testing tool.</p>', function ($message) use ($validated) {
                $message->to($validated['email'])
                    ->subject('[TEST] All Channels Test - Email');
            });

            $results['email'] = [
                'tested' => true,
                'success' => true,
                'message' => 'Email sent successfully',
            ];
        } catch (\Exception $e) {
            $results['email'] = [
                'tested' => true,
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }

        // Test SMS
        try {
            $provider = setting('sms_provider', 'log');

            if ($provider === 'log') {
                $results['sms'] = [
                    'tested' => true,
                    'success' => true,
                    'message' => 'SMS logged (log mode)',
                ];
            } else {
                $smsResult = $this->sendSmsViaProvider($provider, $validated['mobile'], 'Test SMS from notification testing tool');
                $results['sms'] = array_merge(['tested' => true], $smsResult);
            }
        } catch (\Exception $e) {
            $results['sms'] = [
                'tested' => true,
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }

        // Test Push (if token provided)
        if (!empty($validated['device_token'])) {
            try {
                $pushProvider = setting('push_provider', 'fcm');
                $pushResult = $this->sendPushViaProvider($pushProvider, $validated['device_token'], [
                    'title' => '[TEST] All Channels Test',
                    'body' => 'This is a test push notification',
                    'data' => ['test' => true],
                ]);

                $results['push'] = array_merge(['tested' => true], $pushResult);
            } catch (\Exception $e) {
                $results['push'] = [
                    'tested' => true,
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'message' => 'Channel testing completed',
            'results' => $results,
        ]);
    }

    /**
     * Get notification system health check
     */
    public function healthCheck()
    {
        $health = [
            'email' => $this->checkEmailHealth(),
            'sms' => $this->checkSmsHealth(),
            'push' => $this->checkPushHealth(),
            'overall_status' => 'unknown',
        ];

        $allHealthy = $health['email']['status'] === 'healthy'
            && $health['sms']['status'] === 'healthy'
            && $health['push']['status'] === 'healthy';

        $health['overall_status'] = $allHealthy ? 'healthy' : 'degraded';

        return response()->json($health);
    }

    /**
     * Helper: Check email health
     */
    private function checkEmailHealth()
    {
        $provider = setting('email_provider', 'smtp');
        $configured = false;

        if ($provider === 'smtp') {
            $configured = !empty(setting('email_host')) && !empty(setting('email_port'));
        } elseif ($provider === 'sendgrid') {
            $configured = !empty(setting('sendgrid_api_key'));
        }

        return [
            'status' => $configured ? 'healthy' : 'unconfigured',
            'provider' => $provider,
            'configured' => $configured,
        ];
    }

    /**
     * Helper: Check SMS health
     */
    private function checkSmsHealth()
    {
        $provider = setting('sms_provider', 'log');
        $configured = false;

        if ($provider === 'msg91') {
            $configured = !empty(setting('msg91_auth_key'));
        } elseif ($provider === 'twilio') {
            $configured = !empty(setting('twilio_sid')) && !empty(setting('twilio_token'));
        } elseif ($provider === 'log') {
            $configured = true; // Log mode is always configured
        }

        return [
            'status' => $configured ? 'healthy' : 'unconfigured',
            'provider' => $provider,
            'configured' => $configured,
        ];
    }

    /**
     * Helper: Check push health
     */
    private function checkPushHealth()
    {
        $provider = setting('push_provider', 'fcm');
        $configured = false;

        if ($provider === 'fcm') {
            $configured = !empty(setting('fcm_server_key'));
        } elseif ($provider === 'onesignal') {
            $configured = !empty(setting('onesignal_app_id')) && !empty(setting('onesignal_api_key'));
        }

        return [
            'status' => $configured ? 'healthy' : 'unconfigured',
            'provider' => $provider,
            'configured' => $configured,
        ];
    }

    /**
     * Helper: Send SMS via provider
     */
    private function sendSmsViaProvider($provider, $mobile, $message)
    {
        if ($provider === 'msg91') {
            return $this->sendViaMsg91($mobile, $message);
        } elseif ($provider === 'twilio') {
            return $this->sendViaTwilio($mobile, $message);
        }

        return [
            'success' => false,
            'message' => 'Provider not implemented: ' . $provider,
        ];
    }

    /**
     * Helper: Send via MSG91
     */
    private function sendViaMsg91($mobile, $message)
    {
        $authKey = setting('msg91_auth_key');
        $senderId = setting('msg91_sender_id');

        if (empty($authKey)) {
            return ['success' => false, 'message' => 'MSG91 not configured'];
        }

        $response = Http::get('https://api.msg91.com/api/sendhttp.php', [
            'authkey' => $authKey,
            'mobiles' => $mobile,
            'message' => $message,
            'sender' => $senderId,
            'route' => '4',
        ]);

        if ($response->successful()) {
            return [
                'success' => true,
                'message' => 'SMS sent via MSG91',
                'response' => $response->body(),
            ];
        }

        return [
            'success' => false,
            'message' => 'MSG91 request failed',
            'error' => $response->body(),
        ];
    }

    /**
     * Helper: Send via Twilio
     */
    private function sendViaTwilio($mobile, $message)
    {
        $sid = setting('twilio_sid');
        $token = setting('twilio_token');
        $from = setting('twilio_from_number');

        if (empty($sid) || empty($token)) {
            return ['success' => false, 'message' => 'Twilio not configured'];
        }

        $response = Http::withBasicAuth($sid, $token)
            ->asForm()
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", [
                'From' => $from,
                'To' => $mobile,
                'Body' => $message,
            ]);

        if ($response->successful()) {
            return [
                'success' => true,
                'message' => 'SMS sent via Twilio',
                'response' => $response->json(),
            ];
        }

        return [
            'success' => false,
            'message' => 'Twilio request failed',
            'error' => $response->json(),
        ];
    }

    /**
     * Helper: Test MSG91 connection
     */
    private function testMsg91Connection()
    {
        $authKey = setting('msg91_auth_key');

        if (empty($authKey)) {
            return response()->json([
                'success' => false,
                'message' => 'MSG91 auth key not configured',
            ], 422);
        }

        // Test balance API
        $response = Http::get('https://api.msg91.com/api/balance.php', [
            'authkey' => $authKey,
        ]);

        if ($response->successful()) {
            return response()->json([
                'success' => true,
                'message' => 'MSG91 connection successful',
                'balance' => $response->body(),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'MSG91 connection failed',
            'error' => $response->body(),
        ], 500);
    }

    /**
     * Helper: Test Twilio connection
     */
    private function testTwilioConnection()
    {
        $sid = setting('twilio_sid');
        $token = setting('twilio_token');

        if (empty($sid) || empty($token)) {
            return response()->json([
                'success' => false,
                'message' => 'Twilio credentials not configured',
            ], 422);
        }

        // Test account API
        $response = Http::withBasicAuth($sid, $token)
            ->get("https://api.twilio.com/2010-04-01/Accounts/{$sid}.json");

        if ($response->successful()) {
            $data = $response->json();
            return response()->json([
                'success' => true,
                'message' => 'Twilio connection successful',
                'account' => [
                    'friendly_name' => $data['friendly_name'] ?? 'N/A',
                    'status' => $data['status'] ?? 'N/A',
                ],
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Twilio connection failed',
            'error' => $response->json(),
        ], 500);
    }

    /**
     * Helper: Send push via provider
     */
    private function sendPushViaProvider($provider, $deviceToken, $payload)
    {
        if ($provider === 'fcm') {
            return $this->sendViaFcm($deviceToken, $payload);
        } elseif ($provider === 'onesignal') {
            return $this->sendViaOneSignal($deviceToken, $payload);
        }

        return [
            'success' => false,
            'message' => 'Provider not implemented: ' . $provider,
        ];
    }

    /**
     * Helper: Send via FCM
     */
    private function sendViaFcm($deviceToken, $payload)
    {
        $serverKey = setting('fcm_server_key');

        if (empty($serverKey)) {
            return ['success' => false, 'message' => 'FCM server key not configured'];
        }

        $response = Http::withHeaders([
            'Authorization' => 'key=' . $serverKey,
            'Content-Type' => 'application/json',
        ])->post('https://fcm.googleapis.com/fcm/send', [
            'to' => $deviceToken,
            'notification' => [
                'title' => $payload['title'],
                'body' => $payload['body'],
                'sound' => 'default',
            ],
            'data' => $payload['data'] ?? [],
        ]);

        if ($response->successful()) {
            return [
                'success' => true,
                'message' => 'Push notification sent via FCM',
                'response' => $response->json(),
            ];
        }

        return [
            'success' => false,
            'message' => 'FCM request failed',
            'error' => $response->json(),
        ];
    }

    /**
     * Helper: Send via OneSignal
     */
    private function sendViaOneSignal($deviceToken, $payload)
    {
        $appId = setting('onesignal_app_id');
        $apiKey = setting('onesignal_api_key');

        if (empty($appId) || empty($apiKey)) {
            return ['success' => false, 'message' => 'OneSignal not configured'];
        }

        $response = Http::withHeaders([
            'Authorization' => 'Basic ' . $apiKey,
            'Content-Type' => 'application/json',
        ])->post('https://onesignal.com/api/v1/notifications', [
            'app_id' => $appId,
            'include_player_ids' => [$deviceToken],
            'headings' => ['en' => $payload['title']],
            'contents' => ['en' => $payload['body']],
            'data' => $payload['data'] ?? [],
        ]);

        if ($response->successful()) {
            return [
                'success' => true,
                'message' => 'Push notification sent via OneSignal',
                'response' => $response->json(),
            ];
        }

        return [
            'success' => false,
            'message' => 'OneSignal request failed',
            'error' => $response->json(),
        ];
    }

    /**
     * Helper: Replace variables in text
     */
    private function replaceVariables($text, $data)
    {
        foreach ($data as $key => $value) {
            $text = str_replace('{{' . $key . '}}', $value, $text);
        }
        return $text;
    }

    /**
     * Helper: Get default email sample data
     */
    private function getDefaultEmailSampleData()
    {
        return [
            'user_name' => 'Test User',
            'app_name' => config('app.name', 'PreIPOSip'),
            'app_url' => config('app.url', 'https://preiposip.com'),
            'year' => date('Y'),
        ];
    }

    /**
     * Helper: Get default SMS sample data
     */
    private function getDefaultSmsSampleData()
    {
        return [
            'user_name' => 'Test User',
            'app_name' => 'PreIPOSip',
            'otp' => '123456',
        ];
    }
}
