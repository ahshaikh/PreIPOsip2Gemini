<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Offer;
use Carbon\Carbon;

class OffersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command?->info('🎁 Seeding offers (production-safe)...');

        $offers = [
            [
                'code' => 'WELCOME500',
                'title' => 'Welcome Bonus - New Investors',
                'subtitle' => 'Start your investment journey with an exclusive welcome offer',
                'description' => 'Get ₹500 instant discount on your first investment of ₹10,000 or more.',
                'long_description' => '<p>We\'re excited to welcome you to PreIPO SIP! As a special welcome gift, enjoy an instant discount on your first investment.</p>',
                'status' => 'active',
                'discount_type' => 'fixed_amount',
                'discount_amount' => 500.00,
                'discount_percent' => null,
                'min_investment' => 10000.00,
                'max_discount' => null,
                'usage_limit' => 1000,
                'user_usage_limit' => 1,
                'expiry' => Carbon::now()->addMonths(3),
                'image_url' => '/storage/offers/welcome-offer.jpg',
                'hero_image' => '/storage/offers/welcome-hero.jpg',
                'video_url' => null,
                'features' => [
                    '₹500 instant discount on first investment',
                    'No hidden charges',
                    'Easy to apply',
                ],
                'terms' => [
                    'Valid only for first-time investors',
                    'Minimum investment ₹10,000',
                    'Cannot be combined with other offers',
                ],
                'is_featured' => true,
            ],

            [
                'code' => 'DIWALI10',
                'title' => 'Diwali Special - 10% Extra Units',
                'subtitle' => 'Celebrate Diwali with extra investment units',
                'description' => 'Get 10% extra units on investments above ₹25,000 during the festive season.',
                'long_description' => '<p>Celebrate Diwali with our festive bonus! Enjoy extra units on qualifying investments.</p>',
                'status' => 'active',
                'discount_type' => 'percentage',
                'discount_percent' => 10.00,
                'discount_amount' => null,
                'min_investment' => 25000.00,
                'max_discount' => 5000.00,
                'usage_limit' => 500,
                'user_usage_limit' => 1,
                'expiry' => Carbon::now()->addDays(15),
                'image_url' => '/storage/offers/diwali-offer.jpg',
                'hero_image' => '/storage/offers/diwali-hero.jpg',
                'video_url' => null,
                'features' => [
                    '10% extra units',
                    'Festive special',
                    'Limited-time offer',
                ],
                'terms' => [
                    'Minimum investment ₹25,000',
                    'Maximum bonus ₹5,000',
                    'One-time use per user',
                ],
                'is_featured' => true,
            ],

            [
                'code' => 'REFER2X',
                'title' => 'Refer & Earn - Double Rewards',
                'subtitle' => '2X referral bonus for a limited time',
                'description' => 'Earn ₹2,000 for every successful referral investing ₹50,000 or more.',
                'long_description' => '<p>For a limited time, earn double rewards on referrals. Both you and your friend benefit!</p>',
                'status' => 'active',
                'discount_type' => 'fixed_amount',
                'discount_amount' => 2000.00,
                'discount_percent' => null,
                'min_investment' => 50000.00,
                'max_discount' => null,
                'usage_limit' => null, // Unlimited
                'user_usage_limit' => null, // Unlimited per user
                'expiry' => Carbon::now()->addDays(7),
                'image_url' => '/storage/offers/referral-offer.jpg',
                'hero_image' => '/storage/offers/referral-hero.jpg',
                'video_url' => null,
                'features' => [
                    '₹2,000 per successful referral',
                    'No referral limit',
                ],
                'terms' => [
                    'Referral investment minimum ₹50,000',
                    'Rewards credited post-confirmation',
                ],
                'is_featured' => false,
            ],

            [
                'code' => 'WEEKEND5',
                'title' => 'Weekend Flash Sale - 5% Off',
                'subtitle' => 'Special weekend discount on all plans',
                'description' => 'Get 5% discount on all investments this weekend.',
                'long_description' => '<p>Enjoy a special weekend flash sale with instant savings.</p>',
                'status' => 'active',
                'discount_type' => 'percentage',
                'discount_percent' => 5.00,
                'discount_amount' => null,
                'min_investment' => null,
                'max_discount' => 3000.00,
                'usage_limit' => 200,
                'user_usage_limit' => 1,
                'expiry' => Carbon::now()->addDays(2),
                'image_url' => '/storage/offers/weekend-offer.jpg',
                'hero_image' => '/storage/offers/weekend-hero.jpg',
                'video_url' => null,
                'features' => [
                    '5% instant discount',
                    'No minimum investment',
                ],
                'terms' => [
                    'Valid for 48 hours only',
                    'Maximum discount ₹3,000',
                ],
                'is_featured' => false,
            ],

            [
                'code' => 'PREMIUM15',
                'title' => 'Premium Plan - Exclusive 15% Off',
                'subtitle' => 'Special discount for premium investors',
                'description' => 'Exclusive 15% discount on investments of ₹1 lakh and above.',
                'long_description' => '<p>Premium investors enjoy exclusive benefits and priority support.</p>',
                'status' => 'active',
                'discount_type' => 'percentage',
                'discount_percent' => 15.00,
                'discount_amount' => null,
                'min_investment' => 100000.00,
                'max_discount' => 25000.00,
                'usage_limit' => 100,
                'user_usage_limit' => 1,
                'expiry' => Carbon::now()->addMonths(2),
                'image_url' => '/storage/offers/premium-offer.jpg',
                'hero_image' => '/storage/offers/premium-hero.jpg',
                'video_url' => null,
                'features' => [
                    '15% premium discount',
                    'Priority customer support',
                ],
                'terms' => [
                    'Minimum investment ₹1,00,000',
                    'Maximum discount ₹25,000',
                ],
                'is_featured' => true,
            ],

            [
                'code' => 'STUDENT200',
                'title' => 'Student Special - Flat ₹200 Off',
                'subtitle' => 'Special offer for student investors',
                'description' => 'Students get ₹200 off on investments of ₹5,000 or more.',
                'long_description' => '<p>Empowering students to start investing early.</p>',
                'status' => 'active',
                'discount_type' => 'fixed_amount',
                'discount_amount' => 200.00,
                'discount_percent' => null,
                'min_investment' => 5000.00,
                'max_discount' => null,
                'usage_limit' => 500,
                'user_usage_limit' => 1,
                'expiry' => Carbon::now()->addMonths(6),
                'image_url' => '/storage/offers/student-offer.jpg',
                'hero_image' => '/storage/offers/student-hero.jpg',
                'video_url' => null,
                'features' => [
                    '₹200 instant discount',
                    'Low minimum investment',
                ],
                'terms' => [
                    'Valid student ID required',
                    'One-time use per student',
                ],
                'is_featured' => false,
            ],
        ];

        foreach ($offers as $offer) {
            // Never overwrite live counters or analytics
            unset($offer['usage_count']);

            Offer::updateOrCreate(
                ['code' => $offer['code']],
                $offer
            );
        }

        $this->command?->info('✅ Offers seeded successfully (production-safe)');
    }
}
