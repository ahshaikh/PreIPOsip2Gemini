<?php
// V-REMEDIATE-1730-149 (Created) | V-FINAL-1730-595 (Full User Flow) | V-FIX-MODULE-14-PERFORMANCE (Gemini)

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\User;
use App\Services\FileUploadService;
// REMOVED: NotificationService is no longer used directly to prevent synchronous blocking.
// use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
// ADDED: Events for asynchronous processing
use App\Events\TicketCreated;
use App\Events\TicketReplied;

class SupportTicketController extends Controller
{
    protected $fileUploader;
    // protected $notificationService; // Removed dependency

    public function __construct(FileUploadService $fileUploader)
    {
        $this->fileUploader = $fileUploader;
        // $this->notificationService = $notificationService; // Removed
    }

    /**
     * Get the user's own tickets.
     */
    public function index(Request $request)
    {
        $query = $request->user()->tickets();
        
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $tickets = $query->latest()->paginate(20);
        return response()->json($tickets);
    }

    /**
     * Store a new support ticket.
     * Test: testUserCanCreateTicket
     * Test: testUserCanAttachFilesToTicket
     */
    public function store(Request $request)
    {
        // Check if support tickets are enabled
        // Helper function setting() assumed to exist globally
        if (function_exists('setting') && !setting('support_tickets_enabled', true)) {
            return response()->json([
                'message' => 'Support tickets are currently disabled. Please contact us via email.'
            ], 503);
        }

        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'category' => 'required|string|in:technical,investment,payment,kyc,withdrawal,bonus,account,subscription,general,other',
            'priority' => 'required|string|in:low,medium,high',
            'message' => 'required|string|min:20',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf,zip|max:10240', // 10MB
        ]);
        
        $user = $request->user();

        $ticket = $user->tickets()->create([
            'ticket_code' => 'TKT-' . strtoupper(Str::random(8)),
            'subject' => $validated['subject'],
            'category' => $validated['category'],
            'priority' => $validated['priority'],
            'status' => 'open',
        ]);
        
        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $this->fileUploader->upload($request->file('attachment'), [
                'path' => "support/{$ticket->id}",
                'encrypt' => true
            ]);
        }

        $ticket->messages()->create([
            'user_id' => $user->id,
            'is_admin_reply' => false,
            'message' => $validated['message'],
            'attachments' => $attachmentPath ? [$attachmentPath] : null,
        ]);

        /* * FIX: Module 14 - Synchronous Notification Loops (Critical)
         * REPLACED: The foreach loop below blocked the request for 1-5 seconds.
         * * $admins = User::role('admin')->get();
         * foreach ($admins as $admin) {
         * $this->notificationService->send($admin, ...);
         * }
         * * ACTION: Dispatched 'TicketCreated' event. The listener 'SendTicketNotifications'
         * must be implemented and set to ShouldQueue to handle emails in the background.
         */
        event(new TicketCreated($ticket));

        return response()->json($ticket, 201);
    }

    /**
     * Show a single ticket (if user owns it).
     */
    public function show(Request $request, SupportTicket $supportTicket)
    {
        if ($supportTicket->user_id !== $request->user()->id) {
            abort(403);
        }
        return $supportTicket->load('messages.author:id,username');
    }

    /**
     * User replies to their own ticket.
     */
    public function reply(Request $request, SupportTicket $supportTicket)
    {
        if ($supportTicket->user_id !== $request->user()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'message' => 'required|string|min:1',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf,zip|max:10240', // 10MB
        ]);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $this->fileUploader->upload($request->file('attachment'), [
                'path' => "support/{$supportTicket->id}",
                'encrypt' => true
            ]);
        }

        $message = $supportTicket->messages()->create([
            'user_id' => $request->user()->id,
            'is_admin_reply' => false,
            'message' => $validated['message'],
            'attachments' => $attachmentPath ? [$attachmentPath] : null,
        ]);

        $supportTicket->update(['status' => 'open']); // User replied

        /* * FIX: Module 14 - Synchronous Notification Loops (Critical)
         * REPLACED: Synchronous admin notification loop.
         * ACTION: Dispatched 'TicketReplied' event for async processing.
         */
        event(new TicketReplied($supportTicket, $message));

        return response()->json(['message' => 'Reply added.'], 201);
    }

    /**
     * NEW: User closes their own ticket.
     * Test: testUserCanCloseTicket
     */
    public function close(Request $request, SupportTicket $supportTicket)
    {
        if ($supportTicket->user_id !== $request->user()->id) {
            abort(403);
        }
        if ($supportTicket->status === 'closed') {
            return response()->json(['message' => 'Ticket is already closed.'], 400);
        }

        $supportTicket->update([
            'status' => 'resolved', // User closing = resolved
            'resolved_at' => now()
        ]);
        
        return response()->json(['message' => 'Ticket marked as resolved.']);
    }
    
    /**
     * NEW: User rates a ticket.
     * Test: testUserCanRateTicketResolution
     */
    public function rate(Request $request, SupportTicket $supportTicket)
    {
        if ($supportTicket->user_id !== $request->user()->id) {
            abort(403);
        }
        if (!in_array($supportTicket->status, ['resolved', 'closed'])) {
            return response()->json(['message' => 'Can only rate resolved or closed tickets.'], 400);
        }

        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'rating_feedback' => 'nullable|string|max:1000'
        ]);
        
        $supportTicket->update([
            'rating' => $validated['rating'],
            'rating_feedback' => $validated['rating_feedback']
        ]);
        
        return response()->json(['message' => 'Thank you for your feedback!']);
    }
}