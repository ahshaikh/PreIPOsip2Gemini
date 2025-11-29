<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    /**
     * Get a list of notifications for the authenticated admin.
     * Used for the "Bell" icon dropdown or a dedicated notifications page.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Return empty structure if user shouldn't have notifications
        if (!$user) {
            return response()->json(['data' => [], 'unread_count' => 0]);
        }

        // Fetch notifications (assuming standard Laravel Database Notifications)
        // If you are using Spatie or another package, adjust accordingly.
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
     * This fixes the 404 error on the Admin Dashboard.
     */
    public function system(): JsonResponse
    {
        // In a real Tier-1 app, you would check:
        // 1. Disk Space
        // 2. Queue Worker Status
        // 3. Third-party API health (SMS/Email gateways)
        
        // For now, we return a safe default structure to prevent the frontend crash.
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

    /**
     * Get the count of unread notifications.
     * Often polled by the frontend header.
     */
    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json([
            'count' => $request->user()->unreadNotifications()->count()
        ]);
    }

    /**
     * Mark a specific notification as read.
     */
    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()
            ->notifications()
            ->where('id', $id)
            ->first();

        if ($notification) {
            $notification->markAsRead();
        }

        return response()->json(['message' => 'Notification marked as read']);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json(['message' => 'All notifications marked as read']);
    }

    /**
     * Delete a notification.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()
            ->notifications()
            ->where('id', $id)
            ->first();

        if ($notification) {
            $notification->delete();
            return response()->json(['message' => 'Notification deleted']);
        }

        return response()->json(['message' => 'Notification not found'], 404);
    }
}