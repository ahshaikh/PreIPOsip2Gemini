<?php
// V-FINAL-1730-234 (Created) | V-FINAL-1730-590 

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    /**
     * Get all notifications for the authenticated user, paginated.
     * Test: testGetNotificationsReturnsUserNotifications
     * Test: testNotificationsPaginated
     */
    public function index(Request $request)
    {
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->paginate(20); // 20 per page
            
        return response()->json($notifications);
    }

    /**
     * Mark a single notification as read.
     * Test: testMarkNotificationAsRead
     */
    public function markAsRead(Request $request, $id)
    {
        $notification = $request->user()->notifications()->find($id);

        if ($notification) {
            $notification->markAsRead();
            return response()->json(['message' => 'Notification marked as read.']);
        }

        return response()->json(['message' => 'Notification not found.'], 404);
    }

    /**
     * Mark all unread notifications as read.
     * Test: testMarkAllNotificationsAsRead
     */
    public function markAllAsRead(Request $request)
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);
        
        return response()->json(['message' => 'All notifications marked as read.']);
    }

    /**
     * Delete a single notification.
     * Test: testDeleteNotification
     */
    public function destroy(Request $request, $id)
    {
        $notification = $request->user()->notifications()->find($id);

        if ($notification) {
            $notification->delete();
            return response()->json(['message' => 'Notification deleted.']);
        }

        return response()->json(['message' => 'Notification not found.'], 404);
    }
}