<?php
// V-REMEDIATE-1730-150 (Created) | V-FINAL-1730-531 (Filtering Added) | V-FINAL-1730-594 (Notify on Reply)

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\User;
use App\Services\SupportService;
use App\Notifications\SupportReplyNotification; // <-- IMPORT
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification; // <-- IMPORT

class SupportTicketController extends Controller
{
    protected $service;
    public function __construct(SupportService $service)
    {
        $this->service = $service;
    }

    /**
     * Get all support tickets for the admin queue.
     */
    public function index(Request $request)
    {
        $query = SupportTicket::query();

        if ($request->has('status')) $query->where('status', $request->status);
        if ($request->has('category')) $query->where('category', $request->category);
        if ($request->has('agent_id')) $query->where('assigned_to', $request->agent_id);
        
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('created_at', [$request->start_date, $request->end_date]);
        }
        
        if ($request->has('search')) {
            $query->where('subject', 'like', '%' . $request->search . '%');
        }

        $tickets = $query->with('user:id,username,email', 'assignedTo:id,username')
            ->latest()
            ->paginate(25);
            
        return response()->json($tickets);
    }

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
            // TODO: Add attachment support
        ]);

        $message = $supportTicket->messages()->create([
            'user_id' => $admin->id,
            'is_admin_reply' => true,
            'message' => $validated['message'],
        ]);
        
        $supportTicket->update(['status' => 'waiting_for_user']); // Admin replied

        // --- NEW: Notify User (Gap 2 Fix) ---
        $supportTicket->user->notify(new SupportReplyNotification($supportTicket, $message));
        // ------------------------------------

        return response()->json($message, 201);
    }

    /**
     * Update a ticket's status (e.g., resolve it).
     */
    public function updateStatus(Request $request, SupportTicket $supportTicket)
    {
        $admin = $request->user();

        $validated = $request->validate([
            'status' => 'required|string|in:open,waiting_for_user,resolved,closed',
        ]);

        $statusData = ['status' => $validated['status']];

        if ($validated['status'] === 'resolved' && $supportTicket->status !== 'resolved') {
            $statusData['resolved_by'] = $admin->id;
            $statusData['resolved_at'] = now();
        }
        if ($validated['status'] === 'closed') {
            $statusData['closed_at'] = now();
        }

        $supportTicket->update($statusData);
        
        return response()->json(['message' => 'Ticket status updated.']);
    }
}