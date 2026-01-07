<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') - PreIPOsip</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .email-container {
            max-width: 600px;
            margin: 20px auto;
            background: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .header p {
            margin: 5px 0 0 0;
            font-size: 14px;
            opacity: 0.9;
        }
        .content {
            padding: 30px;
        }
        .content h2 {
            color: #667eea;
            font-size: 20px;
            margin-top: 0;
        }
        .highlight-box {
            background: #f8f9ff;
            border-left: 4px solid #667eea;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: #667eea;
            color: white !important;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
            font-weight: 500;
        }
        .btn:hover {
            background: #5568d3;
        }
        .footer {
            background: #f8f9fa;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #6c757d;
            border-top: 1px solid #dee2e6;
        }
        .footer a {
            color: #667eea;
            text-decoration: none;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        table td {
            padding: 10px;
            border-bottom: 1px solid #e9ecef;
        }
        table td:first-child {
            font-weight: 600;
            width: 40%;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1>PreIPOsip</h1>
            <p>Investment Platform</p>
        </div>

        <div class="content">
            @yield('content')
        </div>

        <div class="footer">
            <p>© {{ date('Y') }} PreIPOsip Investment Platform. All rights reserved.</p>
            <p>
                <a href="{{ config('app.url') }}">Visit Website</a> |
                <a href="{{ config('app.url') }}/support">Support</a> |
                <a href="{{ config('app.url') }}/faq">FAQ</a>
            </p>
            <p style="margin-top: 15px; font-size: 11px;">
                This is an automated email. Please do not reply to this message.<br>
                If you have questions, contact us at support@preiposip.com
            </p>
        </div>
    </div>
</body>
</html>
