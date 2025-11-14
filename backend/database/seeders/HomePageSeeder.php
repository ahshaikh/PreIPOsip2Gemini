<?php
// V-FINAL-1730-311

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Page;

class HomePageSeeder extends Seeder
{
    public function run(): void
    {
        $content = [
            'hero' => [
                'badge' => '🎉 100% Zero Fees Forever!',
                'title_prefix' => "India's First",
                'title_highlight' => '100% FREE',
                'title_suffix' => 'Pre-IPO SIP Platform',
                'description' => 'Invest in tomorrow\'s unicorns today! Get 10-20% guaranteed bonuses + portfolio gains. No platform fees. No exit fees. No hidden charges.',
                'features' => [
                    'Zero Platform Fees (Save ₹54,000)',
                    'Zero Exit Fees (Save ₹6,000)',
                    '10% Guaranteed Bonuses (Earn ₹36,000)'
                ],
                'cta_primary' => 'Start Investing Free',
                'cta_secondary' => 'Calculate Returns 📊'
            ],
            'value_props' => [
                'title' => 'Why Choose PreIPO SIP?',
                'items' => [
                    [
                        'title' => 'Zero Fees',
                        'desc' => 'Save thousands with zero platform fees and zero exit fees.'
                    ],
                    [
                        'title' => '10% Guaranteed Bonuses',
                        'desc' => 'Earn a 10% bonus on your investments through our unique model.'
                    ],
                    [
                        'title' => 'Safe & Secure',
                        'desc' => 'SEBI compliant processes and bank-grade security for your peace of mind.'
                    ]
                ]
            ],
            'how_it_works' => [
                'title' => 'Start Investing in 3 Simple Steps',
                'steps' => [
                    [
                        'title' => '1. Sign Up & Complete KYC',
                        'desc' => 'Create your free account and verify your identity in 5 minutes.'
                    ],
                    [
                        'title' => '2. Choose Plan & Start SIP',
                        'desc' => 'Select an investment plan that fits your goals, starting from ₹1,000/month.'
                    ],
                    [
                        'title' => '3. Earn Bonuses & Track Growth',
                        'desc' => 'Make monthly payments, earn bonuses, and watch your portfolio grow.'
                    ]
                ],
                'cta' => 'Get Started Free'
            ]
        ];

        Page::updateOrCreate(
            ['slug' => 'home'],
            [
                'title' => 'Home',
                'content' => $content, // Casts to JSON automatically
                'status' => 'published',
                'seo_meta' => ['title' => 'PreIPO SIP - Zero Fee Investment', 'description' => 'India\'s first zero-fee Pre-IPO SIP platform.']
            ]
        );
    }
}