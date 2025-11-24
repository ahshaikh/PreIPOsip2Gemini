<?php
// V-SEEDER (Created for development environment setup)

namespace Database\Seeders;

use App\Models\Faq;
use Illuminate\Database\Seeder;

class FaqSeeder extends Seeder
{
    public function run(): void
    {
        $faqs = [
            // Getting Started
            [
                'question' => 'What is PreIPO SIP?',
                'answer' => 'PreIPO SIP is an investment platform that allows you to invest in pre-IPO companies through a Systematic Investment Plan (SIP). You can invest small amounts monthly and own shares in promising startups before they go public.',
                'category' => 'getting_started',
                'display_order' => 1,
                'is_published' => true,
            ],
            [
                'question' => 'How do I create an account?',
                'answer' => "Creating an account is simple:\n1. Click on 'Sign Up' on our homepage\n2. Enter your mobile number and email\n3. Verify with OTP\n4. Complete your profile\n5. Submit KYC documents\n\nOnce KYC is approved, you can start investing!",
                'category' => 'getting_started',
                'display_order' => 2,
                'is_published' => true,
            ],
            [
                'question' => 'What documents are required for KYC?',
                'answer' => "For KYC verification, you need:\n- PAN Card (mandatory for all investments)\n- Aadhaar Card or other address proof\n- Recent photograph\n- Bank account details\n\nAll documents should be clear, valid, and matching your registered details.",
                'category' => 'getting_started',
                'display_order' => 3,
                'is_published' => true,
            ],

            // Investment
            [
                'question' => 'What is the minimum investment amount?',
                'answer' => 'The minimum SIP amount starts from ₹1,000 per month, depending on the plan you choose. We offer multiple plans to suit different investment capacities.',
                'category' => 'investment',
                'display_order' => 1,
                'is_published' => true,
            ],
            [
                'question' => 'How are shares allocated?',
                'answer' => 'Shares are allocated based on your cumulative SIP payments. Once a bulk purchase is made at the company level, shares are distributed proportionally among all investors in that product based on their investment amounts.',
                'category' => 'investment',
                'display_order' => 2,
                'is_published' => true,
            ],
            [
                'question' => 'What happens when a company goes IPO?',
                'answer' => "When a company in your portfolio goes IPO:\n1. You'll be notified immediately\n2. Your shares may be subject to a lock-in period\n3. After lock-in, you can sell on the open market\n4. Any gains will be credited to your account\n\nThe exact process depends on the company's IPO terms.",
                'category' => 'investment',
                'display_order' => 3,
                'is_published' => true,
            ],

            // Payments
            [
                'question' => 'What payment methods are accepted?',
                'answer' => "We accept multiple payment methods:\n- UPI (Google Pay, PhonePe, Paytm)\n- Net Banking\n- Debit/Credit Cards\n- Auto-debit via UPI Mandate\n\nAll payments are secure and encrypted.",
                'category' => 'payments',
                'display_order' => 1,
                'is_published' => true,
            ],
            [
                'question' => 'Can I set up auto-debit for SIP?',
                'answer' => "Yes! You can set up UPI Auto-debit (E-mandate) for your SIP:\n1. Choose auto-debit option during payment\n2. Approve the mandate on your UPI app\n3. Payments will be automatically deducted monthly\n\nYou can cancel the mandate anytime from your account settings.",
                'category' => 'payments',
                'display_order' => 2,
                'is_published' => true,
            ],

            // Bonuses
            [
                'question' => 'How does the bonus system work?',
                'answer' => "We offer multiple bonus types:\n\n**Progressive Bonus:** Increases monthly (starts from month 4)\n**Milestone Bonus:** One-time rewards at 6, 12, 24 months\n**Consistency Bonus:** For on-time payments\n**Referral Bonus:** When your friends join and invest\n\nAll bonuses are credited to your wallet automatically!",
                'category' => 'bonuses',
                'display_order' => 1,
                'is_published' => true,
            ],
            [
                'question' => 'How can I withdraw my bonuses?',
                'answer' => "Bonus amounts are credited to your wallet. To withdraw:\n1. Go to Wallet section\n2. Click 'Withdraw'\n3. Enter amount and bank details\n4. Confirm the request\n\nWithdrawals are processed within 3-5 business days. Note: A minimum wallet balance of ₹500 is required for withdrawal.",
                'category' => 'bonuses',
                'display_order' => 2,
                'is_published' => true,
            ],

            // Security
            [
                'question' => 'Is my investment safe?',
                'answer' => "Your investment security is our priority:\n- All investments are held in your name\n- Funds are managed through SEBI-registered entities\n- Bank-grade encryption for all transactions\n- Two-factor authentication available\n- Regular audits and compliance checks\n\nWhile pre-IPO investments carry market risks, your ownership is legally documented.",
                'category' => 'security',
                'display_order' => 1,
                'is_published' => true,
            ],
            [
                'question' => 'How do I enable two-factor authentication?',
                'answer' => "To enable 2FA:\n1. Go to Settings > Security\n2. Click 'Enable 2FA'\n3. Scan QR code with Google Authenticator or similar app\n4. Enter the 6-digit code to confirm\n5. Save your recovery codes securely\n\n2FA adds an extra layer of security to your account.",
                'category' => 'security',
                'display_order' => 2,
                'is_published' => true,
            ],
        ];

        foreach ($faqs as $faq) {
            Faq::updateOrCreate(
                ['question' => $faq['question']],
                $faq
            );
        }
    }
}
