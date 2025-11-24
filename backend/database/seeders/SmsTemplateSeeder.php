<?php
// V-SEEDER (Created for development environment setup)

namespace Database\Seeders;

use App\Models\SmsTemplate;
use Illuminate\Database\Seeder;

class SmsTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'name' => 'OTP Verification',
                'slug' => 'otp',
                'content' => '{{otp}} is your OTP for PreIPO SIP. Valid for 10 minutes. Do not share this code with anyone.',
                'variables' => json_encode(['otp']),
                'is_active' => true,
            ],
            [
                'name' => 'Payment Confirmation',
                'slug' => 'payment-confirmation',
                'content' => 'Payment of Rs.{{amount}} received for {{plan_name}}. Txn ID: {{transaction_id}}. Thank you for investing with PreIPO SIP.',
                'variables' => json_encode(['amount', 'plan_name', 'transaction_id']),
                'is_active' => true,
            ],
            [
                'name' => 'Payment Reminder',
                'slug' => 'payment-reminder',
                'content' => 'Reminder: Your SIP payment of Rs.{{amount}} for {{plan_name}} is due on {{due_date}}. Pay now to maintain your streak!',
                'variables' => json_encode(['amount', 'plan_name', 'due_date']),
                'is_active' => true,
            ],
            [
                'name' => 'Bonus Credited',
                'slug' => 'bonus-credited',
                'content' => 'Congratulations! Rs.{{amount}} {{bonus_type}} bonus credited to your PreIPO SIP wallet. Keep investing!',
                'variables' => json_encode(['amount', 'bonus_type']),
                'is_active' => true,
            ],
            [
                'name' => 'KYC Approved',
                'slug' => 'kyc-approved',
                'content' => 'Great news! Your KYC verification is complete. Start investing now on PreIPO SIP.',
                'variables' => json_encode([]),
                'is_active' => true,
            ],
            [
                'name' => 'Withdrawal Initiated',
                'slug' => 'withdrawal-initiated',
                'content' => 'Withdrawal of Rs.{{amount}} initiated. It will be credited to your bank account within 2-3 business days.',
                'variables' => json_encode(['amount']),
                'is_active' => true,
            ],
            [
                'name' => 'Investment Allocated',
                'slug' => 'investment-allocated',
                'content' => '{{shares}} shares of {{product_name}} allocated to your portfolio at Rs.{{price_per_share}}/share. View details in app.',
                'variables' => json_encode(['shares', 'product_name', 'price_per_share']),
                'is_active' => true,
            ],
        ];

        foreach ($templates as $template) {
            SmsTemplate::updateOrCreate(
                ['slug' => $template['slug']],
                $template
            );
        }
    }
}
