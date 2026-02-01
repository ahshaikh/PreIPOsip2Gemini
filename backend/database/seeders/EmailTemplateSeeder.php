<?php
// V-SEEDER (Created for development environment setup)

namespace Database\Seeders;

use App\Models\EmailTemplate;
use Illuminate\Database\Seeder;

class EmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'name' => 'Welcome Email',
                'slug' => 'welcome',
                'subject' => 'Welcome to PreIPO SIP - Your Investment Journey Begins!',
                'body' => '<h1>Welcome, {{name}}!</h1>
<p>Thank you for joining PreIPO SIP. Your account has been created successfully.</p>
<p>Get started by:</p>
<ol>
    <li>Complete your KYC verification</li>
    <li>Choose an investment plan</li>
    <li>Start your first SIP</li>
</ol>
<p>If you have any questions, our support team is here to help.</p>
<p>Best regards,<br>The PreIPO SIP Team</p>',
                'variables' => json_encode(['name', 'email']),
                'is_active' => true,
            ],
            [
                'name' => 'Payment Confirmation',
                'slug' => 'payment-confirmation',
                'subject' => 'Payment Confirmed - ₹{{amount}} Received',
                'body' => '<h1>Payment Received!</h1>
<p>Dear {{name}},</p>
<p>We have successfully received your payment of <strong>₹{{amount}}</strong>.</p>
<p><strong>Payment Details:</strong></p>
<ul>
    <li>Amount: ₹{{amount}}</li>
    <li>Plan: {{plan_name}}</li>
    <li>Transaction ID: {{transaction_id}}</li>
    <li>Date: {{date}}</li>
</ul>
<p>If you have chopsen automated sip, your investment will be allocated shortly, otherwise please proceed to deals page and browse the available pre-ipo available.</p>
<p>Thank you for investing with us!</p>',
                'variables' => json_encode(['name', 'amount', 'plan_name', 'transaction_id', 'date']),
                'is_active' => true,
            ],
            [
                'name' => 'KYC Approved',
                'slug' => 'kyc-approved',
                'subject' => 'KYC Verification Approved - Start Investing Now!',
                'body' => '<h1>KYC Approved!</h1>
<p>Dear {{name}},</p>
<p>Great news! Your KYC verification has been approved.</p>
<p>You can now:</p>
<ul>
    <li>Subscribe to investment plans</li>
    <li>Make investments</li>
    <li>Request withdrawals</li>
</ul>
<p>Start your investment journey today!</p>',
                'variables' => json_encode(['name']),
                'is_active' => true,
            ],
            [
                'name' => 'KYC Rejected',
                'slug' => 'kyc-rejected',
                'subject' => 'KYC Verification - Action Required',
                'body' => '<h1>KYC Verification Update</h1>
<p>Dear {{name}},</p>
<p>Unfortunately, we could not verify your KYC documents.</p>
<p><strong>Reason:</strong> {{rejection_reason}}</p>
<p>Please re-submit your documents with the necessary corrections.</p>
<p>If you need assistance, please contact our support team.</p>',
                'variables' => json_encode(['name', 'rejection_reason']),
                'is_active' => true,
            ],
            [
                'name' => 'Bonus Credited',
                'slug' => 'bonus-credited',
                'subject' => 'Bonus Credited - ₹{{amount}} Added to Your Wallet!',
                'body' => '<h1>Congratulations!</h1>
<p>Dear {{name}},</p>
<p>A bonus of <strong>₹{{amount}}</strong> has been credited to your wallet.</p>
<p><strong>Bonus Details:</strong></p>
<ul>
    <li>Type: {{bonus_type}}</li>
    <li>Amount: ₹{{amount}}</li>
    <li>Date: {{date}}</li>
</ul>
<p>Keep investing to earn more bonuses!</p>',
                'variables' => json_encode(['name', 'amount', 'bonus_type', 'date']),
                'is_active' => true,
            ],
            [
                'name' => 'Payment Reminder',
                'slug' => 'payment-reminder',
                'subject' => 'SIP Payment Due - {{plan_name}}',
                'body' => '<h1>Payment Reminder</h1>
<p>Dear {{name}},</p>
<p>Your SIP payment of <strong>₹{{amount}}</strong> for {{plan_name}} is due on {{due_date}}.</p>
<p>Please ensure timely payment to:</p>
<ul>
    <li>Maintain your investment streak</li>
    <li>Earn consistency bonuses</li>
    <li>Maximize your returns</li>
</ul>
<p><a href="{{payment_link}}">Pay Now</a></p>',
                'variables' => json_encode(['name', 'amount', 'plan_name', 'due_date', 'payment_link']),
                'is_active' => true,
            ],
            [
                'name' => 'Withdrawal Processed',
                'slug' => 'withdrawal-processed',
                'subject' => 'Withdrawal Processed - ₹{{amount}}',
                'body' => '<h1>Withdrawal Successful</h1>
<p>Dear {{name}},</p>
<p>Your withdrawal request has been processed.</p>
<p><strong>Details:</strong></p>
<ul>
    <li>Amount: ₹{{amount}}</li>
    <li>Bank: {{bank_name}}</li>
    <li>UTR: {{utr_number}}</li>
    <li>Date: {{date}}</li>
</ul>
<p>The amount should reflect in your bank account within 2-3 business days.</p>',
                'variables' => json_encode(['name', 'amount', 'bank_name', 'utr_number', 'date']),
                'is_active' => true,
            ],
        ];

        foreach ($templates as $template) {
            EmailTemplate::updateOrCreate(
                ['slug' => $template['slug']],
                $template
            );
        }
    }
}
