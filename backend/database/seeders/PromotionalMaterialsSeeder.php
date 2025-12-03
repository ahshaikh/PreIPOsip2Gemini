<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PromotionalMaterial;
use Illuminate\Support\Facades\DB;

class PromotionalMaterialsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('📦 Seeding Promotional Materials...');

        // Clear existing materials
        DB::table('promotional_material_downloads')->truncate();
        DB::table('promotional_materials')->truncate();

        $materials = [
            // Banners & Images
            [
                'title' => 'PreIPO SIP Facebook Cover Banner',
                'description' => 'High-quality Facebook cover banner for promoting PreIPO SIP investments.',
                'category' => 'banners',
                'type' => 'image',
                'file_url' => '/storage/materials/banners/facebook-cover.jpg',
                'file_name' => 'preipo-facebook-cover.jpg',
                'file_size' => 524288, // 512 KB
                'thumbnail_url' => '/storage/materials/thumbnails/facebook-cover-thumb.jpg',
                'preview_url' => '/storage/materials/banners/facebook-cover.jpg',
                'dimensions' => '1920x1080',
                'is_active' => true,
            ],
            [
                'title' => 'Instagram Square Post',
                'description' => 'Perfect square format for Instagram posts about pre-IPO investment opportunities.',
                'category' => 'social',
                'type' => 'image',
                'file_url' => '/storage/materials/social/instagram-square.jpg',
                'file_name' => 'preipo-instagram-post.jpg',
                'file_size' => 358400, // 350 KB
                'thumbnail_url' => '/storage/materials/thumbnails/instagram-square-thumb.jpg',
                'preview_url' => '/storage/materials/social/instagram-square.jpg',
                'dimensions' => '1080x1080',
                'is_active' => true,
            ],
            [
                'title' => 'WhatsApp Story Template',
                'description' => 'Vertical format banner optimized for WhatsApp status and Instagram stories.',
                'category' => 'social',
                'type' => 'image',
                'file_url' => '/storage/materials/social/whatsapp-story.jpg',
                'file_name' => 'preipo-whatsapp-story.jpg',
                'file_size' => 409600, // 400 KB
                'thumbnail_url' => '/storage/materials/thumbnails/whatsapp-story-thumb.jpg',
                'preview_url' => '/storage/materials/social/whatsapp-story.jpg',
                'dimensions' => '1080x1920',
                'is_active' => true,
            ],
            [
                'title' => 'LinkedIn Banner',
                'description' => 'Professional banner for LinkedIn posts and company page.',
                'category' => 'banners',
                'type' => 'image',
                'file_url' => '/storage/materials/banners/linkedin-banner.jpg',
                'file_name' => 'preipo-linkedin-banner.jpg',
                'file_size' => 614400, // 600 KB
                'thumbnail_url' => '/storage/materials/thumbnails/linkedin-banner-thumb.jpg',
                'preview_url' => '/storage/materials/banners/linkedin-banner.jpg',
                'dimensions' => '1584x396',
                'is_active' => true,
            ],
            [
                'title' => 'Twitter Header Image',
                'description' => 'Eye-catching Twitter/X header image to attract potential investors.',
                'category' => 'banners',
                'type' => 'image',
                'file_url' => '/storage/materials/banners/twitter-header.jpg',
                'file_name' => 'preipo-twitter-header.jpg',
                'file_size' => 471040, // 460 KB
                'thumbnail_url' => '/storage/materials/thumbnails/twitter-header-thumb.jpg',
                'preview_url' => '/storage/materials/banners/twitter-header.jpg',
                'dimensions' => '1500x500',
                'is_active' => true,
            ],

            // Videos
            [
                'title' => 'PreIPO SIP Explainer Video',
                'description' => '60-second explainer video about how PreIPO SIP works and its benefits.',
                'category' => 'videos',
                'type' => 'video',
                'file_url' => '/storage/materials/videos/explainer-60s.mp4',
                'file_name' => 'preipo-explainer-60s.mp4',
                'file_size' => 8388608, // 8 MB
                'thumbnail_url' => '/storage/materials/thumbnails/explainer-video-thumb.jpg',
                'preview_url' => '/storage/materials/videos/explainer-60s.mp4',
                'dimensions' => '1920x1080',
                'is_active' => true,
            ],
            [
                'title' => 'Success Stories Video',
                'description' => 'Testimonial video featuring successful investors sharing their experiences.',
                'category' => 'videos',
                'type' => 'video',
                'file_url' => '/storage/materials/videos/success-stories.mp4',
                'file_name' => 'preipo-success-stories.mp4',
                'file_size' => 15728640, // 15 MB
                'thumbnail_url' => '/storage/materials/thumbnails/success-stories-thumb.jpg',
                'preview_url' => '/storage/materials/videos/success-stories.mp4',
                'dimensions' => '1920x1080',
                'is_active' => true,
            ],
            [
                'title' => 'WhatsApp Status Video - 30s',
                'description' => 'Short 30-second video optimized for WhatsApp status and stories.',
                'category' => 'videos',
                'type' => 'video',
                'file_url' => '/storage/materials/videos/whatsapp-status-30s.mp4',
                'file_name' => 'preipo-whatsapp-30s.mp4',
                'file_size' => 5242880, // 5 MB
                'thumbnail_url' => '/storage/materials/thumbnails/whatsapp-status-thumb.jpg',
                'preview_url' => '/storage/materials/videos/whatsapp-status-30s.mp4',
                'dimensions' => '1080x1920',
                'is_active' => true,
            ],

            // Documents & PDFs
            [
                'title' => 'PreIPO Investment Guide PDF',
                'description' => 'Comprehensive guide to pre-IPO investments with all essential information.',
                'category' => 'documents',
                'type' => 'document',
                'file_url' => '/storage/materials/documents/investment-guide.pdf',
                'file_name' => 'preipo-investment-guide.pdf',
                'file_size' => 2097152, // 2 MB
                'thumbnail_url' => '/storage/materials/thumbnails/investment-guide-thumb.jpg',
                'preview_url' => null,
                'dimensions' => 'A4',
                'is_active' => true,
            ],
            [
                'title' => 'FAQ Brochure',
                'description' => 'Frequently asked questions about PreIPO SIP in an easy-to-share format.',
                'category' => 'documents',
                'type' => 'document',
                'file_url' => '/storage/materials/documents/faq-brochure.pdf',
                'file_name' => 'preipo-faq-brochure.pdf',
                'file_size' => 1048576, // 1 MB
                'thumbnail_url' => '/storage/materials/thumbnails/faq-brochure-thumb.jpg',
                'preview_url' => null,
                'dimensions' => 'A4',
                'is_active' => true,
            ],
            [
                'title' => 'Risk Disclosure Statement',
                'description' => 'Official risk disclosure document for sharing with potential investors.',
                'category' => 'documents',
                'type' => 'document',
                'file_url' => '/storage/materials/documents/risk-disclosure.pdf',
                'file_name' => 'risk-disclosure.pdf',
                'file_size' => 524288, // 512 KB
                'thumbnail_url' => '/storage/materials/thumbnails/risk-disclosure-thumb.jpg',
                'preview_url' => null,
                'dimensions' => 'A4',
                'is_active' => true,
            ],

            // Presentations
            [
                'title' => 'PreIPO SIP Business Presentation',
                'description' => 'Complete PowerPoint presentation for explaining PreIPO SIP to potential investors.',
                'category' => 'presentations',
                'type' => 'document',
                'file_url' => '/storage/materials/presentations/business-presentation.pptx',
                'file_name' => 'preipo-business-presentation.pptx',
                'file_size' => 5242880, // 5 MB
                'thumbnail_url' => '/storage/materials/thumbnails/presentation-thumb.jpg',
                'preview_url' => null,
                'dimensions' => '16:9',
                'is_active' => true,
            ],
            [
                'title' => 'Investment Pitch Deck',
                'description' => 'Professional pitch deck with market analysis and investment opportunities.',
                'category' => 'presentations',
                'type' => 'document',
                'file_url' => '/storage/materials/presentations/pitch-deck.pdf',
                'file_name' => 'preipo-pitch-deck.pdf',
                'file_size' => 3145728, // 3 MB
                'thumbnail_url' => '/storage/materials/thumbnails/pitch-deck-thumb.jpg',
                'preview_url' => null,
                'dimensions' => '16:9',
                'is_active' => true,
            ],

            // Additional Social Media Materials
            [
                'title' => 'Referral Program Banner',
                'description' => 'Promotional banner for sharing your referral link and earning rewards.',
                'category' => 'social',
                'type' => 'image',
                'file_url' => '/storage/materials/social/referral-banner.jpg',
                'file_name' => 'referral-program-banner.jpg',
                'file_size' => 409600, // 400 KB
                'thumbnail_url' => '/storage/materials/thumbnails/referral-banner-thumb.jpg',
                'preview_url' => '/storage/materials/social/referral-banner.jpg',
                'dimensions' => '1200x630',
                'is_active' => true,
            ],
            [
                'title' => 'Investment Calculator Infographic',
                'description' => 'Visual infographic showing potential returns with SIP investments.',
                'category' => 'social',
                'type' => 'image',
                'file_url' => '/storage/materials/social/calculator-infographic.jpg',
                'file_name' => 'investment-calculator-infographic.jpg',
                'file_size' => 716800, // 700 KB
                'thumbnail_url' => '/storage/materials/thumbnails/calculator-infographic-thumb.jpg',
                'preview_url' => '/storage/materials/social/calculator-infographic.jpg',
                'dimensions' => '1080x1350',
                'is_active' => true,
            ],
        ];

        foreach ($materials as $material) {
            PromotionalMaterial::create($material);
        }

        $this->command->info('✅ Created ' . count($materials) . ' promotional materials');
        $this->command->info('   - Banners: ' . collect($materials)->where('category', 'banners')->count());
        $this->command->info('   - Videos: ' . collect($materials)->where('category', 'videos')->count());
        $this->command->info('   - Documents: ' . collect($materials)->where('category', 'documents')->count());
        $this->command->info('   - Social Media: ' . collect($materials)->where('category', 'social')->count());
        $this->command->info('   - Presentations: ' . collect($materials)->where('category', 'presentations')->count());
    }
}
