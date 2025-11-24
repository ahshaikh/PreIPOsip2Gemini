<?php
// V-SEEDER (Created for development environment setup)

namespace Database\Seeders;

use App\Models\CannedResponse;
use Illuminate\Database\Seeder;

class CannedResponseSeeder extends Seeder
{
    public function run(): void
    {
        $responses = [
            // Payment Related
            [
                'title' => 'Payment Not Reflected',
                'content' => "Thank you for reaching out. I understand you're concerned about your payment not reflecting in your account.\n\nPayments typically take 15-30 minutes to reflect after successful completion. If it's been longer:\n\n1. Please share your payment reference/UTR number\n2. I'll verify the transaction status\n3. If confirmed, your account will be updated within 24 hours\n\nRest assured, all verified payments will be credited to your account.",
                'category' => 'payment',
                'is_active' => true,
            ],
            [
                'title' => 'Payment Failed',
                'content' => "I'm sorry to hear about the payment failure. This can happen due to:\n\n1. Bank server issues\n2. Insufficient funds\n3. Transaction timeout\n\nDon't worry - if any amount was deducted, it will be automatically refunded to your bank account within 5-7 business days.\n\nYou can try making the payment again once you've verified your bank balance.",
                'category' => 'payment',
                'is_active' => true,
            ],

            // KYC Related
            [
                'title' => 'KYC Processing Time',
                'content' => "Thank you for completing your KYC submission.\n\nOur verification team reviews all documents within 24-48 business hours. You'll receive an email notification once your KYC is approved.\n\nIf there are any issues with your documents, we'll send specific feedback so you can resubmit.\n\nPlease ensure your documents are:\n- Clear and readable\n- Not expired\n- Matching the registered name",
                'category' => 'kyc',
                'is_active' => true,
            ],
            [
                'title' => 'KYC Document Guidelines',
                'content' => "Here are the document guidelines for successful KYC verification:\n\n**Identity Proof (any one):**\n- PAN Card (mandatory)\n- Aadhaar Card\n- Passport\n\n**Address Proof (any one):**\n- Aadhaar Card\n- Utility Bill (less than 3 months old)\n- Bank Statement\n\n**Photo Guidelines:**\n- Clear, recent photo\n- Good lighting\n- Face clearly visible\n\nPlease upload high-quality images where all text is readable.",
                'category' => 'kyc',
                'is_active' => true,
            ],

            // Bonus Related
            [
                'title' => 'Bonus Eligibility',
                'content' => "Great question about our bonus program!\n\n**Types of Bonuses:**\n1. Progressive Bonus - Increases monthly from 4th payment\n2. Milestone Bonus - At 6, 12, 24 months\n3. Consistency Bonus - For on-time payments\n4. Referral Bonus - When friends join and pay\n\n**Eligibility:**\n- Active subscription\n- KYC verified\n- Timely payments\n\nAll bonuses are credited to your wallet automatically!",
                'category' => 'bonus',
                'is_active' => true,
            ],

            // Withdrawal Related
            [
                'title' => 'Withdrawal Processing',
                'content' => "Thank you for your withdrawal request.\n\n**Processing Timeline:**\n- Review: 1-2 business days\n- Bank transfer: 2-3 business days after approval\n\n**Requirements:**\n- Verified bank account\n- Sufficient wallet balance\n- KYC approved\n\nYou'll receive an email with UTR number once processed. Please check your registered bank account for the credit.",
                'category' => 'withdrawal',
                'is_active' => true,
            ],

            // Technical
            [
                'title' => 'Login Issues',
                'content' => "I understand you're having trouble logging in. Let's resolve this:\n\n1. **Forgot Password?** Use the 'Forgot Password' link on the login page\n2. **OTP not received?** Check spam folder, wait 30 seconds before resending\n3. **Account locked?** This happens after 5 failed attempts. Wait 30 minutes or contact support.\n\nIf the issue persists, please provide:\n- Registered email/mobile\n- Browser/device being used\n- Any error message shown",
                'category' => 'technical',
                'is_active' => true,
            ],

            // General
            [
                'title' => 'Thanks for Patience',
                'content' => "Thank you for your patience while I looked into this.\n\nI've now resolved the issue and updated your account accordingly. You should see the changes reflected immediately.\n\nIs there anything else I can help you with today?",
                'category' => 'general',
                'is_active' => true,
            ],
            [
                'title' => 'Ticket Closure',
                'content' => "I'm glad we could resolve your concern today.\n\nThis ticket will now be marked as resolved. If you have any other questions in the future, don't hesitate to reach out.\n\nThank you for choosing PreIPO SIP. Happy investing!",
                'category' => 'general',
                'is_active' => true,
            ],
        ];

        foreach ($responses as $response) {
            CannedResponse::updateOrCreate(
                ['title' => $response['title']],
                $response
            );
        }
    }
}
