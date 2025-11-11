<?php
// V-PHASE2-1730-053


namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index(Request $request)
    {
        $users = User::role('user')
            ->with('profile', 'kyc')
            ->latest()
            ->paginate(setting('records_per_page', 25));
            
        return response()->json($users);
    }

    /**
     * Display the specified user.
     */
    public function show(User $user)
    {
        return response()->json($user->load('profile', 'kyc', 'activityLogs'));
    }

    /**
     * Update the specified user's admin-level details.
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'status' => 'required|in:active,suspended,blocked',
            // Add other updatable fields as needed
        ]);
        
        $user->update($validated);
        
        // TODO: Log this admin action in audit_trails
        
        return response()->json([
            'message' => 'User status updated.',
            'user' => $user,
        ]);
    }

    /**
     * Suspend a user.
     */
    public function suspend(Request $request, User $user)
    {
        $request->validate(['reason' => 'required|string|max:255']);
        
        $user->update(['status' => 'suspended']);

        // TODO: Log this action with the reason
        
        return response()->json(['message' => 'User has been suspended.']);
    }
}