<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\ChatAgentStatus;
use App\Models\LiveChatMessage;
use App\Models\LiveChatSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LiveChatController extends Controller
{
    /**
     * Check if live chat is available
     * GET /api/v1/live-chat/availability
     */
    public function checkAvailability()
    {
        $isEnabled = setting('live_chat_enabled', false);
        $onlineStatus = setting('live_chat_online_status', 'auto');

        $isAvailable = false;
        $message = '';

        if (!$isEnabled) {
            $message = 'Live chat is currently disabled';
        } elseif ($onlineStatus === 'offline') {
            $message = setting('live_chat_offline_message', 'Our support team is currently offline.');
        } elseif ($onlineStatus === 'online') {
            $isAvailable = true;
            $message = setting('live_chat_welcome_message', 'Hello! How can we help you today?');
        } else { // auto
            $availableAgents = ChatAgentStatus::available()->count();
            $isAvailable = $availableAgents > 0;
            $message = $isAvailable
                ? setting('live_chat_welcome_message', 'Hello! How can we help you today?')
                : setting('live_chat_offline_message', 'Our support team is currently offline.');
        }

        return response()->json([
            'is_available' => $isAvailable,
            'message' => $message,
            'office_hours' => [
                'start' => setting('live_chat_office_hours_start', '09:00'),
                'end' => setting('live_chat_office_hours_end', '18:00'),
            ],
        ]);
    }

    /**
     * Get user's chat sessions
     * GET /api/v1/live-chat/sessions
     */
    public function mySessions()
    {
        $sessions = LiveChatSession::where('user_id', Auth::id())
            ->with(['agent', 'messages' => function ($query) {
                $query->latest()->limit(1);
            }])
            ->latest()
            ->get();

        return response()->json(['data' => $sessions]);
    }

    /**
     * Get active session
     * GET /api/v1/live-chat/active-session
     */
    public function activeSession()
    {
        $session = LiveChatSession::where('user_id', Auth::id())
            ->whereIn('status', [LiveChatSession::STATUS_WAITING, LiveChatSession::STATUS_ACTIVE])
            ->with(['agent', 'messages.sender'])
            ->first();

        if ($session) {
            // Mark user messages as read
            $session->messages()
                ->where('sender_type', LiveChatMessage::SENDER_TYPE_AGENT)
                ->unread()
                ->each(function ($message) {
                    $message->markAsRead();
                });
        }

        return response()->json(['data' => $session]);
    }

    /**
     * Start new chat session
     * POST /api/v1/live-chat/sessions
     */
    public function startSession(Request $request)
    {
        // Check if user already has an active session
        $existingSession = LiveChatSession::where('user_id', Auth::id())
            ->whereIn('status', [LiveChatSession::STATUS_WAITING, LiveChatSession::STATUS_ACTIVE])
            ->first();

        if ($existingSession) {
            return response()->json([
                'message' => 'You already have an active chat session',
                'data' => $existingSession,
            ], 422);
        }

        $validated = $request->validate([
            'subject' => 'nullable|string|max:255',
            'initial_message' => 'required|string',
        ]);

        // Create session
        $session = LiveChatSession::create([
            'user_id' => Auth::id(),
            'subject' => $validated['subject'] ?? 'General Inquiry',
            'initial_message' => $validated['initial_message'],
            'status' => LiveChatSession::STATUS_WAITING,
        ]);

        // Create first message
        LiveChatMessage::create([
            'session_id' => $session->id,
            'sender_id' => Auth::id(),
            'sender_type' => LiveChatMessage::SENDER_TYPE_USER,
            'message' => $validated['initial_message'],
            'type' => LiveChatMessage::TYPE_TEXT,
        ]);

        // Auto-assign if enabled
        if (setting('live_chat_auto_assign', true)) {
            $this->autoAssignAgent($session);
        }

        return response()->json([
            'message' => 'Chat session started successfully',
            'data' => $session->fresh(['agent', 'messages']),
        ], 201);
    }

    /**
     * Send message in chat
     * POST /api/v1/live-chat/sessions/{sessionCode}/messages
     */
    public function sendMessage(Request $request, $sessionCode)
    {
        $session = LiveChatSession::where('session_code', $sessionCode)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        if ($session->isClosed()) {
            return response()->json([
                'message' => 'This chat session is closed',
            ], 422);
        }

        $validated = $request->validate([
            'message' => 'required|string',
            'type' => 'nullable|in:text,file,image',
            'attachments' => 'nullable|array',
        ]);

        $message = LiveChatMessage::create([
            'session_id' => $session->id,
            'sender_id' => Auth::id(),
            'sender_type' => LiveChatMessage::SENDER_TYPE_USER,
            'message' => $validated['message'],
            'type' => $validated['type'] ?? LiveChatMessage::TYPE_TEXT,
            'attachments' => $validated['attachments'] ?? null,
        ]);

        return response()->json([
            'message' => 'Message sent successfully',
            'data' => $message->load('sender'),
        ], 201);
    }

    /**
     * Get messages for a session
     * GET /api/v1/live-chat/sessions/{sessionCode}/messages
     */
    public function getMessages($sessionCode)
    {
        $session = LiveChatSession::where('session_code', $sessionCode)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $messages = $session->messages()
            ->with('sender')
            ->orderBy('created_at', 'asc')
            ->get();

        // Mark agent messages as read
        $session->messages()
            ->where('sender_type', LiveChatMessage::SENDER_TYPE_AGENT)
            ->unread()
            ->each(function ($message) {
                $message->markAsRead();
            });

        return response()->json(['data' => $messages]);
    }

    /**
     * Close chat session
     * POST /api/v1/live-chat/sessions/{sessionCode}/close
     */
    public function closeSession($sessionCode)
    {
        $session = LiveChatSession::where('session_code', $sessionCode)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        if ($session->isClosed()) {
            return response()->json([
                'message' => 'This chat session is already closed',
            ], 422);
        }

        $session->closeSession('user', Auth::id());

        // Decrement agent's active chats if assigned
        if ($session->agent_id) {
            $agentStatus = ChatAgentStatus::where('agent_id', $session->agent_id)->first();
            if ($agentStatus && $agentStatus->active_chats_count > 0) {
                $agentStatus->decrement('active_chats_count');
            }
        }

        return response()->json([
            'message' => 'Chat closed successfully',
        ]);
    }

    /**
     * Rate chat session
     * POST /api/v1/live-chat/sessions/{sessionCode}/rate
     */
    public function rateSession(Request $request, $sessionCode)
    {
        $session = LiveChatSession::where('session_code', $sessionCode)
            ->where('user_id', Auth::id())
            ->closed()
            ->firstOrFail();

        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'feedback' => 'nullable|string|max:1000',
        ]);

        $session->update([
            'user_rating' => $validated['rating'],
            'user_feedback' => $validated['feedback'] ?? null,
        ]);

        return response()->json([
            'message' => 'Thank you for your feedback!',
        ]);
    }

    /**
     * Auto-assign agent to session
     */
    protected function autoAssignAgent(LiveChatSession $session)
    {
        $agent = ChatAgentStatus::getLeastBusyAgent();

        if ($agent) {
            $session->assignAgent($agent->agent_id);
            $agent->increment('active_chats_count');
            $agent->updateActivity();

            // Send system message
            LiveChatMessage::create([
                'session_id' => $session->id,
                'sender_id' => $agent->agent_id,
                'sender_type' => LiveChatMessage::SENDER_TYPE_SYSTEM,
                'message' => $agent->agent->username . ' has joined the conversation',
                'type' => LiveChatMessage::TYPE_SYSTEM,
            ]);
        }
    }

    /**
     * Get chat transcript
     * GET /api/v1/live-chat/sessions/{sessionCode}/transcript
     */
    public function getTranscript($sessionCode)
    {
        $session = LiveChatSession::where('session_code', $sessionCode)
            ->where('user_id', Auth::id())
            ->with(['agent', 'messages.sender'])
            ->firstOrFail();

        return response()->json([
            'session' => [
                'session_code' => $session->session_code,
                'subject' => $session->subject,
                'agent' => $session->agent?->username,
                'started_at' => $session->started_at,
                'closed_at' => $session->closed_at,
                'status' => $session->status,
            ],
            'messages' => $session->messages->map(function ($message) {
                return [
                    'sender' => $message->sender?->username ?? 'System',
                    'sender_type' => $message->sender_type,
                    'message' => $message->message,
                    'created_at' => $message->created_at,
                ];
            }),
        ]);
    }
}
