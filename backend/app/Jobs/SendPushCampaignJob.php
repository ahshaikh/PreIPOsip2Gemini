<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\PushLog;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class SendPushCampaignJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $users;
    protected $payload;

    /**
     * Create a new job instance.
     *
     * @param \Illuminate\Support\Collection|array $users
     * @param array $payload
     */
    public function __construct($users, array $payload)
    {
        $this->users = $users;
        $this->payload = $payload;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        $provider = function_exists('setting') ? setting('push_provider', 'fcm') : 'fcm';

        foreach ($this->users as $user) {
            // Placeholder: Retrieve actual device token (e.g. from user_devices table)
            // In production, loop through all active tokens for the user
            $deviceToken = 'placeholder_device_token_' . $user->id; 

            if (!$deviceToken) continue;

            try {
                $result = $this->sendPushNotification($provider, $deviceToken, [
                    'title' => $this->payload['title'],
                    'body' => $this->payload['body'],
                    'data' => $this->payload['data'] ?? [],
                    'image_url' => $this->payload['image_url'] ?? null,
                    'action_url' => $this->payload['action_url'] ?? null,
                    'priority' => $this->payload['priority'] ?? 'normal',
                    'badge_count' => $this->payload['badge_count'] ?? null,
                ]);

                // Log the result
                PushLog::create([
                    'user_id' => $user->id,
                    'device_token' => $deviceToken,
                    'device_type' => 'unknown', // Could detect based on token pattern
                    'title' => $this->payload['title'],
                    'body' => $this->payload['body'],
                    'data' => $this->payload['data'] ?? null,
                    'status' => $result['success'] ? PushLog::STATUS_SENT : PushLog::STATUS_FAILED,
                    'provider' => $provider,
                    'provider_message_id' => $result['message_id'] ?? null,
                    'provider_response' => $result['response'] ?? null,
                    'sent_at' => now(),
                    'priority' => $this->payload['priority'] ?? 'normal',
                    'image_url' => $this->payload['image_url'] ?? null,
                    'action_url' => $this->payload['action_url'] ?? null,
                    'badge_count' => $this->payload['badge_count'] ?? null,
                ]);

            } catch (\Exception $e) {
                // Log failure locally or to Sentry, don't fail the whole job
                // Ideally create a failed PushLog entry here too
            }
        }
    }

    /**
     * Public Helper: Send push notification via provider
     * (Made public so controllers can use it for single/test sends)
     */
    public function sendPushNotification($provider, $deviceToken, $payload)
    {
        if ($provider === 'fcm') {
            return $this->sendViaFcm($deviceToken, $payload);
        } elseif ($provider === 'onesignal') {
            return $this->sendViaOneSignal($deviceToken, $payload);
        }

        return ['success' => false, 'error' => 'Invalid provider'];
    }

    private function sendViaFcm($deviceToken, $payload)
    {
        $serverKey = function_exists('setting') ? setting('fcm_server_key') : null;

        if (empty($serverKey)) {
            return ['success' => false, 'error' => 'FCM server key not configured'];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'key=' . $serverKey,
                'Content-Type' => 'application/json',
            ])->post('https://fcm.googleapis.com/fcm/send', [
                'to' => $deviceToken,
                'notification' => [
                    'title' => $payload['title'],
                    'body' => $payload['body'],
                    'icon' => $payload['image_url'] ?? (function_exists('setting') ? setting('push_default_icon') : null),
                    'sound' => function_exists('setting') ? setting('push_sound', 'default') : 'default',
                ],
                'data' => $payload['data'] ?? [],
                'priority' => $payload['priority'] ?? 'high',
                'time_to_live' => function_exists('setting') ? setting('push_ttl', 86400) : 86400,
            ]);

            $data = $response->json();
            return [
                'success' => $response->successful() && !isset($data['failure']) && !isset($data['error']),
                'message_id' => $data['results'][0]['message_id'] ?? null,
                'response' => $data,
                'error' => $response->successful() ? null : $response->body(),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function sendViaOneSignal($deviceToken, $payload)
    {
        $appId = function_exists('setting') ? setting('onesignal_app_id') : null;
        $apiKey = function_exists('setting') ? setting('onesignal_api_key') : null;

        if (empty($appId) || empty($apiKey)) {
            return ['success' => false, 'error' => 'OneSignal not configured'];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->post('https://onesignal.com/api/v1/notifications', [
                'app_id' => $appId,
                'include_player_ids' => [$deviceToken],
                'headings' => ['en' => $payload['title']],
                'contents' => ['en' => $payload['body']],
                'data' => $payload['data'] ?? [],
                'big_picture' => $payload['image_url'] ?? null,
                'url' => $payload['action_url'] ?? null,
                'priority' => ($payload['priority'] ?? 'normal') === 'high' ? 10 : 5,
                'ttl' => function_exists('setting') ? setting('push_ttl', 86400) : 86400,
            ]);

            $data = $response->json();
            return [
                'success' => $response->successful() && !isset($data['errors']),
                'message_id' => $data['id'] ?? null,
                'response' => $data,
                'error' => $response->successful() ? null : $response->body(),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}