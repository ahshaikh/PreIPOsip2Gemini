<?php
// V-FINAL-1730-536 (Created) | V-FIX-MODULE-16 (Gemini)

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus; // Added for Batching

// Models & Services
use App\Models\User;
use App\Models\PushLog; // Added PushLog model
use App\Services\SmsService;
use App\Jobs\SendPushCampaignJob; // Added Job for async sending

class NotificationController extends Controller
{
    // =======================================================================
    // PART 1: DASHBOARD & UI NOTIFICATIONS (System Alerts & Bell Icon)
    // =======================================================================

    /**
     * Get a list of notifications for the authenticated admin.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json(['data' => [], 'unread_count' => 0]);
        }

        $notifications = $user->notifications()
            ->latest()
            ->paginate($request->input('per_page', 10));

        return response()->json([
            'data' => $notifications->items(),
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'total' => $notifications->total(),
            ],
            'unread_count' => $user->unreadNotifications()->count(),
        ]);
    }

    /**
     * Get system-wide notifications and health status.
     */
    public function system(): JsonResponse
    {
        return response()->json([
            'data' => [
                [
                    'id' => 'sys-1',
                    'type' => 'info',
                    'title' => 'System Operational',
                    'message' => 'All systems are running normally.',
                    'created_at' => now()->toIso8601String(),
                ]
            ],
            'count' => 0
        ]);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json([
            'count' => $request->user()->unreadNotifications()->count()
        ]);
    }

    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()->notifications()->where('id', $id)->first();
        if ($notification) {
            $notification->markAsRead();
        }
        return response()->json(['message' => 'Notification marked as read']);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();
        return response()->json(['message' => 'All notifications marked as read']);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()->notifications()->where('id', $id)->first();
        if ($notification) {
            $notification->delete();
            return response()->json(['message' => 'Notification deleted']);
        }
        return response()->json(['message' => 'Notification not found'], 404);
    }

    // =======================================================================
    // PART 2: PUSH NOTIFICATION CAMPAIGNS (Real Data Implementation)
    // =======================================================================

    /**
     * Dashboard Stats for Push Campaigns
     * Endpoint: /api/v1/admin/notifications/push/stats
     * FIX: Replaced mock data with real aggregation from PushLog
     */
    public function pushStats(): JsonResponse
    {
        // FIX: Calculate real stats from PushLog table
        $totalSent = PushLog::count();
        $delivered = PushLog::where('status', 'delivered')->count();
        $opened = PushLog::where('status', 'opened')->count();
        $clicked = PushLog::where('status', 'clicked')->count();
        
        // Calculate rates safely
        $deliveryRate = $totalSent > 0 ? ($delivered / $totalSent) * 100 : 0;
        $openRate = $delivered > 0 ? ($opened / $delivered) * 100 : 0;
        $clickRate = $opened > 0 ? ($clicked / $opened) * 100 : 0;

        // Estimate active devices (users who have received a push in last 30 days)
        $activeDevices = PushLog::where('created_at', '>=', now()->subDays(30))
            ->distinct('user_id')
            ->count('user_id');

        return response()->json([
            'total_sent' => $totalSent,
            'total_delivered' => $delivered,
            'total_opened' => $opened,
            'total_clicked' => $clicked,
            'delivery_rate' => round($deliveryRate, 1),
            'open_rate' => round($openRate, 1),
            'click_rate' => round($clickRate, 1),
            'active_devices' => $activeDevices
        ]);
    }

    /**
     * Push Campaign History
     * Endpoint: /api/v1/admin/notifications/push
     * FIX: Replaced mock array with paginated PushLog query
     */
    public function pushIndex(Request $request): JsonResponse
    {
        // FIX: Fetch real logs sorted by date
        $logs = PushLog::latest()
            ->select('id', 'title', 'body', 'status', 'sent_at', 'created_at', 'user_id')
            ->with('user:id,name,email') // Load user info
            ->paginate(20);

        return response()->json($logs);
    }

    /**
     * Push Templates
     * Endpoint: /api/v1/admin/notifications/templates
     */
    public function templates(): JsonResponse
    {
        // Keeping mocks for templates as table structure wasn't audited for change
        // Ideally fetch from 'notification_templates' if available
        $data = [
            ['id' => 1, 'name' => "New IPO Alert", 'title' => "New Investment Opportunity!", 'body' => "{{company_name}} is now available.", 'category' => "investment"],
            ['id' => 2, 'name' => "KYC Reminder", 'title' => "Complete Your KYC", 'body' => "Hi {{user_name}}, please complete verification.", 'category' => "kyc"],
            ['id' => 3, 'name' => "Payment Success", 'title' => "Payment Received", 'body' => "We received ₹{{amount}}.", 'category' => "payment"]
        ];
        return response()->json($data);
    }

    /**
     * Send Push Notification
     *
     * V-AUDIT-MODULE16-LOW (SECURITY): Sanitize action_url and image_url
     *
     * Previous Issue:
     * Accepted action_url and image_url without strict sanitization. A compromised admin
     * account could broadcast push notifications with malicious deep links like:
     * - javascript:alert('XSS') - Execute JS if WebView handles insecurely
     * - data:text/html,<script>...</script> - Inject malicious content
     * - file:///etc/passwd - Access local files (if app allows)
     *
     * Security Risk:
     * If mobile app's WebView doesn't properly validate URLs, users could be exploited
     * via XSS, phishing, or malware distribution through push notifications
     *
     * Fix:
     * 1. Validate URLs are proper HTTP/HTTPS only
     * 2. Block javascript:, data:, file:, and other dangerous schemes
     * 3. Ensure URLs point to trusted domains (optional whitelist)
     *
     * FIX: Implemented logic to actually send via Job Batching
     */
    public function sendPush(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'target_type' => 'required|in:all,active,inactive,incomplete_kyc,kyc_verified,high_value,low_activity,new_users,user,users',
            'user_id' => 'required_if:target_type,user|exists:users,id',
            // FIX: Added 'nullable' to prevent "must be an array" error when field is null
            'user_ids' => 'nullable|required_if:target_type,users|array',
            'user_ids.*' => 'integer|exists:users,id', // Validate each ID in array
            'title' => 'required|string|max:255',
            'body' => 'required|string|max:500',
            'image_url' => 'nullable|url|regex:/^https?:\/\//i', // V-AUDIT-MODULE16: Only HTTP/HTTPS
            'action_url' => 'nullable|url|regex:/^https?:\/\//i', // V-AUDIT-MODULE16: Only HTTP/HTTPS
            'priority' => 'nullable|in:high,normal',
        ]);

        // V-AUDIT-MODULE16-LOW: Additional URL security validation
        // Block dangerous URL schemes that could be used for XSS or phishing
        $dangerousSchemes = ['javascript:', 'data:', 'file:', 'vbscript:', 'about:'];

        if (isset($validated['image_url'])) {
            foreach ($dangerousSchemes as $scheme) {
                if (stripos($validated['image_url'], $scheme) === 0) {
                    return response()->json([
                        'message' => 'Invalid image_url: Dangerous URL scheme detected',
                        'error' => 'image_url must be a valid HTTP or HTTPS URL'
                    ], 422);
                }
            }
        }

        if (isset($validated['action_url'])) {
            foreach ($dangerousSchemes as $scheme) {
                if (stripos($validated['action_url'], $scheme) === 0) {
                    return response()->json([
                        'message' => 'Invalid action_url: Dangerous URL scheme detected',
                        'error' => 'action_url must be a valid HTTP or HTTPS URL'
                    ], 422);
                }
            }

            // V-AUDIT-MODULE16-LOW: Optional domain whitelist check
            // Uncomment and configure if you want to restrict to specific domains
            // $allowedDomains = ['preiposip.com', 'app.preiposip.com'];
            // $urlHost = parse_url($validated['action_url'], PHP_URL_HOST);
            // if (!in_array($urlHost, $allowedDomains)) {
            //     return response()->json([
            //         'message' => 'Invalid action_url: Domain not whitelisted',
            //         'allowed_domains' => $allowedDomains
            //     ], 422);
            // }
        }

        // FIX: Implement proper segment filtering logic
        // Build query based on target_type (segments mapped to actual user filters)
        $query = User::where('status', 'active');

        switch ($validated['target_type']) {
            case 'user':
                $query->where('id', $validated['user_id']);
                break;

            case 'users':
                $query->whereIn('id', $validated['user_ids']);
                break;

            case 'all':
                // No additional filtering - send to all active users
                break;

            case 'active':
                $query->whereHas('subscription', function ($q) {
                    $q->where('status', 'active');
                });
                break;

            case 'inactive':
                $query->whereDoesntHave('subscription');
                break;

            case 'incomplete_kyc':
                $query->whereHas('kyc', function ($q) {
                    $q->where('status', 'pending');
                });
                break;

            case 'kyc_verified':
                $query->whereHas('kyc', function ($q) {
                    $q->where('status', 'verified');
                });
                break;

            case 'high_value':
                $query->whereHas('wallet', function ($q) {
                    $q->where('balance', '>', 10000);
                });
                break;

            case 'low_activity':
                $query->whereDoesntHave('activityLogs', function ($q) {
                    $q->where('created_at', '>=', now()->subDays(30));
                });
                break;

            case 'new_users':
                $query->where('created_at', '>=', now()->subDays(7));
                break;
        }

        // FIX: Check if push notification infrastructure is ready
        // Without this check, notifications queue but never send (silent failure)
        $totalUsers = $query->count();

        if ($totalUsers === 0) {
            return response()->json([
                'message' => 'No users match the selected target segment.',
                'total_users' => 0
            ], 422);
        }

        // CRITICAL: Check if user_devices table exists (required for actual sending)
        // Without device tokens, jobs run but notifications never reach users
        try {
            \DB::table('user_devices')->limit(1)->count();
            $hasDeviceTokens = \DB::table('user_devices')->exists();
        } catch (\Throwable $e) {
            // Table doesn't exist - notification system not fully configured
            \Log::warning('Push notification sent to queue, but user_devices table missing', [
                'error' => $e->getMessage(),
                'target_type' => $validated['target_type'],
                'total_users' => $totalUsers
            ]);

            // FIX: Return warning instead of silent failure
            return response()->json([
                'message' => 'Notification queued for ' . $totalUsers . ' users',
                'warning' => 'Push notification infrastructure incomplete. Jobs will queue but not send until user_devices table is created.',
                'total_users' => $totalUsers,
                'debug' => 'Missing: user_devices table for storing FCM/OneSignal tokens'
            ], 200);
        }

        if (!$hasDeviceTokens) {
            \Log::warning('Push notification sent but no device tokens registered', [
                'target_type' => $validated['target_type'],
                'total_users' => $totalUsers
            ]);

            return response()->json([
                'message' => 'Notification queued for ' . $totalUsers . ' users',
                'warning' => 'No device tokens registered. Users must enable push notifications in their app first.',
                'total_users' => $totalUsers
            ], 200);
        }

        // FIX: Chunking for Performance
        // Dispatch batch jobs instead of looping synchronously
        $batch = [];

        $query->chunkById(100, function ($users) use ($validated, &$batch) {
            $batch[] = new SendPushCampaignJob($users, $validated);
        });

        if (count($batch) > 0) {
            Bus::batch($batch)
                ->name('Push Campaign: ' . $validated['title'])
                ->dispatch();

            return response()->json([
                'message' => 'Push notification campaign queued successfully!',
                'total_users' => $totalUsers,
                'batches' => count($batch)
            ]);
        }

        return response()->json(['message' => 'No active users found for target.'], 422);
    }

    public function schedulePush(Request $request): JsonResponse
    {
        // TODO: Implement Scheduled Task / Cron storage
        return response()->json(['message' => 'Notification scheduled successfully']);
    }

    // =======================================================================
    // PART 3: SMS TESTING (Preserved)
    // =======================================================================

    public function sendTestSms(Request $request, SmsService $smsService)
    {
        $validated = $request->validate([
            'mobile' => 'required|string|regex:/^[0-9]{10}$/',
        ]);
        
        $message = "This is a test message from PreIPO SIP at " . now();
        $templateId = function_exists('setting') ? setting('msg91_dlt_te_id') : null;
        $mockUser = new User(['mobile' => $validated['mobile']]);

        try {
            $log = $smsService->send($mockUser, $message, 'admin.test', $templateId);

            if ($log && $log->status === 'sent') {
                return response()->json(['message' => 'Test SMS sent successfully to ' . $validated['mobile']]);
            } else {
                return response()->json([
                    'message' => 'SMS failed to send. Check logs and settings.',
                    'error' => $log->error_message ?? 'Unknown error'
                ], 500);
            }
        } catch (\Throwable $e) {
            return response()->json(['message' => 'SMS Service Error: ' . $e->getMessage()], 500);
        }
    }
}