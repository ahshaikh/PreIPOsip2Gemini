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

// V-AUDIT-MODULE14-RECOMMENDATIONS-C: PDF generation for ticket transcripts
use Barryvdh\DomPDF\Facade\Pdf;

// V-AUDIT-MODULE14-RECOMMENDATIONS-D: Analytics and aggregations
use Illuminate\Support\Facades\DB;

class SupportTicketController extends Controller
{
    /**
     * List Support Tickets
     * Endpoint: GET /api/v1/admin/support-tickets
     * V-AUDIT-MODULE13-003 (HIGH): Restored eager loading to fix N+1 query issue
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // 1. Start Query
            // V-AUDIT-MODULE13-003: Previous Issue - Removed with() to prevent crashes, causing N+1
            // (10 tickets = 11 queries: 1 for tickets + 10 for users + 10 for assignees).
            // Fix: Restore eager loading. Ensure SupportTicket model defines relationships properly.
            // Benefits: Reduces queries from 21 to 3 (tickets, users, assignees), drastically improving performance.
            $query = SupportTicket::with(['user.profile', 'assignedTo']);

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
            $perPage = (int) setting('records_per_page', 15);
            $tickets = $query->latest()->paginate($perPage);

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
     * V-AUDIT-MODULE13-003 (HIGH): Restored eager loading to fix N+1 query issue
     */
    public function show($id): JsonResponse
    {
        try {
            // V-AUDIT-MODULE13-003: Previous Issue - Removed eager loading causing N+1 queries
            // when loading ticket with messages (1 ticket + N messages + N message users).
            // Fix: Restore with(['user.profile', 'messages.user']) to load all data in 3 queries.
            // Benefits: Prevents N+1, improves response time significantly.
            $ticket = SupportTicket::with(['user.profile', 'messages.user'])->find($id);

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

    /**
     * V-AUDIT-MODULE14-RECOMMENDATIONS-C: Export Ticket Transcript as PDF
     *
     * Endpoint: GET /api/v1/admin/support-tickets/{id}/export-transcript
     *
     * Purpose: Generate a comprehensive PDF transcript of the entire ticket conversation
     * including ticket details, all messages, timestamps, and user information.
     *
     * Benefits:
     * - Compliance: Store permanent records of customer interactions
     * - Documentation: Share ticket history with stakeholders
     * - Legal: Maintain audit trail for dispute resolution
     * - Customer Service: Email transcript to customer after resolution
     *
     * Implementation Details:
     * - Uses barryvdh/laravel-dompdf package (already installed in composer.json)
     * - Generates professionally formatted PDF with ticket metadata
     * - Includes all messages chronologically with sender identification
     * - Returns PDF as downloadable file with ticket code in filename
     * - Uses eager loading to prevent N+1 queries
     *
     * @param int $id Ticket ID
     * @return \Illuminate\Http\Response PDF download response
     */
    public function exportTranscript($id)
    {
        try {
            // V-AUDIT-MODULE14-RECOMMENDATIONS-C: Load ticket with all related data in single query
            // Eager load user, messages, and message senders to avoid N+1 queries
            $ticket = SupportTicket::with([
                'user.profile',
                'assignedTo',
                'resolvedBy',
                'messages.user'
            ])->find($id);

            if (!$ticket) {
                return response()->json(['message' => 'Ticket not found'], 404);
            }

            // V-AUDIT-MODULE14-RECOMMENDATIONS-C: Prepare data for PDF view
            // Structure data in a format optimized for PDF rendering
            $data = [
                'ticket' => $ticket,
                'user' => $ticket->user,
                'messages' => $ticket->messages,
                'generated_at' => now()->format('d M Y, h:i A'),
            ];

            // V-AUDIT-MODULE14-RECOMMENDATIONS-C: Generate PDF using DomPDF
            // The view 'pdfs.ticket-transcript' should be created at resources/views/pdfs/ticket-transcript.blade.php
            // PDF configuration: A4 size, portrait orientation
            $pdf = Pdf::loadView('pdfs.ticket-transcript', $data)
                ->setPaper('a4', 'portrait')
                ->setOption('margin-top', 10)
                ->setOption('margin-bottom', 10)
                ->setOption('margin-left', 10)
                ->setOption('margin-right', 10);

            // V-AUDIT-MODULE14-RECOMMENDATIONS-C: Return PDF as download
            // Filename format: ticket-transcript-TKT-XXXXXXXX.pdf
            $filename = 'ticket-transcript-' . $ticket->ticket_code . '.pdf';

            return $pdf->download($filename);

        } catch (\Throwable $e) {
            Log::error("Export Transcript Failed for Ticket ID {$id}: " . $e->getMessage());
            return response()->json([
                'message' => 'Failed to generate transcript: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * V-AUDIT-MODULE14-RECOMMENDATIONS-D: Support Ticket Analytics Dashboard
     *
     * Endpoint: GET /api/v1/admin/support-tickets/analytics
     *
     * Purpose: Provide comprehensive analytics for support ticket performance monitoring.
     * Helps admins understand support team efficiency, customer satisfaction, and workload distribution.
     *
     * Metrics Calculated:
     * 1. Average Response Time: Time between ticket creation and first admin reply
     * 2. Average Resolution Time: Time between ticket creation and resolution
     * 3. Customer Satisfaction: Average rating from resolved tickets
     * 4. Tickets by Category: Distribution across technical, payment, KYC, etc.
     * 5. Tickets by Priority: Breakdown of low, medium, high priority
     * 6. Tickets by Status: Current status distribution
     * 7. Peak Hours: Hour-by-hour ticket creation analysis
     * 8. Agent Performance: Individual agent statistics (tickets handled, avg resolution time)
     *
     * Benefits:
     * - Identify bottlenecks in support workflow
     * - Optimize agent allocation based on peak hours
     * - Track customer satisfaction trends
     * - Make data-driven decisions for support improvements
     *
     * Query Parameters:
     * - date_from: Start date for analytics (default: 30 days ago)
     * - date_to: End date for analytics (default: now)
     * - agent_id: Filter analytics for specific agent
     *
     * @param Request $request
     * @return JsonResponse Analytics data
     */
    public function analytics(Request $request): JsonResponse
    {
        try {
            // V-AUDIT-MODULE14-RECOMMENDATIONS-D: Parse date filters with defaults
            // Default to last 30 days if no date range specified
            $dateFrom = $request->input('date_from', now()->subDays(30)->startOfDay());
            $dateTo = $request->input('date_to', now()->endOfDay());
            $agentId = $request->input('agent_id'); // Optional agent filter

            // V-AUDIT-MODULE14-RECOMMENDATIONS-D: Base query with date filters
            $query = SupportTicket::whereBetween('created_at', [$dateFrom, $dateTo]);

            if ($agentId) {
                $query->where('assigned_to', $agentId);
            }

            // V-AUDIT-MODULE14-RECOMMENDATIONS-D: 1. Average Response Time
            // Calculate time between ticket creation and first admin reply
            // Uses subquery to find first admin message timestamp for each ticket
            $avgResponseTime = DB::table('support_tickets')
                ->join('support_messages', 'support_tickets.id', '=', 'support_messages.support_ticket_id')
                ->whereBetween('support_tickets.created_at', [$dateFrom, $dateTo])
                ->where('support_messages.is_admin_reply', true)
                ->when($agentId, function($q) use ($agentId) {
                    return $q->where('support_tickets.assigned_to', $agentId);
                })
                ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, support_tickets.created_at, support_messages.created_at)) as avg_seconds')
                ->whereRaw('support_messages.id = (
                    SELECT MIN(id)
                    FROM support_messages
                    WHERE support_ticket_id = support_tickets.id
                    AND is_admin_reply = true
                )')
                ->value('avg_seconds');

            // Convert seconds to hours (rounded to 2 decimals)
            $avgResponseTimeHours = $avgResponseTime ? round($avgResponseTime / 3600, 2) : 0;

            // V-AUDIT-MODULE14-RECOMMENDATIONS-D: 2. Average Resolution Time
            // Calculate time between ticket creation and resolution
            $avgResolutionTime = SupportTicket::whereBetween('created_at', [$dateFrom, $dateTo])
                ->whereNotNull('resolved_at')
                ->when($agentId, function($q) use ($agentId) {
                    return $q->where('assigned_to', $agentId);
                })
                ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, created_at, resolved_at)) as avg_seconds')
                ->value('avg_seconds');

            $avgResolutionTimeHours = $avgResolutionTime ? round($avgResolutionTime / 3600, 2) : 0;

            // V-AUDIT-MODULE14-RECOMMENDATIONS-D: 3. Customer Satisfaction Score
            // Average rating from tickets that have been rated (1-5 scale)
            $satisfactionScore = SupportTicket::whereBetween('created_at', [$dateFrom, $dateTo])
                ->whereNotNull('rating')
                ->when($agentId, function($q) use ($agentId) {
                    return $q->where('assigned_to', $agentId);
                })
                ->avg('rating');

            $satisfactionScore = $satisfactionScore ? round($satisfactionScore, 2) : 0;

            // Count of rated tickets
            $ratedTicketsCount = SupportTicket::whereBetween('created_at', [$dateFrom, $dateTo])
                ->whereNotNull('rating')
                ->when($agentId, function($q) use ($agentId) {
                    return $q->where('assigned_to', $agentId);
                })
                ->count();

            // V-AUDIT-MODULE14-RECOMMENDATIONS-D: 4. Tickets by Category
            // Distribution of tickets across different categories
            $ticketsByCategory = SupportTicket::whereBetween('created_at', [$dateFrom, $dateTo])
                ->when($agentId, function($q) use ($agentId) {
                    return $q->where('assigned_to', $agentId);
                })
                ->select('category', DB::raw('count(*) as count'))
                ->groupBy('category')
                ->get()
                ->pluck('count', 'category');

            // V-AUDIT-MODULE14-RECOMMENDATIONS-D: 5. Tickets by Priority
            // Distribution of tickets by priority level
            $ticketsByPriority = SupportTicket::whereBetween('created_at', [$dateFrom, $dateTo])
                ->when($agentId, function($q) use ($agentId) {
                    return $q->where('assigned_to', $agentId);
                })
                ->select('priority', DB::raw('count(*) as count'))
                ->groupBy('priority')
                ->get()
                ->pluck('count', 'priority');

            // V-AUDIT-MODULE14-RECOMMENDATIONS-D: 6. Tickets by Status
            // Current status distribution
            $ticketsByStatus = SupportTicket::whereBetween('created_at', [$dateFrom, $dateTo])
                ->when($agentId, function($q) use ($agentId) {
                    return $q->where('assigned_to', $agentId);
                })
                ->select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->get()
                ->pluck('count', 'status');

            // V-AUDIT-MODULE14-RECOMMENDATIONS-D: 7. Peak Hours Analysis
            // Hour-by-hour breakdown of ticket creation (0-23 hours)
            $peakHours = SupportTicket::whereBetween('created_at', [$dateFrom, $dateTo])
                ->when($agentId, function($q) use ($agentId) {
                    return $q->where('assigned_to', $agentId);
                })
                ->selectRaw('HOUR(created_at) as hour, count(*) as count')
                ->groupBy('hour')
                ->orderBy('hour')
                ->get()
                ->pluck('count', 'hour');

            // V-AUDIT-MODULE14-RECOMMENDATIONS-D: 8. Agent Performance
            // Per-agent statistics if no specific agent filter applied
            $agentPerformance = [];
            if (!$agentId) {
                $agentPerformance = SupportTicket::whereBetween('created_at', [$dateFrom, $dateTo])
                    ->whereNotNull('assigned_to')
                    ->with('assignedTo:id,username')
                    ->select(
                        'assigned_to',
                        DB::raw('count(*) as tickets_handled'),
                        DB::raw('AVG(CASE WHEN resolved_at IS NOT NULL THEN TIMESTAMPDIFF(SECOND, created_at, resolved_at) END) as avg_resolution_seconds'),
                        DB::raw('AVG(rating) as avg_rating')
                    )
                    ->groupBy('assigned_to')
                    ->get()
                    ->map(function($stat) {
                        return [
                            'agent_id' => $stat->assigned_to,
                            'agent_name' => $stat->assignedTo?->username ?? 'Unknown',
                            'tickets_handled' => $stat->tickets_handled,
                            'avg_resolution_time_hours' => $stat->avg_resolution_seconds ? round($stat->avg_resolution_seconds / 3600, 2) : 0,
                            'avg_rating' => $stat->avg_rating ? round($stat->avg_rating, 2) : 0,
                        ];
                    });
            }

            // V-AUDIT-MODULE14-RECOMMENDATIONS-D: Total tickets in period
            $totalTickets = $query->count();

            // V-AUDIT-MODULE14-RECOMMENDATIONS-D: Return comprehensive analytics
            return response()->json([
                'period' => [
                    'from' => Carbon::parse($dateFrom)->format('Y-m-d'),
                    'to' => Carbon::parse($dateTo)->format('Y-m-d'),
                ],
                'summary' => [
                    'total_tickets' => $totalTickets,
                    'avg_response_time_hours' => $avgResponseTimeHours,
                    'avg_resolution_time_hours' => $avgResolutionTimeHours,
                    'satisfaction_score' => $satisfactionScore,
                    'rated_tickets_count' => $ratedTicketsCount,
                ],
                'distribution' => [
                    'by_category' => $ticketsByCategory,
                    'by_priority' => $ticketsByPriority,
                    'by_status' => $ticketsByStatus,
                ],
                'peak_hours' => $peakHours,
                'agent_performance' => $agentPerformance,
            ]);

        } catch (\Throwable $e) {
            Log::error("Support Analytics Failed: " . $e->getMessage());
            return response()->json([
                'message' => 'Failed to generate analytics: ' . $e->getMessage()
            ], 500);
        }
    }
}