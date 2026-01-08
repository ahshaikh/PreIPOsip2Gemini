<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction Statement - {{ $user->name }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 10px;
            line-height: 1.4;
            color: #333;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #2563eb;
            padding-bottom: 15px;
        }
        .header h1 {
            font-size: 20px;
            color: #2563eb;
            margin-bottom: 5px;
        }
        .header p {
            font-size: 9px;
            color: #666;
        }
        .statement-info {
            background: #f3f4f6;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .statement-info table {
            width: 100%;
        }
        .statement-info td {
            padding: 3px 0;
        }
        .statement-info strong {
            color: #1f2937;
        }
        .user-info {
            margin-bottom: 20px;
            padding: 10px;
            background: #fefce8;
            border-left: 3px solid #eab308;
        }
        .section-title {
            font-size: 12px;
            font-weight: bold;
            color: #1f2937;
            margin: 20px 0 10px 0;
            padding-bottom: 5px;
            border-bottom: 1px solid #d1d5db;
        }
        table.transactions {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        table.transactions th {
            background: #2563eb;
            color: white;
            padding: 8px;
            text-align: left;
            font-size: 9px;
            font-weight: bold;
        }
        table.transactions td {
            padding: 6px 8px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 9px;
        }
        table.transactions tr:nth-child(even) {
            background: #f9fafb;
        }
        .amount-positive {
            color: #059669;
            font-weight: bold;
        }
        .amount-negative {
            color: #dc2626;
            font-weight: bold;
        }
        .summary-box {
            background: #eff6ff;
            border: 1px solid #2563eb;
            border-radius: 5px;
            padding: 15px;
            margin-top: 20px;
        }
        .summary-table {
            width: 100%;
        }
        .summary-table td {
            padding: 5px 0;
            font-size: 10px;
        }
        .summary-table .label {
            font-weight: bold;
            color: #1f2937;
        }
        .summary-table .value {
            text-align: right;
            font-weight: bold;
        }
        .summary-table .total {
            border-top: 2px solid #2563eb;
            padding-top: 8px;
            font-size: 11px;
            color: #2563eb;
        }
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #d1d5db;
            font-size: 8px;
            color: #6b7280;
            text-align: center;
        }
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 80px;
            color: rgba(0, 0, 0, 0.05);
            z-index: -1;
        }
    </style>
</head>
<body>
    <div class="watermark">PREIPOPSIP</div>

    <div class="header">
        <h1>PreIPOsip Investment Platform</h1>
        <p>Transaction Statement</p>
    </div>

    <div class="statement-info">
        <table>
            <tr>
                <td><strong>Statement Number:</strong> {{ $statement_number }}</td>
                <td style="text-align: right;"><strong>Generated:</strong> {{ $generated_at->format('d M Y, h:i A') }}</td>
            </tr>
            <tr>
                <td><strong>Period:</strong> {{ $start_date->format('d M Y') }} to {{ $end_date->format('d M Y') }}</td>
                <td style="text-align: right;"><strong>Total Days:</strong> {{ $start_date->diffInDays($end_date) + 1 }} days</td>
            </tr>
        </table>
    </div>

    <div class="user-info">
        <strong>Account Holder:</strong> {{ $user->name }}<br>
        <strong>Email:</strong> {{ $user->email }}<br>
        <strong>User ID:</strong> {{ str_pad($user->id, 6, '0', STR_PAD_LEFT) }}<br>
        @if($user->phone)
        <strong>Phone:</strong> {{ $user->phone }}<br>
        @endif
    </div>

    @if(isset($payments) && $payments->count() > 0)
    <div class="section-title">Payments ({{ $payments->count() }})</div>
    <table class="transactions">
        <thead>
            <tr>
                <th>Date</th>
                <th>Description</th>
                <th>Reference</th>
                <th>Status</th>
                <th style="text-align: right;">Amount (₹)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($payments as $payment)
            <tr>
                <td>{{ $payment['date']->format('d M Y') }}</td>
                <td>{{ $payment['description'] }}</td>
                <td>{{ $payment['reference'] }}</td>
                <td>{{ ucfirst($payment['status']) }}</td>
                <td style="text-align: right;" class="amount-positive">{{ number_format($payment['amount'], 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    @if(isset($withdrawals) && $withdrawals->count() > 0)
    <div class="section-title">Withdrawals ({{ $withdrawals->count() }})</div>
    <table class="transactions">
        <thead>
            <tr>
                <th>Date</th>
                <th>Description</th>
                <th>Reference</th>
                <th>Status</th>
                <th style="text-align: right;">Amount (₹)</th>
                <th style="text-align: right;">Fee (₹)</th>
                <th style="text-align: right;">Net (₹)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($withdrawals as $withdrawal)
            <tr>
                <td>{{ $withdrawal['date']->format('d M Y') }}</td>
                <td>{{ $withdrawal['description'] }}</td>
                <td>{{ $withdrawal['reference'] }}</td>
                <td>{{ ucfirst($withdrawal['status']) }}</td>
                <td style="text-align: right;" class="amount-negative">{{ number_format(abs($withdrawal['amount']), 2) }}</td>
                <td style="text-align: right;">{{ number_format($withdrawal['fee'], 2) }}</td>
                <td style="text-align: right;" class="amount-negative">{{ number_format(abs($withdrawal['net_amount']), 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    @if(isset($investments) && $investments->count() > 0)
    <div class="section-title">Investments ({{ $investments->count() }})</div>
    <table class="transactions">
        <thead>
            <tr>
                <th>Date</th>
                <th>Product & Plan</th>
                <th>Reference</th>
                <th style="text-align: right;">Shares</th>
                <th style="text-align: right;">Price/Share (₹)</th>
                <th style="text-align: right;">Amount (₹)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($investments as $investment)
            <tr>
                <td>{{ $investment['date']->format('d M Y') }}</td>
                <td>{{ $investment['description'] }}</td>
                <td>{{ $investment['reference'] }}</td>
                <td style="text-align: right;">{{ $investment['shares'] }}</td>
                <td style="text-align: right;">{{ number_format($investment['price_per_share'], 2) }}</td>
                <td style="text-align: right;">{{ number_format($investment['amount'], 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    @if(isset($bonuses) && $bonuses->count() > 0)
    <div class="section-title">Bonuses ({{ $bonuses->count() }})</div>
    <table class="transactions">
        <thead>
            <tr>
                <th>Date</th>
                <th>Type</th>
                <th>Reference</th>
                <th>Status</th>
                <th style="text-align: right;">Amount (₹)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($bonuses as $bonus)
            <tr>
                <td>{{ $bonus['date']->format('d M Y') }}</td>
                <td>{{ $bonus['description'] }}</td>
                <td>{{ $bonus['reference'] }}</td>
                <td>{{ ucfirst($bonus['status']) }}</td>
                <td style="text-align: right;" class="amount-positive">{{ number_format($bonus['amount'], 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <div class="summary-box">
        <strong style="display: block; margin-bottom: 10px; font-size: 11px;">Statement Summary</strong>
        <table class="summary-table">
            <tr>
                <td class="label">Total Payments</td>
                <td class="value amount-positive">₹ {{ number_format($summary['total_payments'], 2) }}</td>
            </tr>
            <tr>
                <td class="label">Total Bonuses</td>
                <td class="value amount-positive">₹ {{ number_format($summary['total_bonuses'], 2) }}</td>
            </tr>
            <tr>
                <td class="label">Total Withdrawals</td>
                <td class="value amount-negative">₹ {{ number_format($summary['total_withdrawals'], 2) }}</td>
            </tr>
            <tr>
                <td class="label">Total Investments</td>
                <td class="value">₹ {{ number_format($summary['total_investments'], 2) }}</td>
            </tr>
            <tr class="total">
                <td class="label">Net Cash Flow</td>
                <td class="value">₹ {{ number_format($summary['net_cash_flow'], 2) }}</td>
            </tr>
        </table>
    </div>

    <div class="footer">
        <p><strong>This is a computer-generated statement and does not require a signature.</strong></p>
        <p>For any queries, please contact support@preiposip.com | Visit: www.preiposip.com</p>
        <p style="margin-top: 10px;">© {{ now()->year }} PreIPOsip Investment Platform. All rights reserved.</p>
        <p style="margin-top: 5px; font-size: 7px;">This statement is confidential and intended solely for the use of the account holder.</p>
    </div>
</body>
</html>
