<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Wallet Statement</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid #007bff;
        }
        .header h1 {
            color: #1a1a1a;
            margin: 0 0 10px 0;
            font-size: 28px;
        }
        .header p {
            margin: 5px 0;
            color: #6c757d;
            font-size: 14px;
        }
        .user-info {
            background-color: #f8f9fa;
            padding: 15px;
            border-left: 4px solid #007bff;
            margin-bottom: 20px;
        }
        .user-info p {
            margin: 5px 0;
            font-size: 14px;
        }
        .user-info strong {
            color: #495057;
            display: inline-block;
            width: 120px;
        }
        .summary {
            display: table;
            width: 100%;
            margin-bottom: 30px;
        }
        .summary-item {
            display: table-cell;
            text-align: center;
            padding: 15px;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
        }
        .summary-item .label {
            font-size: 12px;
            color: #6c757d;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        .summary-item .value {
            font-size: 20px;
            font-weight: bold;
            color: #007bff;
        }
        .transactions-title {
            font-size: 18px;
            font-weight: bold;
            margin: 30px 0 15px 0;
            color: #1a1a1a;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
            font-size: 12px;
        }
        th {
            background-color: #007bff;
            color: white;
            padding: 10px;
            text-align: left;
            font-weight: bold;
        }
        td {
            padding: 8px 10px;
            border-bottom: 1px solid #dee2e6;
        }
        tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        .amount-credit {
            color: #28a745;
            font-weight: bold;
        }
        .amount-debit {
            color: #dc3545;
            font-weight: bold;
        }
        .no-transactions {
            text-align: center;
            padding: 40px;
            color: #6c757d;
            font-style: italic;
        }
        .footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
            font-size: 11px;
            color: #6c757d;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Wallet Statement</h1>
        <p>PreIPO SIP Platform</p>
    </div>

    <div class="user-info">
        <p><strong>Name:</strong> {{ $user->profile->first_name ?? $user->username }} {{ $user->profile->last_name ?? '' }}</p>
        <p><strong>Email:</strong> {{ $user->email }}</p>
        <p><strong>User ID:</strong> {{ $user->id }}</p>
        <p><strong>Generated On:</strong> {{ $generated_at }}</p>
    </div>

    <div class="summary">
        <div class="summary-item">
            <div class="label">Current Balance</div>
            <div class="value">₹{{ number_format($wallet->balance, 2) }}</div>
        </div>
        <div class="summary-item">
            <div class="label">Locked Balance</div>
            <div class="value">₹{{ number_format($wallet->locked_balance ?? 0, 2) }}</div>
        </div>
        <div class="summary-item">
            <div class="label">Total Transactions</div>
            <div class="value">{{ $transactions->count() }}</div>
        </div>
    </div>

    <div class="transactions-title">Transaction History</div>

    @if($transactions->count() > 0)
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Description</th>
                    <th style="text-align: right;">Debit</th>
                    <th style="text-align: right;">Credit</th>
                    <th style="text-align: right;">Balance</th>
                </tr>
            </thead>
            <tbody>
                @foreach($transactions as $transaction)
                    <tr>
                        <td>{{ $transaction->created_at->format('d/m/Y H:i') }}</td>
                        <td>{{ ucfirst(str_replace('_', ' ', $transaction->type)) }}</td>
                        <td>{{ $transaction->description }}</td>
                        <td style="text-align: right;">
                            @if($transaction->amount < 0)
                                <span class="amount-debit">₹{{ number_format(abs($transaction->amount), 2) }}</span>
                            @else
                                -
                            @endif
                        </td>
                        <td style="text-align: right;">
                            @if($transaction->amount > 0)
                                <span class="amount-credit">₹{{ number_format($transaction->amount, 2) }}</span>
                            @else
                                -
                            @endif
                        </td>
                        <td style="text-align: right;">₹{{ number_format($transaction->balance_after, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="no-transactions">
            No transactions found in your wallet history.
        </div>
    @endif

    <div class="footer">
        <p>
            This statement was generated on {{ $generated_at }}<br>
            For any queries, please contact support@preipo-sip.com<br>
            © {{ now()->year }} PreIPO SIP Platform. All rights reserved.
        </p>
    </div>
</body>
</html>
