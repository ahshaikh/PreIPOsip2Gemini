<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Offer;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class OffersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🎁 Seeding Offers...');

        // Clear existing offers
        DB::table('offers')->truncate();

        $offers = [
            [
                'title' => 'Welcome Bonus - New Investors',
                'subtitle' => 'Start your investment journey with an exclusive welcome offer',
                'code' => 'WELCOME500',
                'description' => 'Get ₹500 instant discount on your first investment of ₹10,000 or more. Perfect for new investors looking to start their pre-IPO investment journey.',
                'long_description' => '<p>We\'re excited to welcome you to PreIPO SIP! As a special welcome gift, get an instant discount of ₹500 on your first investment.</p><p>This exclusive offer is designed to help new investors get started with pre-IPO investing at a discounted rate.</p>',
                'status' => 'active',
                'discount_type' => 'fixed_amount',
                'discount_percent' => null,
                'discount_amount' => 500.00,
                'min_investment' => 10000.00,
                'max_discount' => null,
                'usage_limit' => 1000,
                'usage_count' => 45,
                'user_usage_limit' => 1,
                'expiry' => Carbon::now()->addMonths(3),
                'image_url' => '/storage/offers/welcome-offer.jpg',
                'hero_image' => '/storage/offers/welcome-hero.jpg',
                'video_url' => null,
                'features' => [
                    '₹500 instant discount on first investment',
                    'No hidden charges or conditions',
                    'Valid on all investment plans',
                    'Easy to apply at checkout',
                ],
                'terms' => [
                    'Valid only for new users making their first investment',
                    'Minimum investment of ₹10,000 required',
                    'Cannot be combined with other offers',
                    'Offer valid until ' . Carbon::now()->addMonths(3)->format('d M Y'),
                ],
                'is_featured' => true,
            ],
            [
                'title' => 'Diwali Special - 10% Extra Units',
                'subtitle' => 'Celebrate Diwali with extra investment units',
                'code' => 'DIWALI10',
                'description' => 'Get 10% extra units on all investments above ₹25,000 during the festive season. Limited time Diwali special offer!',
                'long_description' => '<p>Celebrate the festival of lights with our special Diwali offer! Get 10% extra units on all your investments above ₹25,000.</p><p>This means if you invest ₹25,000, you will receive units worth ₹27,500 - giving you instant 10% bonus units.</p>',
                'status' => 'active',
                'discount_type' => 'percentage',
                'discount_percent' => 10.00,
                'discount_amount' => null,
                'min_investment' => 25000.00,
                'max_discount' => 5000.00,
                'usage_limit' => 500,
                'usage_count' => 128,
                'user_usage_limit' => 1,
                'expiry' => Carbon::now()->addDays(15),
                'image_url' => '/storage/offers/diwali-offer.jpg',
                'hero_image' => '/storage/offers/diwali-hero.jpg',
                'video_url' => null,
                'features' => [
                    '10% extra units on investments',
                    'Minimum investment ₹25,000',
                    'Maximum bonus ₹5,000',
                    'Limited time festive offer',
                ],
                'terms' => [
                    'Applicable on investments of ₹25,000 and above',
                    'Maximum bonus units capped at ₹5,000',
                    'One time use per user',
                    'Valid for 15 days only',
                ],
                'is_featured' => true,
            ],
            [
                'title' => 'Refer & Earn - Double Rewards',
                'subtitle' => '2X referral bonus for limited time',
                'code' => 'REFER2X',
                'description' => 'Earn double referral rewards for the next 7 days! Get ₹2,000 for every friend who invests ₹50,000 or more.',
                'long_description' => '<p>Our biggest referral bonus ever! For the next 7 days, earn 2X rewards on all successful referrals.</p><p>When your friend invests ₹50,000 or more using your referral link, both of you get amazing rewards!</p>',
                'status' => 'active',
                'discount_type' => 'fixed_amount',
                'discount_percent' => null,
                'discount_amount' => 2000.00,
                'min_investment' => 50000.00,
                'max_discount' => null,
                'usage_limit' => null, // Unlimited
                'usage_count' => 342,
                'user_usage_limit' => null, // Unlimited per user
                'expiry' => Carbon::now()->addDays(7),
                'image_url' => '/storage/offers/referral-offer.jpg',
                'hero_image' => '/storage/offers/referral-hero.jpg',
                'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                'features' => [
                    'Earn ₹2,000 per successful referral',
                    'Your friend also gets ₹500 bonus',
                    'No limit on number of referrals',
                    'Instant credit to wallet',
                ],
                'terms' => [
                    'Referral must invest minimum ₹50,000',
                    'Reward credited after investment is confirmed',
                    'Both referrer and referee get bonuses',
                    'Offer valid for 7 days only',
                ],
                'is_featured' => false,
            ],
            [
                'title' => 'Weekend Flash Sale - 5% Off',
                'subtitle' => 'Special weekend discount on all plans',
                'code' => 'WEEKEND5',
                'description' => 'This weekend only! Get 5% discount on all investments. No minimum investment required.',
                'long_description' => '<p>Make the most of your weekend with our flash sale! Get 5% discount on all investment plans with no minimum investment requirement.</p>',
                'status' => 'active',
                'discount_type' => 'percentage',
                'discount_percent' => 5.00,
                'discount_amount' => null,
                'min_investment' => null,
                'max_discount' => 3000.00,
                'usage_limit' => 200,
                'usage_count' => 67,
                'user_usage_limit' => 1,
                'expiry' => Carbon::now()->addDays(2),
                'image_url' => '/storage/offers/weekend-offer.jpg',
                'hero_image' => '/storage/offers/weekend-hero.jpg',
                'video_url' => null,
                'features' => [
                    '5% discount on all investments',
                    'No minimum investment required',
                    'Maximum discount ₹3,000',
                    'Valid for 48 hours only',
                ],
                'terms' => [
                    'Valid for weekend only (48 hours)',
                    'Maximum discount capped at ₹3,000',
                    'Cannot be clubbed with other offers',
                    'One time use per user',
                ],
                'is_featured' => false,
            ],
            [
                'title' => 'Premium Plan - Exclusive 15% Off',
                'subtitle' => 'Special discount for premium investors',
                'code' => 'PREMIUM15',
                'description' => 'Exclusive 15% discount for investments of ₹1 lakh and above. Premium benefits for premium investors!',
                'long_description' => '<p>Join our elite group of premium investors and enjoy exclusive benefits! Get 15% discount on investments of ₹1 lakh and above.</p><p>This offer is designed for serious investors looking to build substantial pre-IPO portfolios.</p>',
                'status' => 'active',
                'discount_type' => 'percentage',
                'discount_percent' => 15.00,
                'discount_amount' => null,
                'min_investment' => 100000.00,
                'max_discount' => 25000.00,
                'usage_limit' => 100,
                'usage_count' => 23,
                'user_usage_limit' => 1,
                'expiry' => Carbon::now()->addMonths(2),
                'image_url' => '/storage/offers/premium-offer.jpg',
                'hero_image' => '/storage/offers/premium-hero.jpg',
                'video_url' => null,
                'features' => [
                    '15% discount on premium investments',
                    'Priority customer support',
                    'Exclusive investment opportunities',
                    'Maximum discount ₹25,000',
                ],
                'terms' => [
                    'Minimum investment of ₹1,00,000 required',
                    'Maximum discount ₹25,000',
                    'Valid for 2 months',
                    'Limited to first 100 users',
                ],
                'is_featured' => true,
            ],
            [
                'title' => 'Student Special - Flat ₹200 Off',
                'subtitle' => 'Special offer for student investors',
                'code' => 'STUDENT200',
                'description' => 'Students get ₹200 off on investments of ₹5,000 or more. Start investing early with this special student offer!',
                'long_description' => '<p>We believe in empowering young investors! Students can get a special discount of ₹200 on investments of ₹5,000 or more.</p><p>Start building your investment portfolio while you\'re still in college!</p>',
                'status' => 'active',
                'discount_type' => 'fixed_amount',
                'discount_percent' => null,
                'discount_amount' => 200.00,
                'min_investment' => 5000.00,
                'max_discount' => null,
                'usage_limit' => 500,
                'usage_count' => 89,
                'user_usage_limit' => 1,
                'expiry' => Carbon::now()->addMonths(6),
                'image_url' => '/storage/offers/student-offer.jpg',
                'hero_image' => '/storage/offers/student-hero.jpg',
                'video_url' => null,
                'features' => [
                    '₹200 instant discount',
                    'Low minimum investment of ₹5,000',
                    'Perfect for student budgets',
                    'Valid for 6 months',
                ],
                'terms' => [
                    'Valid student ID required for verification',
                    'Minimum investment ₹5,000',
                    'One time use per student',
                    'Valid for 6 months from activation',
                ],
                'is_featured' => false,
            ],
        ];

        foreach ($offers as $offer) {
            Offer::create($offer);
        }

        $this->command->info('✅ Created ' . count($offers) . ' offers');
        $this->command->info('   - Active offers: ' . Offer::where('status', 'active')->count());
        $this->command->info('   - Featured offers: ' . Offer::where('is_featured', true)->count());
    }
}
