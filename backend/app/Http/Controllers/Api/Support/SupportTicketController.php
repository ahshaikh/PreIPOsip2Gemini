<?php
/**
 * V-AUDIT-REFACTOR-2025 | V-SECURE-ATTACHMENTS | V-CHAT-PAGINATION
 */

namespace App\Http\Controllers\Api\Support;

use App\Http\Controllers\Controller;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SupportTicketController extends Controller
{
    /**
     * Fetch messages with pagination.
     * [AUDIT FIX]: Replaces get() with simplePaginate to handle large chats.
     */
    public function messages(SupportTicket $ticket)
    {
        return $ticket->messages()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->simplePaginate(20); // [AUDIT FIX]: Chat Pagination
    }

    /**
     * View chat attachment via Signed URL.
     * [AUDIT FIX]: Prevents public access to sensitive screenshots.
     */
    public function viewAttachment(SupportMessage $message)
    {
        if (!$message->attachment_path || !Storage::disk('private')->exists($message->attachment_path)) {
            return response()->json(['message' => 'File not found.'], 404);
        }

        // Generate 5-minute temporary link
        $url = Storage::disk('private')->temporaryUrl($message->attachment_path, now()->addMinutes(5));

        return response()->json(['url' => $url]);
    }
}