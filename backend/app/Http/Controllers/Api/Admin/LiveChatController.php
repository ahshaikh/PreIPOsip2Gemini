<?php
// V-AUDIT-MODULE14-002 (MEDIUM): Fixed race condition in acceptSession

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChatAgentStatus;
use App\Models\LiveChatMessage;
use App\Models\LiveChatSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LiveChatController extends Controller
{
    /**
     * Get all chat sessions for agent
     * GET /api/v1/admin/live-chat/sessions
     */
    public function sessions(Request $request)
    {
        $query = LiveChatSession::with(['user', 'agent'])
            ->when($request->filled('status'), function ($q) use ($request) {
                $q->where('status', $request->status);
            })
            ->when($request->filled('agent_id'), function ($q) use ($request) {
                if ($request->agent_id === 'me') {
                    $q->where('agent_id', Auth::id());
                } else {
                    $q->where('agent_id', $request->agent_id);
                }
            })
            ->latest();

        $sessions = $query->paginate(15);

        return response()->json($sessions);
    }

    /**
     * Get waiting sessions (queue)
     * GET /api/v1/admin/live-chat/waiting
     */
    public function waitingSessions()
    {
        $sessions = LiveChatSession::waiting()
            ->with('user')
            ->oldest()
            ->get();

        return response()->json(['data' => $sessions]);
    }

    /**
     * Get single session
     * GET /api/v1/admin/live-chat/sessions/{sessionCode}
     */
    public function showSession($sessionCode)
    {
        $session = LiveChatSession::where('session_code', $sessionCode)
            ->with(['user', 'agent', 'messages.sender'])
            ->firstOrFail();

        // Mark agent messages as read
        $session->messages()
            ->where('sender_type', LiveChatMessage::SENDER_TYPE_USER)
            ->unread()
            ->each(function ($message) {
                $message->markAsRead();
            });

        return response()->json(['data' => $session]);
    }

    /**
     * Accept and assign chat to current agent
     * POST /api/v1/admin/live-chat/sessions/{sessionCode}/accept
     * V-AUDIT-MODULE14-002 (MEDIUM): Fixed race condition with lockForUpdate()
     */
    public function acceptSession($sessionCode)
    {
        // V-AUDIT-MODULE14-002: Prevent race condition when multiple admins accept simultaneously
        // Previous Issue: If two admins click "Accept" on the same pending chat simultaneously,
        // both requests might pass the waiting() scope check before the first transaction commits,
        // resulting in double-assignment or errors.
        // Fix: Use lockForUpdate() to acquire an exclusive lock on the row during the transaction.
        // Benefits: Ensures only one agent can accept a specific chat, prevents data corruption.

        return DB::transaction(function () use ($sessionCode) {
            // V-AUDIT-MODULE14-002: lockForUpdate() acquires exclusive row lock
            $session = LiveChatSession::where('session_code', $sessionCode)
                ->waiting()
                ->lockForUpdate()
                ->first();

            if (!$session) {
                return response()->json([
                    'message' => 'Chat not found or already accepted by another agent',
                ], 422);
            }

            $agentStatus = ChatAgentStatus::where('agent_id', Auth::id())->first();

            if (!$agentStatus || !$agentStatus->isAvailable()) {
                return response()->json([
                    'message' => 'You are not available to accept chats',
                ], 422);
            }

            $session->assignAgent(Auth::id());
            $agentStatus->increment('active_chats_count');
            $agentStatus->updateActivity();

            // Send system message
            LiveChatMessage::create([
                'session_id' => $session->id,
                'sender_id' => Auth::id(),
                'sender_type' => LiveChatMessage::SENDER_TYPE_SYSTEM,
                'message' => Auth::user()->username . ' has joined the conversation',
                'type' => LiveChatMessage::TYPE_SYSTEM,
            ]);

            return response()->json([
                'message' => 'Chat accepted successfully',
                'data' => $session->fresh(['user', 'agent']),
            ]);
        });
    }

    /**
     * Send message
     * POST /api/v1/admin/live-chat/sessions/{sessionCode}/messages
     */
    public function sendMessage(Request $request, $sessionCode)
    {
        $session = LiveChatSession::where('session_code', $sessionCode)->firstOrFail();

        // Verify agent is assigned to this session
        if ($session->agent_id !== Auth::id()) {
            return response()->json([
                'message' => 'You are not assigned to this chat',
            ], 403);
        }

        $validated = $request->validate([
            'message' => 'required|string',
            'type' => 'nullable|in:text,file,image',
            'attachments' => 'nullable|array',
        ]);

        $message = LiveChatMessage::create([
            'session_id' => $session->id,
            'sender_id' => Auth::id(),
            'sender_type' => LiveChatMessage::SENDER_TYPE_AGENT,
            'message' => $validated['message'],
            'type' => $validated['type'] ?? LiveChatMessage::TYPE_TEXT,
            'attachments' => $validated['attachments'] ?? null,
        ]);

        // Update agent activity
        ChatAgentStatus::where('agent_id', Auth::id())->first()?->updateActivity();

        return response()->json([
            'message' => 'Message sent successfully',
            'data' => $message->load('sender'),
        ], 201);
    }

    /**
     * Close chat session
     * POST /api/v1/admin/live-chat/sessions/{sessionCode}/close
     */
    public function closeSession($sessionCode)
    {
        $session = LiveChatSession::where('session_code', $sessionCode)->firstOrFail();

        if ($session->agent_id !== Auth::id()) {
            return response()->json([
                'message' => 'You are not assigned to this chat',
            ], 403);
        }

        $session->closeSession('agent', Auth::id());

        // Decrement agent's active chats
        $agentStatus = ChatAgentStatus::where('agent_id', Auth::id())->first();
        if ($agentStatus && $agentStatus->active_chats_count > 0) {
            $agentStatus->decrement('active_chats_count');
        }

        // Send system message
        LiveChatMessage::create([
            'session_id' => $session->id,
            'sender_id' => Auth::id(),
            'sender_type' => LiveChatMessage::SENDER_TYPE_SYSTEM,
            'message' => 'Chat closed by ' . Auth::user()->username,
            'type' => LiveChatMessage::TYPE_SYSTEM,
        ]);

        return response()->json([
            'message' => 'Chat closed successfully',
        ]);
    }

    /**
     * Transfer chat to another agent
     * POST /api/v1/admin/live-chat/sessions/{sessionCode}/transfer
     */
    public function transferSession(Request $request, $sessionCode)
    {
        $session = LiveChatSession::where('session_code', $sessionCode)->firstOrFail();

        $validated = $request->validate([
            'agent_id' => 'required|exists:users,id',
        ]);

        $newAgentStatus = ChatAgentStatus::where('agent_id', $validated['agent_id'])->first();

        if (!$newAgentStatus || !$newAgentStatus->isAvailable()) {
            return response()->json([
                'message' => 'Selected agent is not available',
            ], 422);
        }

        $oldAgentId = $session->agent_id;

        // Update session
        $session->update(['agent_id' => $validated['agent_id']]);

        // Update agent stats
        if ($oldAgentId) {
            ChatAgentStatus::where('agent_id', $oldAgentId)->decrement('active_chats_count');
        }
        $newAgentStatus->increment('active_chats_count');

        // Send system message
        LiveChatMessage::create([
            'session_id' => $session->id,
            'sender_id' => Auth::id(),
            'sender_type' => LiveChatMessage::SENDER_TYPE_SYSTEM,
            'message' => 'Chat transferred to ' . $newAgentStatus->agent->username,
            'type' => LiveChatMessage::TYPE_SYSTEM,
        ]);

        return response()->json([
            'message' => 'Chat transferred successfully',
        ]);
    }

    /**
     * Get agent status
     * GET /api/v1/admin/live-chat/agent/status
     */
    public function agentStatus()
    {
        $status = ChatAgentStatus::firstOrCreate(
            ['agent_id' => Auth::id()],
            [
                'status' => ChatAgentStatus::STATUS_OFFLINE,
                'max_concurrent_chats' => setting('live_chat_max_concurrent_chats', 5),
            ]
        );

        return response()->json(['data' => $status]);
    }

    /**
     * Update agent status
     * PUT /api/v1/admin/live-chat/agent/status
     */
    public function updateAgentStatus(Request $request)
    {
        $validated = $request->validate([
            'status' => 'required|in:online,away,busy,offline',
            'is_accepting_chats' => 'nullable|boolean',
            'max_concurrent_chats' => 'nullable|integer|min:1|max:20',
        ]);

        $status = ChatAgentStatus::updateOrCreate(
            ['agent_id' => Auth::id()],
            array_merge($validated, ['last_activity_at' => now()])
        );

        return response()->json([
            'message' => 'Status updated successfully',
            'data' => $status,
        ]);
    }

    /**
     * Get all available agents
     * GET /api/v1/admin/live-chat/agents
     */
    public function availableAgents()
    {
        $agents = ChatAgentStatus::with('agent')
            ->available()
            ->orderBy('active_chats_count')
            ->get();

        return response()->json(['data' => $agents]);
    }

    /**
     * Get chat statistics
     * GET /api/v1/admin/live-chat/stats
     */
    public function statistics(Request $request)
    {
        $agentId = $request->input('agent_id', Auth::id());
        $days = $request->input('days', 7);

        $stats = [
            'total_chats' => LiveChatSession::forAgent($agentId)->count(),
            'active_chats' => LiveChatSession::forAgent($agentId)->active()->count(),
            'waiting_chats' => LiveChatSession::waiting()->count(),
            'closed_chats_today' => LiveChatSession::forAgent($agentId)
                ->closed()
                ->whereDate('closed_at', today())
                ->count(),
            'average_rating' => LiveChatSession::forAgent($agentId)
                ->whereNotNull('user_rating')
                ->avg('user_rating'),
            'chats_last_period' => LiveChatSession::forAgent($agentId)
                ->where('created_at', '>=', now()->subDays($days))
                ->count(),
        ];

        return response()->json(['data' => $stats]);
    }
}
