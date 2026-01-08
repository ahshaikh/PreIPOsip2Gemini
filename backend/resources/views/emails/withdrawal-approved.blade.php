@extends('emails.layout')

@section('title', 'Withdrawal Approved')

@section('content')
<h2>✅ Withdrawal Request Approved</h2>

<p>Hello {{ $name }},</p>

<p>Good news! Your withdrawal request has been approved and the funds are being processed.</p>

<div class="highlight-box">
    <table>
        <tr>
            <td>Amount:</td>
            <td><strong>₹{{ number_format($amount, 2) }}</strong></td>
        </tr>
        <tr>
            <td>Reference Number:</td>
            <td><strong>{{ $reference }}</strong></td>
        </tr>
        <tr>
            <td>Expected Credit:</td>
            <td>Within 3-5 business days</td>
        </tr>
    </table>
</div>

<p>The amount will be credited to your registered bank account. You can track the status of your withdrawal in your dashboard.</p>

<a href="{{ config('app.url') }}/wallet" class="btn">View Wallet</a>

<p><strong>Important Notes:</strong></p>
<ul>
    <li>Funds will be transferred to your verified bank account only</li>
    <li>Processing times may vary depending on your bank</li>
    <li>TDS (if applicable) has been deducted as per IT regulations</li>
</ul>

<p>Thank you for using PreIPOsip!</p>

<p>Best regards,<br>
<strong>PreIPOsip Team</strong></p>
@endsection
