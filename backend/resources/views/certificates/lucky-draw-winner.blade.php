<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Lucky Draw Winner Certificate</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            padding: 50px;
            text-align: center;
        }
        .certificate {
            border: 10px solid #gold;
            padding: 50px;
            background-color: #f9f9f9;
        }
        .header {
            font-size: 36px;
            font-weight: bold;
            color: #333;
            margin-bottom: 20px;
        }
        .subheader {
            font-size: 24px;
            color: #666;
            margin-bottom: 40px;
        }
        .recipient {
            font-size: 32px;
            font-weight: bold;
            color: #000;
            margin: 30px 0;
        }
        .details {
            font-size: 18px;
            margin: 20px 0;
            line-height: 1.8;
        }
        .footer {
            margin-top: 50px;
            font-size: 14px;
            color: #888;
        }
        .signature {
            margin-top: 60px;
            border-top: 2px solid #333;
            display: inline-block;
            padding-top: 10px;
            min-width: 200px;
        }
    </style>
</head>
<body>
    <div class="certificate">
        <div class="header">CERTIFICATE OF ACHIEVEMENT</div>
        <div class="subheader">Lucky Draw Winner</div>

        <p class="details">This is to certify that</p>

        <div class="recipient">{{ $winner->name ?? $winner->username }}</div>

        <p class="details">
            has won <strong>Rank {{ $rank }}</strong> prize<br>
            in the <strong>{{ $draw->name }}</strong><br>
            and has been awarded a prize of<br>
            <strong style="font-size: 28px; color: #28a745;">₹{{ number_format($amount, 2) }}</strong>
        </p>

        <p class="details">
            Date: {{ $date }}
        </p>

        <div class="footer">
            {{ $footer }}
        </div>

        <div class="signature">
            Authorized Signature
        </div>
    </div>
</body>
</html>
