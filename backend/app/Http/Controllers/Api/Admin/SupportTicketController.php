<?php
// V-REMEDIATE-1730-150

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use Illuminate\Http\Request;

class SupportTicketController extends Controller
{
    /**
     * Get all support tickets for the admin queue.
     */
    public function index(Request $request)
    {
        $status = $request->query('status', 'open'); // Default to 'open'

        $tickets = SupportTicket::where('status', $status)
            ->with('user:id,username,email')
            ->latest()
            ->paginate(25);
            
        return response()->json($tickets);
    }

    /**
     * Get a single ticket and its messages for an admin.
     */
    public function show(SupportTicket $supportTicket)
    {
        return $supportTicket->load('messages.author:id,username', 'user.profile');
    }

    /**
     * Add an admin reply to a ticket.
     */
    public function reply(Request $request, SupportTicket $supportTicket)
    {
        $admin = $request->user();
        
        $validated = $request->validate([
            'message' => 'required|string|min:1',
        ]);

        $supportTicket->messages()->create([
            'user_id' => $admin->id,
            'is_admin_reply' => true,
            'message' => $validated['message'],
        ]);
        
        // Set status to "waiting for user"
        $supportTicket->update(['status' => 'waiting_for_user']);

        // TODO: Dispatch job to notify user of the reply

        return response()->json(['message' => 'Reply added.'], 201);
    }

    /**
     * Update a ticket's status (e.g., resolve it).
     */
    public function updateStatus(Request $request, SupportTicket $supportTicket)
    {
        $admin = $request->user();

        $validated = $request->validate([
            'status' => 'required|string|in:open,waiting_for_user,resolved',
        ]);

        $supportTicket->update([
            'status' => $validated['status'],
            'resolved_by' => $validated['status'] === 'resolved' ? $admin->id : null,
            'resolved_at' => $validated['status'] === 'resolved' ? now() : null,
        ]);
        
        // TODO: Notify user if resolved

        return response()->json(['message' => 'Ticket status updated.']);
    }
}