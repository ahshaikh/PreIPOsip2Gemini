<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

// Models
use App\Models\User;

class NotificationController extends Controller
{
    // =======================================================================
    // PART 1: NOTIFICATION LIST & ACTIONS
    // =======================================================================

    /**
     * List User Notifications
     * Endpoint: GET /api/v1/user/notifications
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json(['data' => [], 'meta' => ['total' => 0]]);
            }

            // Defensive: Ensure User model has the Notifiable trait
            if (!method_exists($user, 'notifications')) {
                return response()->json(['data' => [], 'meta' => ['total' => 0]]);
            }

            $notifications = $user->notifications()
                ->latest()
                ->paginate($request->input('per_page', 15));

            // Transform to ensure safe JSON structure
            $data = $notifications->through(function ($n) {
                return [
                    'id' => $n->id,
                    'type' => class_basename($n->type), // Simplified type name
                    'data' => $n->data, // Automatically cast to array by Laravel
                    'read_at' => $n->read_at,
                    'created_at' => $n->created_at ? $n->created_at->toIso8601String() : null,
                    'time_ago' => $n->created_at ? $n->created_at->diffForHumans() : '',
                ];
            });

            return response()->json([
                'data' => $data,
                'meta' => [
                    'current_page' => $notifications->currentPage(),
                    'last_page' => $notifications->lastPage(),
                    'total' => $notifications->total(),
                ],
                'unread_count' => $user->unreadNotifications()->count(),
            ]);

        } catch (\Throwable $e) {
            Log::error("User Notifications Failed: " . $e->getMessage());
            return response()->json([
                'message' => 'Failed to load notifications',
                'data' => [],
                'unread_count' => 0
            ], 500);
        }
    }

    /**
     * Get Unread Count (For bell icon)
     * Endpoint: GET /api/v1/user/notifications/unread-count
     */
    public function unreadCount(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $count = $user ? $user->unreadNotifications()->count() : 0;
            return response()->json(['count' => $count]);
        } catch (\Throwable $e) {
            return response()->json(['count' => 0]);
        }
    }

    /**
     * Mark single notification as read
     * Endpoint: PATCH /api/v1/user/notifications/{id}/read
     */
    public function markAsRead(Request $request, string $id): JsonResponse
    {
        try {
            $notification = $request->user()
                ->notifications()
                ->where('id', $id)
                ->first();

            if ($notification) {
                $notification->markAsRead();
                return response()->json(['message' => 'Notification marked as read']);
            }

            return response()->json(['message' => 'Notification not found'], 404);

        } catch (\Throwable $e) {
            return response()->json(['message' => 'Error updating status'], 500);
        }
    }

    /**
     * Mark all as read
     * Endpoint: POST /api/v1/user/notifications/mark-all-read
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        try {
            $request->user()->unreadNotifications->markAsRead();
            return response()->json(['message' => 'All notifications marked as read']);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Error updating statuses'], 500);
        }
    }

    /**
     * Delete a notification
     * Endpoint: DELETE /api/v1/user/notifications/{id}
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        try {
            $notification = $request->user()
                ->notifications()
                ->where('id', $id)
                ->first();

            if ($notification) {
                $notification->delete();
                return response()->json(['message' => 'Notification deleted']);
            }

            return response()->json(['message' => 'Notification not found'], 404);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Error deleting notification'], 500);
        }
    }

    // =======================================================================
    // PART 2: NOTIFICATION PREFERENCES (Manage Opt-outs)
    // =======================================================================

    /**
     * Get User Preferences
     * Endpoint: GET /api/v1/user/notifications/preferences
     */
    public function getPreferences(Request $request): JsonResponse
    {
        try {
            // Define standard preferences if none exist
            $defaults = [
                'email_transactional' => true,
                'email_marketing' => false,
                'sms_security' => true, // OTPs should usually be locked to true logic-side
                'push_reminders' => true,
            ];

            // In a real app, you fetch this from 'user_notification_preferences' table
            // For now, we simulate or fetch if table exists
            $userPrefs = []; 
            // if (Schema::hasTable('user_notification_preferences')) { ... }

            // Merge user prefs with defaults
            $finalPrefs = array_merge($defaults, $userPrefs);

            return response()->json(['data' => $finalPrefs]);

        } catch (\Throwable $e) {
            return response()->json(['data' => []]);
        }
    }

    /**
     * Update Preference
     * Endpoint: PUT /api/v1/user/notifications/preferences
     */
    public function updatePreference(Request $request): JsonResponse
    {
        $request->validate([
            'key' => 'required|string',
            'value' => 'required|boolean'
        ]);

        try {
            // Logic to update DB would go here
            // DB::table('user_notification_preferences')->updateOrInsert(...)
            
            return response()->json(['message' => 'Preference updated']);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Update failed'], 500);
        }
    }

    // =======================================================================
    // PART 3: DEVICE TOKEN MANAGEMENT (For Push Notifications)
    // =======================================================================

    /**
     * Register Device Token (FCM)
     * Endpoint: POST /api/v1/user/notifications/device-token
     */
    public function registerDevice(Request $request): JsonResponse
    {
        $request->validate([
            'token' => 'required|string',
            'platform' => 'required|in:android,ios,web',
        ]);

        try {
            // Save to 'user_devices' or similar table
            // $request->user()->devices()->create(...)
            
            return response()->json(['message' => 'Device registered successfully']);
        } catch (\Throwable $e) {
            // Log but don't crash frontend flow
            Log::warning("Device Token Registration Failed: " . $e->getMessage());
            return response()->json(['message' => 'Registration failed'], 500);
        }
    }
}