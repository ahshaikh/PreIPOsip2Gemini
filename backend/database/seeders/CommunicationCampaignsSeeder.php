<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;
use App\Models\SmsTemplate;
use App\Models\CannedResponse;
use App\Models\KbCategory;
use App\Models\ReferralCampaign;
use App\Models\Campaign;
use App\Models\LuckyDraw;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Communication & Campaigns Seeder - Phase 5 & 6
 *
 * Seeds communication infrastructure and campaign configurations:
 * - Email Templates
 * - SMS Templates
 * - Canned Responses (Support)
 * - KB Categories
 * - Referral Campaigns
 * - Promotional Campaigns
 * - Lucky Draws
 *
 * CRITICAL:
 * - All templates use variables for dynamic content
 * - Campaign business logic stored in DB
 * - Lucky draw configurations are admin-editable
 */
class CommunicationCampaignsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $this->seedEmailTemplates();
            $this->seedSmsTemplates();
            $this->seedCannedResponses();
            $this->seedKbCategories();
            $this->seedReferralCampaigns();
            $this->seedPromotionalCampaigns();
            $this->seedLuckyDraws();
        });

        $this->command->info('✅ Communication & Campaigns data seeded successfully');
    }

    /**
     * Seed email templates
     */
    private function seedEmailTemplates(): void
    {
        $templates = [
            [
                'name' => 'Welcome Email',
                'slug' => 'welcome_email',
                'subject' => 'Welcome to PreIPOsip - Start Your Investment Journey',
                'body' => '<p>Hello {{user_name}},</p><p>Welcome to PreIPOsip! We are excited to have you on board.</p><p>Get started by completing your KYC verification and exploring our Pre-IPO investment opportunities.</p>',
                'variables' => json_encode(['user_name', 'email', 'referral_code']),
                'is_active' => true,
            ],
            [
                'name' => 'KYC Approved',
                'slug' => 'kyc_approved',
                'subject' => 'KYC Verification Successful - Start Investing',
                'body' => '<p>Hello {{user_name}},</p><p>Congratulations! Your KYC verification has been approved.</p><p>You can now start investing in Pre-IPO companies.</p>',
                'variables' => json_encode(['user_name', 'kyc_verified_at']),
                'is_active' => true,
            ],
            [
                'name' => 'KYC Rejected',
                'slug' => 'kyc_rejected',
                'subject' => 'KYC Verification - Action Required',
                'body' => '<p>Hello {{user_name}},</p><p>Your KYC submission was rejected for the following reason:</p><p>{{rejection_reason}}</p><p>Please resubmit your documents.</p>',
                'variables' => json_encode(['user_name', 'rejection_reason']),
                'is_active' => true,
            ],
            [
                'name' => 'Payment Success',
                'slug' => 'payment_success',
                'subject' => 'Payment Received - ₹{{amount}}',
                'body' => '<p>Hello {{user_name}},</p><p>We have received your payment of ₹{{amount}} for {{plan_name}}.</p><p>Transaction ID: {{transaction_id}}</p>',
                'variables' => json_encode(['user_name', 'amount', 'plan_name', 'transaction_id', 'payment_date']),
                'is_active' => true,
            ],
            [
                'name' => 'Investment Allocation',
                'slug' => 'investment_allocation',
                'subject' => 'Shares Allocated - {{company_name}}',
                'body' => '<p>Hello {{user_name}},</p><p>You have been allocated {{quantity}} shares of {{company_name}} worth ₹{{amount}}.</p>',
                'variables' => json_encode(['user_name', 'company_name', 'quantity', 'amount']),
                'is_active' => true,
            ],
            [
                'name' => 'Withdrawal Approved',
                'slug' => 'withdrawal_approved',
                'subject' => 'Withdrawal Request Approved - ₹{{amount}}',
                'body' => '<p>Hello {{user_name}},</p><p>Your withdrawal request of ₹{{amount}} has been approved and will be processed within 24-48 hours.</p>',
                'variables' => json_encode(['user_name', 'amount', 'bank_account']),
                'is_active' => true,
            ],
            [
                'name' => 'Bonus Credited',
                'slug' => 'bonus_credited',
                'subject' => 'Bonus Credited - ₹{{amount}}',
                'body' => '<p>Hello {{user_name}},</p><p>A bonus of ₹{{amount}} has been credited to your wallet for {{bonus_type}}.</p>',
                'variables' => json_encode(['user_name', 'amount', 'bonus_type']),
                'is_active' => true,
            ],
            [
                'name' => 'Referral Bonus',
                'slug' => 'referral_bonus',
                'subject' => 'Referral Bonus Earned - ₹{{amount}}',
                'body' => '<p>Hello {{user_name}},</p><p>You earned ₹{{amount}} as referral bonus for referring {{referred_user}}!</p>',
                'variables' => json_encode(['user_name', 'amount', 'referred_user']),
                'is_active' => true,
            ],
            [
                'name' => 'Lucky Draw Winner',
                'slug' => 'lucky_draw_winner',
                'subject' => 'Congratulations! You Won ₹{{prize_amount}}',
                'body' => '<p>Hello {{user_name}},</p><p>Congratulations! You are a winner in our {{draw_name}}. You won ₹{{prize_amount}}!</p>',
                'variables' => json_encode(['user_name', 'draw_name', 'prize_amount', 'prize_rank']),
                'is_active' => true,
            ],
            [
                'name' => 'Password Reset',
                'slug' => 'password_reset',
                'subject' => 'Reset Your Password',
                'body' => '<p>Hello {{user_name}},</p><p>Click the link below to reset your password:</p><p>{{reset_link}}</p>',
                'variables' => json_encode(['user_name', 'reset_link']),
                'is_active' => true,
            ],
        ];

        foreach ($templates as $template) {
            EmailTemplate::updateOrCreate(
                ['slug' => $template['slug']],
                $template
            );
        }

        $this->command->info('  ✓ Email templates seeded: ' . count($templates) . ' records');
    }

    /**
     * Seed SMS templates
     */
    private function seedSmsTemplates(): void
    {
        $templates = [
            [
                'name' => 'OTP Verification',
                'slug' => 'otp_verification',
                'content' => 'Your PreIPOsip OTP is {{otp}}. Valid for 10 minutes. Do not share this with anyone.',
                'variables' => json_encode(['otp']),
                'is_active' => true,
            ],
            [
                'name' => 'Payment Success',
                'slug' => 'payment_success',
                'content' => 'Payment of Rs.{{amount}} received for {{plan_name}}. Thank you for investing with PreIPOsip!',
                'variables' => json_encode(['amount', 'plan_name']),
                'is_active' => true,
            ],
            [
                'name' => 'KYC Approved',
                'slug' => 'kyc_approved',
                'content' => 'Your KYC has been approved! You can now start investing in Pre-IPO companies. - PreIPOsip',
                'variables' => json_encode([]),
                'is_active' => true,
            ],
            [
                'name' => 'Withdrawal Approved',
                'slug' => 'withdrawal_approved',
                'content' => 'Withdrawal of Rs.{{amount}} approved. Funds will be transferred within 24-48 hours. - PreIPOsip',
                'variables' => json_encode(['amount']),
                'is_active' => true,
            ],
            [
                'name' => 'Bonus Credited',
                'slug' => 'bonus_credited',
                'content' => 'Bonus of Rs.{{amount}} credited to your wallet for {{bonus_type}}. Check your account now! - PreIPOsip',
                'variables' => json_encode(['amount', 'bonus_type']),
                'is_active' => true,
            ],
        ];

        foreach ($templates as $template) {
            SmsTemplate::updateOrCreate(
                ['slug' => $template['slug']],
                $template
            );
        }

        $this->command->info('  ✓ SMS templates seeded: ' . count($templates) . ' records');
    }

    /**
     * Seed canned responses
     */
    private function seedCannedResponses(): void
    {
        $responses = [
            ['title' => 'Welcome Message', 'content' => 'Hello! Welcome to PreIPOsip support. How can I help you today?', 'category' => 'general'],
            ['title' => 'KYC Under Review', 'content' => 'Your KYC documents are currently under review. You will receive an update within 24-48 hours.', 'category' => 'kyc'],
            ['title' => 'Payment Processing', 'content' => 'Your payment is being processed. You will receive a confirmation email once it is completed.', 'category' => 'payment'],
            ['title' => 'Withdrawal Timeline', 'content' => 'Withdrawal requests are typically processed within 24-48 business hours.', 'category' => 'withdrawal'],
            ['title' => 'Investment Allocation', 'content' => 'Share allocations are done on a priority basis according to your plan tier. You will be notified once shares are allocated.', 'category' => 'investment'],
            ['title' => 'Bonus Eligibility', 'content' => 'Bonuses are calculated based on your investment plan and tenure. Please refer to your plan details for more information.', 'category' => 'bonus'],
            ['title' => 'Referral Program', 'content' => 'You can earn referral bonuses by sharing your unique referral code. Your referee must complete KYC and invest at least ₹5,000.', 'category' => 'referral'],
            ['title' => 'Account Security', 'content' => 'For account security, we recommend enabling two-factor authentication and using a strong password.', 'category' => 'security'],
            ['title' => 'Technical Issue', 'content' => 'I apologize for the technical issue you are facing. Our team is looking into this and will resolve it shortly.', 'category' => 'technical'],
            ['title' => 'Escalation', 'content' => 'I am escalating your query to our senior support team. You will receive a response within 4 hours.', 'category' => 'escalation'],
        ];

        foreach ($responses as $response) {
            CannedResponse::updateOrCreate(
                ['title' => $response['title']],
                $response
            );
        }

        $this->command->info('  ✓ Canned responses seeded: ' . count($responses) . ' records');
    }

    /**
     * Seed knowledge base categories
     */
    private function seedKbCategories(): void
    {
        $categories = [
            ['name' => 'Getting Started', 'slug' => 'getting-started', 'description' => 'Basic guides for new users', 'display_order' => 1, 'is_active' => true],
            ['name' => 'KYC Verification', 'slug' => 'kyc-verification', 'description' => 'KYC submission and verification help', 'display_order' => 2, 'is_active' => true],
            ['name' => 'Investment & Plans', 'slug' => 'investment-plans', 'description' => 'Understanding investment plans and SIPs', 'display_order' => 3, 'is_active' => true],
            ['name' => 'Payments & Wallet', 'slug' => 'payments-wallet', 'description' => 'Payment methods and wallet management', 'display_order' => 4, 'is_active' => true],
            ['name' => 'Withdrawals', 'slug' => 'withdrawals', 'description' => 'Withdrawal process and timelines', 'display_order' => 5, 'is_active' => true],
        ];

        foreach ($categories as $category) {
            KbCategory::updateOrCreate(
                ['slug' => $category['slug']],
                $category
            );
        }

        $this->command->info('  ✓ KB categories seeded: ' . count($categories) . ' records');
    }

    /**
     * Seed referral campaigns
     */
    private function seedReferralCampaigns(): void
    {
        $campaigns = [
            [
                'name' => 'Standard Referral Program',
                'slug' => 'standard-referral',
                'description' => 'Earn ₹500 for each successful referral who completes KYC and makes their first investment.',
                'bonus_amount' => 500,
                'multiplier' => 1.0,
                'start_date' => now()->subMonths(6),
                'end_date' => now()->addYear(),
                'is_active' => true,
                'max_referrals' => null,
            ],
            [
                'name' => 'Premium Referral Campaign',
                'slug' => 'premium-referral',
                'description' => 'Earn ₹1,000 for each referral who invests ₹25,000 or more.',
                'bonus_amount' => 1000,
                'multiplier' => 1.0,
                'start_date' => now()->subMonth(),
                'end_date' => now()->addMonths(3),
                'is_active' => true,
                'max_referrals' => 1000,
            ],
        ];

        foreach ($campaigns as $campaign) {
            ReferralCampaign::updateOrCreate(
                ['name' => $campaign['name']],
                $campaign
            );
        }

        $this->command->info('  ✓ Referral campaigns seeded: ' . count($campaigns) . ' records');
    }

    /**
     * Seed promotional campaigns
     */
    private function seedPromotionalCampaigns(): void
    {
        $campaigns = [
            [
                'title' => 'New Year Investment Offer',
                'code' => 'NEWYEAR2026',
                'description' => 'Get 10% discount on your first investment in any plan.',
                'discount_type' => 'percentage',
                'discount_percent' => 10.00,
                'min_investment' => 5000.00,
                'max_discount' => 2500.00,
                'start_at' => now()->startOfYear(),
                'end_at' => now()->startOfYear()->addMonths(2),
                'is_active' => true,
                'usage_limit' => 500,
                'usage_count' => 0,
                'terms' => json_encode(['Valid for first investment only', 'Cannot be combined with other offers']),
                'features' => json_encode(['10% instant discount', 'No upper limit', 'Auto-applied']),
            ],
            [
                'title' => 'First Investment Cashback',
                'code' => 'FIRST500',
                'description' => 'Get ₹500 cashback on your first investment of ₹10,000 or more.',
                'discount_type' => 'fixed_amount',
                'discount_amount' => 500.00,
                'min_investment' => 10000.00,
                'max_discount' => 500.00,
                'start_at' => now()->subMonth(),
                'end_at' => now()->addMonths(6),
                'is_active' => true,
                'usage_limit' => null,
                'usage_count' => 0,
                'terms' => json_encode(['Valid for investments ₹10,000+', 'Credited within 24 hours']),
                'features' => json_encode(['₹500 instant cashback', 'One-time offer', 'No code required']),
            ],
            [
                'title' => 'Festival Bonus Campaign',
                'code' => 'FESTIVAL2026',
                'description' => 'Special bonus on investments during festival season.',
                'discount_type' => 'percentage',
                'discount_percent' => 5.00,
                'min_investment' => 5000.00,
                'max_discount' => 1000.00,
                'start_at' => now()->addMonths(3),
                'end_at' => now()->addMonths(4),
                'is_active' => false,
                'usage_limit' => 1000,
                'usage_count' => 0,
                'terms' => json_encode(['Limited period offer', 'Valid on all plans']),
                'features' => json_encode(['5% bonus', 'Festival special', 'Auto-applied']),
            ],
        ];

        foreach ($campaigns as $campaign) {
            Campaign::updateOrCreate(
                ['code' => $campaign['code']],
                $campaign
            );
        }

        $this->command->info('  ✓ Promotional campaigns seeded: ' . count($campaigns) . ' records');
    }

    /**
     * Seed lucky draws
     */
    private function seedLuckyDraws(): void
    {
        $draws = [
            [
                'name' => 'Monthly Lucky Draw - January 2026',
                'draw_date' => now()->endOfMonth()->addDays(3)->toDateString(),
                'prize_structure' => json_encode([
                    ['rank' => 1, 'amount' => 25000, 'quantity' => 1],
                    ['rank' => 2, 'amount' => 15000, 'quantity' => 1],
                    ['rank' => 3, 'amount' => 10000, 'quantity' => 1],
                ]),
                'status' => 'open',
                'frequency' => 'monthly',
                'entry_rules' => json_encode([
                    'min_investment' => 5000,
                    'min_active_months' => 1,
                    'entries_per_investment' => 1,
                ]),
                'result_visibility' => 'public',
            ],
        ];

        foreach ($draws as $draw) {
            LuckyDraw::updateOrCreate(
                ['name' => $draw['name']],
                $draw
            );
        }

        $this->command->info('  ✓ Lucky draws seeded: ' . count($draws) . ' records');
    }
}
