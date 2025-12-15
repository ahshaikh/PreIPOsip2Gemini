<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\PushLog;
use App\Models\User;
use App\Jobs\SendPushCampaignJob; // FIX: Use the Job
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Bus; // FIX: Use Batching

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
     * FIX: Module 16 - Fix Bulk Push Timeout (High)
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

        // Retrieve users query builder
        $query = $this->getRecipientsQuery($validated);

        if ($query->count() === 0) {
            return response()->json([
                'message' => 'No recipients found for the specified target',
            ], 422);
        }

        // FIX: Use Job Batching instead of foreach loop to prevent timeout
        $batch = [];
        
        // Chunk users into groups of 100
        $query->chunkById(100, function ($users) use ($validated, &$batch) {
            $batch[] = new SendPushCampaignJob($users, $validated);
        });

        // Dispatch batch
        if (!empty($batch)) {
            Bus::batch($batch)
                ->name('Push Manual: ' . $validated['title'])
                ->onQueue('notifications')
                ->dispatch();
        }

        return response()->json([
            'message' => 'Push notifications queued for processing',
            'total_recipients_approx' => $query->count(),
        ]);
    }

    /**
     * Test push notification configuration
     */
    public function testConfig(Request $request)
    {
        // Keep this synchronous for immediate feedback during config test
        $validated = $request->validate([
            'provider' => 'required|in:fcm,onesignal',
            'test_token' => 'required|string',
        ]);

        $provider = $validated['provider'];
        $testToken = $validated['test_token'];

        // Instantiate a temporary job just to use its logic methods, or refactor helper methods to a Service
        // For now, simpler to replicate the single send logic or instantiate the job logic.
        // Let's manually call the helper logic (which we should move to a service ideally).
        // Since we refactored the bulk send to a job, we need to bring the low-level send logic back here or into a Trait/Service.
        // To avoid code duplication, assume we move sendPushNotification to a static helper or Service.
        // For this fix, I will implement a basic version here for testing.
        
        try {
            // Using a job instance method (public static) would be best, but let's implement inline for the fix request
            $job = new SendPushCampaignJob(collect([]), []); 
            $result = $job->sendPushNotification($provider, $testToken, [
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
     * Helper: Get recipients query based on target type
     * Changed to return Builder instead of Collection for chunking
     */
    private function getRecipientsQuery($validated)
    {
        switch ($validated['target_type']) {
            case 'all':
                return User::where('status', 'active');

            case 'user':
                return User::where('id', $validated['user_id']);

            case 'users':
                return User::whereIn('id', $validated['user_ids']);

            case 'segment':
                // Placeholder for segment logic
                return User::whereRaw('0 = 1'); 

            default:
                return User::whereRaw('0 = 1');
        }
    }
}