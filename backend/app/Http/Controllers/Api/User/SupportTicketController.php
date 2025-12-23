<?php
// V-REMEDIATE-1730-149 (Created) | V-FINAL-1730-595 (Full User Flow) | V-FIX-MODULE-14-PERFORMANCE (Gemini) | V-AUDIT-MODULE13-004 (Enum Validation)

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\User;
use App\Services\FileUploadService;
use App\Enums\TicketCategory;
// REMOVED: NotificationService is no longer used directly to prevent synchronous blocking.
// use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rules\Enum;
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

        $perPage = (int) setting('records_per_page', 15);

        return response()->json(
            $query->latest()->paginate($perPage)
        );
    }

    /**
     * Store a new support ticket.
     * Test: testUserCanCreateTicket
     * Test: testUserCanAttachFilesToTicket
     * V-AUDIT-MODULE13-005 (LOW): Implemented rate limiting to prevent abuse
     */
    public function store(Request $request)
    {
        // V-AUDIT-MODULE13-005: Rate limiting to prevent ticket flooding
        // Previous Issue: No rate limiting on ticket creation endpoint. A malicious user could
        // flood the system with tickets and attachments, filling storage and overwhelming support.
        // Fix: Limit to 5 tickets per hour per user (configurable via settings).
        // Benefits: Prevents abuse, protects storage, maintains support quality.
        $user = $request->user();
        $maxTicketsPerHour = (int) setting('support_max_tickets_per_hour', 5);

        $rateLimitKey = 'create-ticket:' . $user->id;
        $executed = RateLimiter::attempt(
            $rateLimitKey,
            $maxTicketsPerHour,
            function() {}, // Empty callback, we just need the check
            3600 // 1 hour in seconds
        );

        if (!$executed) {
            $availableIn = RateLimiter::availableIn($rateLimitKey);
            return response()->json([
                'message' => "Rate limit exceeded. You can create up to {$maxTicketsPerHour} tickets per hour. Please try again in " . ceil($availableIn / 60) . " minutes.",
                'retry_after' => $availableIn
            ], 429);
        }

        // Check if support tickets are enabled
        // Helper function setting() assumed to exist globally
        if (function_exists('setting') && !setting('support_tickets_enabled', true)) {
            return response()->json([
                'message' => 'Support tickets are currently disabled. Please contact us via email.'
            ], 503);
        }

        // V-AUDIT-MODULE13-004: Replace hardcoded category validation with Enum
        // Previous Issue: Hardcoded 'in:technical,investment,payment,...' required manual updates
        // when adding new categories, creating deployment dependencies.
        // Fix: Use TicketCategory Enum for validation. New categories added to Enum are automatically valid.
        // Benefits: Centralized category management, no controller changes needed for new categories.
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'category' => ['required', 'string', new Enum(TicketCategory::class)],
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