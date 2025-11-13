<?php
// V-FINAL-1730-234

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        // Return 10 latest, plus count of unread
        $user = $request->user();
        
        return response()->json([
            'unread_count' => $user->unreadNotifications()->count(),
            'notifications' => $user->notifications()->latest()->limit(20)->get()
        ]);
    }

    public function markAsRead(Request $request, $id)
    {
        $user = $request->user();
        
        if ($id === 'all') {
            $user->unreadNotifications->markAsRead();
        } else {
            $notification = $user->notifications()->where('id', $id)->first();
            if ($notification) {
                $notification->markAsRead();
            }
        }

        return response()->json(['message' => 'Marked as read']);
    }
}