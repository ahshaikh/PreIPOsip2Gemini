<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Ticket Transcript - {{ $ticket->ticket_code }}</title>
    <style>
        /* V-AUDIT-MODULE14-RECOMMENDATIONS-C: PDF Styling for Professional Transcript */
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 12px;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 20px;
        }

        .header {
            text-align: center;
            border-bottom: 3px solid #4F46E5;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }

        .header h1 {
            color: #4F46E5;
            margin: 0 0 5px 0;
            font-size: 24px;
        }

        .header .subtitle {
            color: #6B7280;
            font-size: 11px;
            margin: 0;
        }

        .ticket-info {
            background-color: #F9FAFB;
            border: 1px solid #E5E7EB;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 25px;
        }

        .info-row {
            display: table;
            width: 100%;
            margin-bottom: 8px;
        }

        .info-label {
            display: table-cell;
            font-weight: bold;
            color: #6B7280;
            width: 150px;
            padding-right: 10px;
        }

        .info-value {
            display: table-cell;
            color: #1F2937;
        }

        .status-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-open {
            background-color: #DBEAFE;
            color: #1E40AF;
        }

        .status-closed {
            background-color: #D1FAE5;
            color: #065F46;
        }

        .status-resolved {
            background-color: #FEF3C7;
            color: #92400E;
        }

        .priority-high {
            background-color: #FEE2E2;
            color: #991B1B;
        }

        .priority-medium {
            background-color: #FEF3C7;
            color: #92400E;
        }

        .priority-low {
            background-color: #E0E7FF;
            color: #3730A3;
        }

        .messages-section {
            margin-top: 30px;
        }

        .section-title {
            font-size: 16px;
            font-weight: bold;
            color: #1F2937;
            border-bottom: 2px solid #E5E7EB;
            padding-bottom: 8px;
            margin-bottom: 20px;
        }

        .message {
            margin-bottom: 20px;
            page-break-inside: avoid;
        }

        .message-header {
            display: table;
            width: 100%;
            margin-bottom: 8px;
        }

        .message-sender {
            display: table-cell;
            font-weight: bold;
            color: #1F2937;
        }

        .message-sender.admin {
            color: #4F46E5;
        }

        .message-time {
            display: table-cell;
            text-align: right;
            color: #9CA3AF;
            font-size: 10px;
        }

        .message-body {
            background-color: #F9FAFB;
            border-left: 4px solid #E5E7EB;
            padding: 12px;
            margin-top: 5px;
            white-space: pre-wrap;
            word-wrap: break-word;
        }

        .message.admin .message-body {
            border-left-color: #4F46E5;
            background-color: #EEF2FF;
        }

        .footer {
            margin-top: 40px;
            padding-top: 15px;
            border-top: 1px solid #E5E7EB;
            text-align: center;
            color: #9CA3AF;
            font-size: 10px;
        }

        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    {{-- V-AUDIT-MODULE14-RECOMMENDATIONS-C: Header Section --}}
    <div class="header">
        <h1>Support Ticket Transcript</h1>
        <p class="subtitle">Generated on {{ $generated_at }}</p>
    </div>

    {{-- V-AUDIT-MODULE14-RECOMMENDATIONS-C: Ticket Information Section --}}
    <div class="ticket-info">
        <div class="info-row">
            <div class="info-label">Ticket Code:</div>
            <div class="info-value"><strong>{{ $ticket->ticket_code }}</strong></div>
        </div>

        <div class="info-row">
            <div class="info-label">Subject:</div>
            <div class="info-value">{{ $ticket->subject }}</div>
        </div>

        <div class="info-row">
            <div class="info-label">Customer:</div>
            <div class="info-value">
                {{ $user->username ?? 'Unknown' }}
                @if($user->email)
                    ({{ $user->email }})
                @endif
            </div>
        </div>

        <div class="info-row">
            <div class="info-label">Category:</div>
            <div class="info-value">{{ ucfirst($ticket->category) }}</div>
        </div>

        <div class="info-row">
            <div class="info-label">Priority:</div>
            <div class="info-value">
                <span class="status-badge priority-{{ $ticket->priority }}">
                    {{ ucfirst($ticket->priority) }}
                </span>
            </div>
        </div>

        <div class="info-row">
            <div class="info-label">Status:</div>
            <div class="info-value">
                <span class="status-badge status-{{ $ticket->status }}">
                    {{ ucfirst(str_replace('_', ' ', $ticket->status)) }}
                </span>
            </div>
        </div>

        @if($ticket->assignedTo)
        <div class="info-row">
            <div class="info-label">Assigned To:</div>
            <div class="info-value">{{ $ticket->assignedTo->username }}</div>
        </div>
        @endif

        <div class="info-row">
            <div class="info-label">Created:</div>
            <div class="info-value">{{ $ticket->created_at->format('d M Y, h:i A') }}</div>
        </div>

        @if($ticket->resolved_at)
        <div class="info-row">
            <div class="info-label">Resolved:</div>
            <div class="info-value">{{ $ticket->resolved_at->format('d M Y, h:i A') }}</div>
        </div>
        @endif

        @if($ticket->rating)
        <div class="info-row">
            <div class="info-label">Customer Rating:</div>
            <div class="info-value">
                {{ $ticket->rating }}/5 Stars
                @if($ticket->rating_feedback)
                    <br><em style="color: #6B7280; font-size: 10px;">"{{ $ticket->rating_feedback }}"</em>
                @endif
            </div>
        </div>
        @endif
    </div>

    {{-- V-AUDIT-MODULE14-RECOMMENDATIONS-C: Messages/Conversation Section --}}
    <div class="messages-section">
        <div class="section-title">Conversation History</div>

        @forelse($messages as $message)
            <div class="message {{ $message->is_admin_reply ? 'admin' : 'user' }}">
                <div class="message-header">
                    <div class="message-sender {{ $message->is_admin_reply ? 'admin' : '' }}">
                        @if($message->is_admin_reply)
                            🛠 Support Agent: {{ $message->user->username ?? 'System' }}
                        @else
                            👤 Customer: {{ $message->user->username ?? $user->username }}
                        @endif
                    </div>
                    <div class="message-time">
                        {{ $message->created_at->format('d M Y, h:i A') }}
                    </div>
                </div>
                <div class="message-body">{{ $message->message }}</div>

                {{-- V-AUDIT-MODULE14-RECOMMENDATIONS-C: Display attachments if present --}}
                @if($message->attachments && count($message->attachments) > 0)
                    <div style="margin-top: 8px; font-size: 10px; color: #6B7280;">
                        📎 Attachments: {{ count($message->attachments) }} file(s)
                    </div>
                @endif
            </div>
        @empty
            <p style="color: #9CA3AF; text-align: center; padding: 20px;">
                No messages found in this ticket.
            </p>
        @endforelse
    </div>

    {{-- V-AUDIT-MODULE14-RECOMMENDATIONS-C: Footer --}}
    <div class="footer">
        <p>
            This is an official transcript of support ticket {{ $ticket->ticket_code }}.
            <br>
            Generated automatically by PreIPOsip Support System.
            <br>
            For inquiries, please contact support@preiposip.com
        </p>
    </div>
</body>
</html>
