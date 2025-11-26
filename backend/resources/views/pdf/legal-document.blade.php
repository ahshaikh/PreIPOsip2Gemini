<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $document->title }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        h1 {
            color: #1a1a1a;
            border-bottom: 3px solid #007bff;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .document-meta {
            background-color: #f8f9fa;
            padding: 15px;
            border-left: 4px solid #007bff;
            margin-bottom: 30px;
        }
        .document-meta p {
            margin: 5px 0;
            font-size: 14px;
        }
        .document-meta strong {
            color: #495057;
        }
        .document-content {
            white-space: pre-wrap;
            font-size: 14px;
        }
        .footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
            font-size: 12px;
            color: #6c757d;
            text-align: center;
        }
    </style>
</head>
<body>
    <h1>{{ $document->title }}</h1>

    <div class="document-meta">
        <p><strong>Document Type:</strong> {{ ucfirst(str_replace('_', ' ', $document->type)) }}</p>
        <p><strong>Version:</strong> {{ $document->version }}</p>
        <p><strong>Status:</strong> {{ ucfirst($document->status) }}</p>
        @if($document->effective_date)
            <p><strong>Effective Date:</strong> {{ \Carbon\Carbon::parse($document->effective_date)->format('F d, Y') }}</p>
        @endif
        <p><strong>Last Updated:</strong> {{ $document->updated_at->format('F d, Y h:i A') }}</p>
    </div>

    @if($document->description)
        <p><em>{{ $document->description }}</em></p>
    @endif

    <div class="document-content">
        {{ $document->content }}
    </div>

    <div class="footer">
        <p>
            This document was generated on {{ now()->format('F d, Y h:i A') }}<br>
            © {{ now()->year }} PreIPO SIP Platform. All rights reserved.
        </p>
    </div>
</body>
</html>
