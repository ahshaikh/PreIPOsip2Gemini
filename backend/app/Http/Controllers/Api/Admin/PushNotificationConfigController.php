<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\PushLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class PushNotificationConfigController extends Controller
{
    /**
     * Get push notification configuration
     */
    public function getConfig()
    {
        $config = [
            'provider' => setting('push_provider', 'fcm'),
            'fcm' => [
                'enabled' => setting('push_provider') === 'fcm',
                'server_key_configured' => !empty(setting('fcm_server_key')),
                'sender_id' => setting('fcm_sender_id', ''),
                'project_id' => setting('fcm_project_id', ''),
            ],
            'onesignal' => [
                'enabled' => setting('push_provider') === 'onesignal',
                'app_id_configured' => !empty(setting('onesignal_app_id')),
                'api_key_configured' => !empty(setting('onesignal_api_key')),
            ],
            'settings' => [
                'queue_enabled' => setting('push_queue_enabled', true),
                'default_icon' => setting('push_default_icon', ''),
                'default_badge' => setting('push_default_badge', ''),
                'sound' => setting('push_sound', 'default'),
                'ttl' => setting('push_ttl', 86400),
                'priority' => setting('push_priority', 'high'),
            ],
        ];

        return response()->json($config);
    }

    /**
     * Update push notification configuration
     */
    public function updateConfig(Request $request)
    {
        $validated = $request->validate([
            'provider' => 'required|in:fcm,onesignal',
            'fcm_server_key' => 'nullable|string',
            'fcm_sender_id' => 'nullable|string',
            'fcm_project_id' => 'nullable|string',
            'onesignal_app_id' => 'nullable|string',
            'onesignal_api_key' => 'nullable|string',
            'push_queue_enabled' => 'boolean',
            'push_default_icon' => 'nullable|url',
            'push_sound' => 'nullable|string',
            'push_ttl' => 'nullable|integer|min:0',
            'push_priority' => 'nullable|in:high,normal',
        ]);

        // Save settings to database
        foreach ($validated as $key => $value) {
            setting([$key => $value]);
        }

        return response()->json([
            'message' => 'Push notification configuration updated successfully',
        ]);
    }

    /**
     * Send manual push notification
     */
    public function sendManual(Request $request)
    {
        $validated = $request->validate([
            'target_type' => 'required|in:all,user,users,segment',
            'user_id' => 'required_if:target_type,user|exists:users,id',
            'user_ids' => 'required_if:target_type,users|array',
            'user_ids.*' => 'exists:users,id',
            'title' => 'required|string|max:255',
            'body' => 'required|string|max:500',
            'data' => 'nullable|array',
            'image_url' => 'nullable|url',
            'action_url' => 'nullable|string',
            'priority' => 'nullable|in:high,normal',
            'badge_count' => 'nullable|integer',
        ]);

        $provider = setting('push_provider', 'fcm');
        $recipients = $this->getRecipients($validated);

        if (empty($recipients)) {
            return response()->json([
                'message' => 'No recipients found for the specified target',
            ], 422);
        }

        $sent = 0;
        $failed = 0;
        $errors = [];

        foreach ($recipients as $user) {
            // Get user's device tokens (would need a user_devices table)
            // For now, we'll create a placeholder
            $deviceToken = $this->getUserDeviceToken($user);

            if (!$deviceToken) {
                $failed++;
                continue;
            }

            try {
                $result = $this->sendPushNotification($provider, $deviceToken, [
                    'title' => $validated['title'],
                    'body' => $validated['body'],
                    'data' => $validated['data'] ?? [],
                    'image_url' => $validated['image_url'] ?? null,
                    'action_url' => $validated['action_url'] ?? null,
                    'priority' => $validated['priority'] ?? 'normal',
                    'badge_count' => $validated['badge_count'] ?? null,
                ]);

                if ($result['success']) {
                    $sent++;

                    // Log the push notification
                    PushLog::create([
                        'user_id' => $user->id,
                        'device_token' => $deviceToken,
                        'device_type' => 'unknown',
                        'title' => $validated['title'],
                        'body' => $validated['body'],
                        'data' => $validated['data'] ?? null,
                        'status' => PushLog::STATUS_SENT,
                        'provider' => $provider,
                        'provider_message_id' => $result['message_id'] ?? null,
                        'provider_response' => $result['response'] ?? null,
                        'sent_at' => now(),
                        'priority' => $validated['priority'] ?? 'normal',
                        'image_url' => $validated['image_url'] ?? null,
                        'action_url' => $validated['action_url'] ?? null,
                        'badge_count' => $validated['badge_count'] ?? null,
                    ]);
                } else {
                    $failed++;
                    $errors[] = $result['error'];
                }
            } catch (\Exception $e) {
                $failed++;
                $errors[] = $e->getMessage();
            }
        }

        return response()->json([
            'message' => 'Push notifications sent',
            'sent' => $sent,
            'failed' => $failed,
            'total_recipients' => count($recipients),
            'errors' => array_unique($errors),
        ]);
    }

    /**
     * Test push notification configuration
     */
    public function testConfig(Request $request)
    {
        $validated = $request->validate([
            'provider' => 'required|in:fcm,onesignal',
            'test_token' => 'required|string',
        ]);

        $provider = $validated['provider'];
        $testToken = $validated['test_token'];

        try {
            $result = $this->sendPushNotification($provider, $testToken, [
                'title' => 'Test Notification',
                'body' => 'This is a test notification from ' . config('app.name'),
                'data' => ['test' => true],
            ]);

            if ($result['success']) {
                return response()->json([
                    'message' => 'Test notification sent successfully',
                    'provider' => $provider,
                    'message_id' => $result['message_id'] ?? null,
                ]);
            } else {
                return response()->json([
                    'message' => 'Test notification failed',
                    'error' => $result['error'] ?? 'Unknown error',
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Test notification failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get push notification statistics
     */
    public function statistics(Request $request)
    {
        $days = $request->input('days', 30);
        $startDate = now()->subDays($days);

        $query = PushLog::where('created_at', '>=', $startDate);

        $stats = [
            'period_days' => $days,
            'total' => $query->count(),
            'sent' => $query->clone()->sent()->count(),
            'delivered' => $query->clone()->delivered()->count(),
            'opened' => $query->clone()->opened()->count(),
            'failed' => $query->clone()->failed()->count(),
            'by_device_type' => [
                'ios' => $query->clone()->byDeviceType('ios')->count(),
                'android' => $query->clone()->byDeviceType('android')->count(),
                'web' => $query->clone()->byDeviceType('web')->count(),
            ],
            'by_priority' => [
                'high' => $query->clone()->where('priority', 'high')->count(),
                'normal' => $query->clone()->where('priority', 'normal')->count(),
            ],
        ];

        // Calculate rates
        $stats['rates'] = [
            'delivery_rate' => $stats['sent'] > 0 ? round(($stats['delivered'] / $stats['sent']) * 100, 2) : 0,
            'open_rate' => $stats['delivered'] > 0 ? round(($stats['opened'] / $stats['delivered']) * 100, 2) : 0,
            'failure_rate' => $stats['total'] > 0 ? round(($stats['failed'] / $stats['total']) * 100, 2) : 0,
        ];

        return response()->json($stats);
    }

    /**
     * Helper: Get recipients based on target type
     */
    private function getRecipients($validated)
    {
        switch ($validated['target_type']) {
            case 'all':
                return User::where('status', 'active')->get();

            case 'user':
                return User::where('id', $validated['user_id'])->get();

            case 'users':
                return User::whereIn('id', $validated['user_ids'])->get();

            case 'segment':
                // Implement segment logic here
                return collect([]);

            default:
                return collect([]);
        }
    }

    /**
     * Helper: Get user device token
     * This is a placeholder - you would need a user_devices table
     */
    private function getUserDeviceToken($user)
    {
        // TODO: Implement actual device token retrieval
        // For now, return a placeholder
        return 'placeholder_device_token_' . $user->id;
    }

    /**
     * Helper: Send push notification via provider
     */
    private function sendPushNotification($provider, $deviceToken, $payload)
    {
        if ($provider === 'fcm') {
            return $this->sendViaFcm($deviceToken, $payload);
        } elseif ($provider === 'onesignal') {
            return $this->sendViaOneSignal($deviceToken, $payload);
        }

        return [
            'success' => false,
            'error' => 'Invalid provider',
        ];
    }

    /**
     * Helper: Send via Firebase Cloud Messaging
     */
    private function sendViaFcm($deviceToken, $payload)
    {
        $serverKey = setting('fcm_server_key');

        if (empty($serverKey)) {
            return [
                'success' => false,
                'error' => 'FCM server key not configured',
            ];
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
                    'icon' => $payload['image_url'] ?? setting('push_default_icon'),
                    'sound' => setting('push_sound', 'default'),
                ],
                'data' => $payload['data'] ?? [],
                'priority' => $payload['priority'] ?? 'high',
                'time_to_live' => setting('push_ttl', 86400),
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'message_id' => $data['results'][0]['message_id'] ?? null,
                    'response' => $data,
                ];
            } else {
                return [
                    'success' => false,
                    'error' => $response->body(),
                    'response' => $response->json(),
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Helper: Send via OneSignal
     */
    private function sendViaOneSignal($deviceToken, $payload)
    {
        $appId = setting('onesignal_app_id');
        $apiKey = setting('onesignal_api_key');

        if (empty($appId) || empty($apiKey)) {
            return [
                'success' => false,
                'error' => 'OneSignal not configured',
            ];
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
                'priority' => $payload['priority'] === 'high' ? 10 : 5,
                'ttl' => setting('push_ttl', 86400),
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'message_id' => $data['id'] ?? null,
                    'response' => $data,
                ];
            } else {
                return [
                    'success' => false,
                    'error' => $response->body(),
                    'response' => $response->json(),
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
