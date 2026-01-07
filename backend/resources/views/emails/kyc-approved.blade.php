@extends('emails.layout')

@section('title', 'KYC Approved')

@section('content')
<h2>🎉 Congratulations, {{ $name }}!</h2>

<p>We're pleased to inform you that your KYC (Know Your Customer) verification has been <strong>successfully approved</strong>!</p>

<div class="highlight-box">
    <p><strong>What this means for you:</strong></p>
    <ul style="margin: 10px 0;">
        <li>You can now make unlimited investments</li>
        <li>Withdrawal requests will be processed faster</li>
        <li>Access to exclusive investment opportunities</li>
        <li>Lower TDS rates (10% instead of 20%)</li>
    </ul>
</div>

<p>You can now explore all available investment deals and start building your portfolio.</p>

<a href="{{ config('app.url') }}/deals" class="btn">Explore Deals</a>

<p>If you have any questions, our support team is here to help.</p>

<p>Best regards,<br>
<strong>PreIPOsip Team</strong></p>
@endsection
