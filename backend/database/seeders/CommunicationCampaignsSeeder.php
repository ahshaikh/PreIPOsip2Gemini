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
                'body' => 'Your PreIPOsip OTP is {{otp}}. Valid for 10 minutes. Do not share this with anyone.',
                'variables' => json_encode(['otp']),
                'is_active' => true,
            ],
            [
                'name' => 'Payment Success',
                'slug' => 'payment_success',
                'body' => 'Payment of Rs.{{amount}} received for {{plan_name}}. Thank you for investing with PreIPOsip!',
                'variables' => json_encode(['amount', 'plan_name']),
                'is_active' => true,
            ],
            [
                'name' => 'KYC Approved',
                'slug' => 'kyc_approved',
                'body' => 'Your KYC has been approved! You can now start investing in Pre-IPO companies. - PreIPOsip',
                'variables' => json_encode([]),
                'is_active' => true,
            ],
            [
                'name' => 'Withdrawal Approved',
                'slug' => 'withdrawal_approved',
                'body' => 'Withdrawal of Rs.{{amount}} approved. Funds will be transferred within 24-48 hours. - PreIPOsip',
                'variables' => json_encode(['amount']),
                'is_active' => true,
            ],
            [
                'name' => 'Bonus Credited',
                'slug' => 'bonus_credited',
                'body' => 'Bonus of Rs.{{amount}} credited to your wallet for {{bonus_type}}. Check your account now! - PreIPOsip',
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
            ['title' => 'Welcome Message', 'content' => 'Hello! Welcome to PreIPOsip support. How can I help you today?'],
            ['title' => 'KYC Under Review', 'content' => 'Your KYC documents are currently under review. You will receive an update within 24-48 hours.'],
            ['title' => 'Payment Processing', 'content' => 'Your payment is being processed. You will receive a confirmation email once it is completed.'],
            ['title' => 'Withdrawal Timeline', 'content' => 'Withdrawal requests are typically processed within 24-48 business hours.'],
            ['title' => 'Investment Allocation', 'content' => 'Share allocations are done on a priority basis according to your plan tier. You will be notified once shares are allocated.'],
            ['title' => 'Bonus Eligibility', 'content' => 'Bonuses are calculated based on your investment plan and tenure. Please refer to your plan details for more information.'],
            ['title' => 'Referral Program', 'content' => 'You can earn referral bonuses by sharing your unique referral code. Your referee must complete KYC and invest at least ₹5,000.'],
            ['title' => 'Account Security', 'content' => 'For account security, we recommend enabling two-factor authentication and using a strong password.'],
            ['title' => 'Technical Issue', 'content' => 'I apologize for the technical issue you are facing. Our team is looking into this and will resolve it shortly.'],
            ['title' => 'Escalation', 'content' => 'I am escalating your query to our senior support team. You will receive a response within 4 hours.'],
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
            ['name' => 'Getting Started', 'slug' => 'getting-started', 'description' => 'Basic guides for new users', 'order' => 1],
            ['name' => 'KYC Verification', 'slug' => 'kyc-verification', 'description' => 'KYC submission and verification help', 'order' => 2],
            ['name' => 'Investment & Plans', 'slug' => 'investment-plans', 'description' => 'Understanding investment plans and SIPs', 'order' => 3],
            ['name' => 'Payments & Wallet', 'slug' => 'payments-wallet', 'description' => 'Payment methods and wallet management', 'order' => 4],
            ['name' => 'Withdrawals', 'slug' => 'withdrawals', 'description' => 'Withdrawal process and timelines', 'order' => 5],
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
                'code' => 'STANDARD_REFERRAL',
                'description' => 'Earn ₹500 for each successful referral who completes KYC and invests ₹5,000 or more.',
                'bonus_amount' => 500,
                'min_investment_required' => 5000,
                'start_date' => now()->subMonths(6),
                'end_date' => now()->addYear(),
                'is_active' => true,
                'max_redemptions' => null,
                'current_redemptions' => 0,
            ],
            [
                'name' => 'Premium Referral Campaign',
                'code' => 'PREMIUM_REFERRAL',
                'description' => 'Earn ₹1,000 for each referral who invests ₹25,000 or more in Plan C.',
                'bonus_amount' => 1000,
                'min_investment_required' => 25000,
                'start_date' => now()->subMonth(),
                'end_date' => now()->addMonths(3),
                'is_active' => true,
                'max_redemptions' => 1000,
                'current_redemptions' => 0,
            ],
        ];

        foreach ($campaigns as $campaign) {
            ReferralCampaign::updateOrCreate(
                ['code' => $campaign['code']],
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
                'name' => 'New Year Investment Offer',
                'code' => 'NEWYEAR2026',
                'description' => 'Get 10% discount on your first investment in any plan.',
                'discount_type' => 'percentage',
                'discount_value' => 10,
                'min_investment' => 5000,
                'max_discount_amount' => 2500,
                'start_date' => now()->startOfYear(),
                'end_date' => now()->startOfYear()->addMonths(2),
                'is_active' => true,
                'max_redemptions' => 500,
                'current_redemptions' => 0,
                'terms' => json_encode(['Valid for first investment only', 'Cannot be combined with other offers']),
                'features' => json_encode(['10% instant discount', 'No upper limit', 'Auto-applied']),
            ],
            [
                'name' => 'First Investment Cashback',
                'code' => 'FIRST500',
                'description' => 'Get ₹500 cashback on your first investment of ₹10,000 or more.',
                'discount_type' => 'fixed_amount',
                'discount_value' => 500,
                'min_investment' => 10000,
                'max_discount_amount' => 500,
                'start_date' => now()->subMonth(),
                'end_date' => now()->addMonths(6),
                'is_active' => true,
                'max_redemptions' => null,
                'current_redemptions' => 0,
                'terms' => json_encode(['Valid for investments ₹10,000+', 'Credited within 24 hours']),
                'features' => json_encode(['₹500 instant cashback', 'One-time offer', 'No code required']),
            ],
            [
                'name' => 'Festival Bonus Campaign',
                'code' => 'FESTIVAL2026',
                'description' => 'Special bonus on investments during festival season.',
                'discount_type' => 'percentage',
                'discount_value' => 5,
                'min_investment' => 5000,
                'max_discount_amount' => 1000,
                'start_date' => now()->addMonths(3),
                'end_date' => now()->addMonths(4),
                'is_active' => false,
                'max_redemptions' => 1000,
                'current_redemptions' => 0,
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
                'code' => 'MONTHLY_JAN2026',
                'description' => 'Monthly lucky draw for all active investors',
                'prize_pool' => 50000,
                'min_investment_required' => 5000,
                'start_date' => now()->startOfMonth(),
                'end_date' => now()->endOfMonth(),
                'draw_date' => now()->endOfMonth()->addDays(3),
                'is_active' => true,
                'status' => 'active',
                'prizes' => json_encode([
                    ['rank' => 1, 'amount' => 25000, 'quantity' => 1],
                    ['rank' => 2, 'amount' => 15000, 'quantity' => 1],
                    ['rank' => 3, 'amount' => 10000, 'quantity' => 1],
                ]),
                'entry_rules' => json_encode([
                    'min_investment' => 5000,
                    'min_active_months' => 1,
                    'entries_per_investment' => 1,
                ]),
            ],
        ];

        foreach ($draws as $draw) {
            LuckyDraw::updateOrCreate(
                ['code' => $draw['code']],
                $draw
            );
        }

        $this->command->info('  ✓ Lucky draws seeded: ' . count($draws) . ' records');
    }
}
