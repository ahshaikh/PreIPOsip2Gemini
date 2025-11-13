<!DOCTYPE html>
<html>
<body>
    <h2>Payment Failed</h2>
    <p>Dear {{ $payment->user->username }},</p>
    <p>We attempted to process a payment of <strong>₹{{ $payment->amount }}</strong> for your {{ $payment->subscription->plan->name }} subscription, but it failed.</p>
    <p><strong>Reason:</strong> {{ $reason }}</p>
    <p>Please log in to your dashboard and retry the payment to ensure you don't lose your bonus streak.</p>
    <a href="{{ env('FRONTEND_URL') }}/subscription">Pay Now</a>
    <br><br>
    <p>PreIPO SIP Team</p>
</body>
</html>