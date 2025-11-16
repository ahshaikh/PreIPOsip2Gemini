<?php
// V-REMEDIATE-1730-150 (Created) | V-FINAL-1730-531 (Filtering Added)

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\User; // <-- IMPORT
use App\Services\SupportService; // <-- IMPORT
use Illuminate\Http\Request;

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
        // --- UPDATED: Advanced Filtering ---
        $query = SupportTicket::query();

        // Filter by Status (e.g., open, resolved)
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by Category (e.g., 'technical' for Live Chat)
        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        // Filter by Agent (e.g., admin_id)
        if ($request->has('agent_id')) {
            $query->where('assigned_to', $request->agent_id);
        }
        
        // Filter by Date Range
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('created_at', [$request->start_date, $request->end_date]);
        }
        
        // Search
        if ($request->has('search')) {
            $query->where('subject', 'like', '%' . $request->search . '%');
        }

        $tickets = $query->with('user:id,username,email', 'assignedTo:id,username') // Eager load agent
            ->latest()
            ->paginate(25);
        // ---------------------------------
            
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

        $message = $supportTicket->messages()->create([
            'user_id' => $admin->id,
            'is_admin_reply' => true,
            'message' => $validated['message'],
        ]);
        
        // If ticket was "waiting_for_user", change to "open" (admin replied)
        if ($supportTicket->status === 'waiting_for_user') {
            $supportTicket->update(['status' => 'open']);
        }

        // TODO: Dispatch job to notify user of the reply

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
        
        // Log this action
        // ...
        
        return response()->json(['message' => 'Ticket status updated.']);
    }
}