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
     *
     * V-AUDIT-MODULE16-HIGH (SCALABILITY): Refactor for FCM Multicast/Batch Messaging
     *
     * Previous Issue:
     * Sent HTTP requests one by one inside loop: 100 users = 100 separate HTTP calls
     * With 200ms latency per call, a chunk took 20 seconds
     * Network bottleneck made push campaigns extremely slow
     *
     * Fix:
     * Use FCM Multicast API to send to up to 500 tokens in a single HTTP request
     * Batch tokens in groups of 500, drastically reducing network overhead
     *
     * Performance Improvement:
     * Before: 100 users = 100 HTTP calls = ~20 seconds
     * After: 100 users = 1 HTTP call = ~200ms
     * Result: 100x faster push delivery
     */
    public function handle()
    {
        $provider = function_exists('setting') ? setting('push_provider', 'fcm') : 'fcm';

        // FIX: Query REAL device tokens from user_devices table
        // Previous: Used placeholder tokens, notifications never reached users
        // Now: Query actual FCM/OneSignal tokens registered by user devices
        $userTokenMap = [];

        // Check if user_devices table exists
        try {
            // Extract user IDs from collection
            $userIds = $this->users->pluck('id')->toArray();

            // Query active device tokens for these users
            $devices = \DB::table('user_devices')
                ->whereIn('user_id', $userIds)
                ->where('is_active', true)
                ->where('provider', $provider)
                ->select('user_id', 'device_token')
                ->get();

            // Build token-to-user mapping
            foreach ($devices as $device) {
                $user = $this->users->firstWhere('id', $device->user_id);
                if ($user) {
                    $userTokenMap[$device->device_token] = $user;
                }
            }
        } catch (\Throwable $e) {
            // FIX: Table doesn't exist - log error with clear instructions
            \Log::error('SendPushCampaignJob FAILED: user_devices table missing', [
                'error' => $e->getMessage(),
                'users_count' => count($this->users),
                'fix' => 'Run migration: php artisan migrate',
                'migration' => 'database/migrations/*_create_user_devices_table.php'
            ]);
            return;
        }

        if (empty($userTokenMap)) {
            \Log::info('SendPushCampaignJob: No active device tokens found for users', [
                'users_count' => count($this->users),
                'provider' => $provider,
                'reason' => 'Users have not registered their devices for push notifications'
            ]);
            return;
        }

        $tokens = array_keys($userTokenMap);

        \Log::info('SendPushCampaignJob: Sending to ' . count($tokens) . ' devices', [
            'provider' => $provider,
            'title' => $this->payload['title']
        ]);

        // V-AUDIT-MODULE16-HIGH: Use provider-specific batch sending
        if ($provider === 'fcm') {
            $this->sendBatchViaFcm($tokens, $userTokenMap);
        } elseif ($provider === 'onesignal') {
            $this->sendBatchViaOneSignal($tokens, $userTokenMap);
        } else {
            \Log::error("SendPushCampaignJob: Invalid provider '{$provider}'");
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

    /**
     * V-AUDIT-MODULE16-HIGH: Batch send via FCM Multicast
     *
     * FCM supports sending to up to 500 tokens in a single request using 'registration_ids'
     * This dramatically reduces network overhead compared to individual requests
     */
    private function sendBatchViaFcm(array $tokens, array $userTokenMap)
    {
        $serverKey = function_exists('setting') ? setting('fcm_server_key') : null;

        if (empty($serverKey)) {
            \Log::error('SendPushCampaignJob: FCM server key not configured');
            return;
        }

        // V-AUDIT-MODULE16-HIGH: Batch tokens in groups of 500 (FCM limit)
        $tokenChunks = array_chunk($tokens, 500);

        foreach ($tokenChunks as $tokenChunk) {
            try {
                $response = Http::withHeaders([
                    'Authorization' => 'key=' . $serverKey,
                    'Content-Type' => 'application/json',
                ])->post('https://fcm.googleapis.com/fcm/send', [
                    'registration_ids' => $tokenChunk, // FCM Multicast: send to multiple tokens
                    'notification' => [
                        'title' => $this->payload['title'],
                        'body' => $this->payload['body'],
                        'icon' => $this->payload['image_url'] ?? (function_exists('setting') ? setting('push_default_icon') : null),
                        'sound' => function_exists('setting') ? setting('push_sound', 'default') : 'default',
                    ],
                    'data' => $this->payload['data'] ?? [],
                    'priority' => ($this->payload['priority'] ?? 'normal') === 'high' ? 'high' : 'normal',
                    'time_to_live' => function_exists('setting') ? setting('push_ttl', 86400) : 86400,
                ]);

                $data = $response->json();

                // V-AUDIT-MODULE16-HIGH: Process results for each token
                // FCM returns results array with success/failure for each token
                if ($response->successful() && isset($data['results'])) {
                    foreach ($tokenChunk as $index => $token) {
                        $result = $data['results'][$index] ?? [];
                        $user = $userTokenMap[$token] ?? null;

                        if (!$user) continue;

                        PushLog::create([
                            'user_id' => $user->id,
                            'device_token' => $token,
                            'device_type' => 'unknown',
                            'title' => $this->payload['title'],
                            'body' => $this->payload['body'],
                            'data' => $this->payload['data'] ?? null,
                            'status' => isset($result['message_id']) ? PushLog::STATUS_SENT : PushLog::STATUS_FAILED,
                            'provider' => 'fcm',
                            'provider_message_id' => $result['message_id'] ?? null,
                            'provider_response' => $result,
                            'sent_at' => now(),
                            'priority' => $this->payload['priority'] ?? 'normal',
                            'image_url' => $this->payload['image_url'] ?? null,
                            'action_url' => $this->payload['action_url'] ?? null,
                            'badge_count' => $this->payload['badge_count'] ?? null,
                        ]);
                    }
                }
            } catch (\Exception $e) {
                \Log::error('SendPushCampaignJob FCM Batch Error: ' . $e->getMessage());
            }
        }
    }

    /**
     * V-AUDIT-MODULE16-HIGH: Batch send via OneSignal
     *
     * OneSignal also supports batch sending to multiple player IDs
     */
    private function sendBatchViaOneSignal(array $tokens, array $userTokenMap)
    {
        $appId = function_exists('setting') ? setting('onesignal_app_id') : null;
        $apiKey = function_exists('setting') ? setting('onesignal_api_key') : null;

        if (empty($appId) || empty($apiKey)) {
            \Log::error('SendPushCampaignJob: OneSignal not configured');
            return;
        }

        // OneSignal can handle large batches, but we'll still chunk for safety
        $tokenChunks = array_chunk($tokens, 500);

        foreach ($tokenChunks as $tokenChunk) {
            try {
                $response = Http::withHeaders([
                    'Authorization' => 'Basic ' . $apiKey,
                    'Content-Type' => 'application/json',
                ])->post('https://onesignal.com/api/v1/notifications', [
                    'app_id' => $appId,
                    'include_player_ids' => $tokenChunk, // Send to multiple player IDs
                    'headings' => ['en' => $this->payload['title']],
                    'contents' => ['en' => $this->payload['body']],
                    'data' => $this->payload['data'] ?? [],
                    'big_picture' => $this->payload['image_url'] ?? null,
                    'url' => $this->payload['action_url'] ?? null,
                    'priority' => ($this->payload['priority'] ?? 'normal') === 'high' ? 10 : 5,
                    'ttl' => function_exists('setting') ? setting('push_ttl', 86400) : 86400,
                ]);

                $data = $response->json();

                // Log success for all tokens in this batch
                if ($response->successful() && !isset($data['errors'])) {
                    foreach ($tokenChunk as $token) {
                        $user = $userTokenMap[$token] ?? null;
                        if (!$user) continue;

                        PushLog::create([
                            'user_id' => $user->id,
                            'device_token' => $token,
                            'device_type' => 'unknown',
                            'title' => $this->payload['title'],
                            'body' => $this->payload['body'],
                            'data' => $this->payload['data'] ?? null,
                            'status' => PushLog::STATUS_SENT,
                            'provider' => 'onesignal',
                            'provider_message_id' => $data['id'] ?? null,
                            'provider_response' => $data,
                            'sent_at' => now(),
                            'priority' => $this->payload['priority'] ?? 'normal',
                            'image_url' => $this->payload['image_url'] ?? null,
                            'action_url' => $this->payload['action_url'] ?? null,
                            'badge_count' => $this->payload['badge_count'] ?? null,
                        ]);
                    }
                }
            } catch (\Exception $e) {
                \Log::error('SendPushCampaignJob OneSignal Batch Error: ' . $e->getMessage());
            }
        }
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