<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Payment Receipt #{{ $payment->id }}</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; color: #333; line-height: 1.6; }
        .container { width: 100%; max-width: 800px; margin: 0 auto; }
        .header { border-bottom: 2px solid #eee; padding-bottom: 20px; margin-bottom: 20px; }
        .logo { font-size: 24px; font-weight: bold; color: #1E40AF; }
        .invoice-info { text-align: right; float: right; }
        .client-info { margin-bottom: 30px; }
        .table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .table th, .table td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        .table th { background-color: #f8f9fa; }
        .total { text-align: right; font-size: 18px; font-weight: bold; }
        .footer { margin-top: 50px; text-align: center; font-size: 12px; color: #777; }
        .badge { background: #d1fae5; color: #065f46; padding: 4px 8px; border-radius: 4px; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="invoice-info">
                <strong>Receipt #:</strong> REC-{{ str_pad($payment->id, 6, '0', STR_PAD_LEFT) }}<br>
                <strong>Date:</strong> {{ $payment->created_at->format('d M, Y') }}<br>
                <strong>Status:</strong> <span class="badge">{{ strtoupper($payment->status) }}</span>
            </div>
            <div class="logo">{{ $company['name'] }}</div>
            <div>{{ $company['address'] }}</div>
            <div>GSTIN: {{ $company['gst'] }}</div>
        </div>

        <div class="client-info">
            <strong>Bill To:</strong><br>
            {{ $user->profile->first_name ?? $user->username }} {{ $user->profile->last_name ?? '' }}<br>
            {{ $user->email }}<br>
            {{ $user->profile->address ?? '' }}
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th>Description</th>
                    <th>Plan Code</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        SIP Installment for {{ $payment->created_at->format('F Y') }}<br>
                        <small>Plan: {{ $plan->name }}</small>
                    </td>
                    <td>{{ $plan->slug }}</td>
                    <td>Rs. {{ number_format($payment->amount, 2) }}</td>
                </tr>
            </tbody>
        </table>

        <div class="total">
            Total Paid: Rs. {{ number_format($payment->amount, 2) }}
        </div>

        <div class="footer">
            <p>This is a computer-generated receipt and does not require a physical signature.</p>
            <p>Thank you for investing with {{ $company['name'] }}.</p>
            <p>{{ $company['website'] }}</p>
        </div>
    </div>
</body>
</html>