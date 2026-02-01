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
                'description' => 'Access curated pre-IPO investment opportunities with a SIP-based approach — built for long-term investors who value transparency and control. No platform fees. No exit fees. No hidden charges.',
                'features' => [
                    'Zero Platform Fees (Save up to ₹54,000 over time)',
                    'Zero Exit Fees — invest with flexibility',
                    'Transparent pricing with no hidden charges'
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
                'title' => 'Get started in 3 simple steps',
                'steps' => [
                    [
                        'title' => '1. Choose Your Plan',
                        'desc' => 'Select a monthly SIP amount between ₹1,000 and ₹25,000. All plans offer access to curated pre-IPO opportunities with transparent, zero-fee pricing.'
                    ],
                    [
                        'title' => '2. Complete KYC',
                        'desc' => 'Complete a quick KYC using Aadhaar, PAN, and your Demat account. The process is secure and follows applicable SEBI guidelines.'
                    ],
                    [
                        'title' => '3. Start Investing',
                        'desc' => 'Invest regularly through a SIP-based approach and track your investments in selected pre-IPO opportunities from a single dashboard.'
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