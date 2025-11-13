<!DOCTYPE html>
<html>
<body>
    <h2>Payment Successful!</h2>
    <p>Dear {{ $payment->user->username }},</p>
    <p>We have received your payment of Rs. {{ $payment->amount }}.</p>
    <p>Your invoice is attached to this email.</p>
    <br>
    <p>Thank you,</p>
    <p>PreIPO SIP</p>
    <p>Accounts Team</p>

</body>
</html>