<?php
// V-REMEDIATE-1730-150 (Created) | V-FINAL-1730-531 (Filtering Added) | V-FINAL-1730-594 (Notify on Reply)

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

// Models
use App\Models\SupportTicket;
use App\Models\User;

class SupportTicketController extends Controller
{
    /**
     * List Support Tickets
     * Endpoint: GET /api/v1/admin/support-tickets
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // 1. Start Query
            // Use query() instead of with() to prevent crashes if relations are undefined in Model
            $query = SupportTicket::query();

            // 2. Filters
            if ($request->filled('status') && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            if ($request->filled('priority') && $request->priority !== 'all') {
                $query->where('priority', $request->priority);
            }

            if ($request->filled('category') && $request->category !== 'all') {
                $query->where('category', $request->category);
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('ticket_code', 'like', "%{$search}%")
                      ->orWhere('subject', 'like', "%{$search}%");
                    
                    // Safe Relation Search
                    try {
                        $q->orWhereHas('user', function($u) use ($search) {
                            $u->where('username', 'like', "%{$search}%")
                              ->orWhere('email', 'like', "%{$search}%");
                        });
                    } catch (\Throwable $e) {
                        // Ignore if user relation missing
                    }
                });
            }

            // 3. Pagination
            $tickets = $query->latest()->paginate(10);

            // 4. Safe Transformation
            $data = $tickets->through(function ($ticket) {
                try {
                    return [
                        'id' => $ticket->id,
                        'ticket_code' => $ticket->ticket_code ?? 'N/A',
                        'subject' => $ticket->subject,
                        'category' => $ticket->category,
                        'priority' => $ticket->priority,
                        'status' => $ticket->status,
                        
                        // Null-Safe User Access
                        'user' => [
                            'name' => $ticket->user?->username ?? 'Unknown User',
                            'username' => $ticket->user?->username ?? 'Unknown User',
                            'email' => $ticket->user?->email ?? 'N/A',
                            'avatar' => $ticket->user?->avatar_url,
                            'profile' => [
                                'first_name' => $ticket->user?->profile?->first_name ?? null,
                                'last_name' => $ticket->user?->profile?->last_name ?? null,
                            ]
                        ],

                        // Null-Safe Assignee Access
                        'assigned_to' => $ticket->assignedTo ? [
                            'id' => $ticket->assignedTo->id,
                            'name' => $ticket->assignedTo->username
                        ] : null,

                        // Safe Dates
                        'created_at' => $this->safeDate($ticket->created_at),
                        'last_reply_at' => $this->safeDate($ticket->updated_at), // Fallback to updated_at
                    ];
                } catch (\Throwable $rowError) {
                    Log::error("Ticket Row Error ID {$ticket->id}: " . $rowError->getMessage());
                    return [
                        'id' => $ticket->id,
                        'ticket_code' => 'ERROR',
                        'subject' => 'Data Corrupted',
                        'status' => 'error',
                        'created_at' => '-'
                    ];
                }
            });

            return response()->json($data);

        } catch (\Throwable $e) {
            Log::error("Support Ticket Index Failed: " . $e->getMessage());
            return response()->json([
                'message' => 'System Error: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    /**
     * Show Single Ticket
     */
    public function show($id): JsonResponse
    {
        try {
            // REMOVED Eager Loading (with) to prevent "Call to undefined relationship" crashes
            // If the SupportTicket model is missing 'messages' or 'user' methods, this prevents 500 error.
            $ticket = SupportTicket::find($id);

            if (!$ticket) {
                return response()->json(['message' => 'Ticket not found'], 404);
            }

            // Safe Messages Loading
            $messages = collect([]);
            try {
                // Only try to load messages if relation exists
                $messages = $ticket->messages->map(function($msg) {
                    return [
                        'id' => $msg->id,
                        'message' => $msg->message,
                        'is_admin' => $msg->is_admin_reply ?? false,
                        'created_at' => $this->safeDate($msg->created_at),
                        'user_name' => $msg->user?->username ?? 'System'
                    ];
                });
            } catch (\Throwable $ex) {
                // Relation missing or failed, return empty messages to keep page alive
            }

            // FIX: Wrap result in a 'data' key to match Frontend expectations
            return response()->json(['data' => [
                'id' => $ticket->id,
                'ticket_code' => $ticket->ticket_code ?? 'N/A',
                'subject' => $ticket->subject,
                'status' => $ticket->status,
                'priority' => $ticket->priority,
                'category' => $ticket->category,
                'description' => $messages->first()['message'] ?? 'No description',
                'created_at' => $this->safeDate($ticket->created_at),
                'user' => [
                    'name' => $ticket->user?->username ?? 'Unknown',
                    'username' => $ticket->user?->username ?? 'Unknown', // Added for fallback
                    'email' => $ticket->user?->email ?? 'N/A',
                    // Added profile object to prevent "reading 'profile' of undefined"
                    'profile' => [
                        'first_name' => $ticket->user?->profile?->first_name ?? null,
                        'last_name' => $ticket->user?->profile?->last_name ?? null,
                    ]
                ],
                'messages' => $messages
            ]]);

        } catch (\Throwable $e) {
            return response()->json(['message' => 'System Error: ' . $e->getMessage()], 500);
        }
    }

    private function safeDate($date)
    {
        if (empty($date)) return '-';
        try {
            return Carbon::parse($date)->format('d M Y, h:i A');
        } catch (\Throwable $e) {
            return '-';
        }
    }
}