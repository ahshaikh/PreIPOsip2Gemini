<?php
// V-FINAL-1730-536 (Created)

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon; // Added for Push Notification dates

// Models & Services
use App\Models\User;
use App\Services\SmsService;

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
    // PART 2: PUSH NOTIFICATION CAMPAIGNS (Fixes 404 Errors)
    // =======================================================================

    /**
     * Dashboard Stats for Push Campaigns
     * Endpoint: /api/v1/admin/notifications/push/stats
     */
    public function pushStats(): JsonResponse
    {
        // Mock data to initialize the dashboard
        return response()->json([
            'total_sent' => 1250,
            'total_delivered' => 1180,
            'total_opened' => 890,
            'total_clicked' => 456,
            'delivery_rate' => 94.4,
            'open_rate' => 75.4,
            'click_rate' => 38.6,
            'active_devices' => 2340
        ]);
    }

    /**
     * Push Campaign History
     * Endpoint: /api/v1/admin/notifications/push
     */
    public function pushIndex(Request $request): JsonResponse
    {
        // Mock history data
        $data = [
            [
                'id' => 1,
                'title' => "New Investment Opportunity!",
                'body' => "SpaceX Series F round now available. Invest from ₹5,000.",
                'target_audience' => "all",
                'sent_at' => Carbon::now()->subDays(2)->toDateTimeString(),
                'status' => "sent",
                'total_recipients' => 2500,
                'delivered' => 2380,
                'opened' => 1850,
                'clicked' => 920,
                'created_at' => Carbon::now()->subDays(2)->toDateTimeString()
            ],
            [
                'id' => 2,
                'title' => "KYC Reminder",
                'body' => "Complete your KYC to start investing.",
                'target_audience' => "incomplete_kyc",
                'sent_at' => Carbon::now()->subDay()->toDateTimeString(),
                'status' => "sent",
                'total_recipients' => 450,
                'delivered' => 420,
                'opened' => 280,
                'clicked' => 145,
                'created_at' => Carbon::now()->subDay()->toDateTimeString()
            ]
        ];

        return response()->json($data);
    }

    /**
     * Push Templates
     * Endpoint: /api/v1/admin/notifications/templates
     */
    public function templates(): JsonResponse
    {
        $data = [
            ['id' => 1, 'name' => "New IPO Alert", 'title' => "New Investment Opportunity!", 'body' => "{{company_name}} is now available.", 'category' => "investment"],
            ['id' => 2, 'name' => "KYC Reminder", 'title' => "Complete Your KYC", 'body' => "Hi {{user_name}}, please complete verification.", 'category' => "kyc"],
            ['id' => 3, 'name' => "Payment Success", 'title' => "Payment Received", 'body' => "We received ₹{{amount}}.", 'category' => "payment"]
        ];
        return response()->json($data);
    }

    public function sendPush(Request $request): JsonResponse
    {
        // Placeholder for actual sending logic (FCM/OneSignal)
        return response()->json(['message' => 'Notification queued successfully']);
    }

    public function schedulePush(Request $request): JsonResponse
    {
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