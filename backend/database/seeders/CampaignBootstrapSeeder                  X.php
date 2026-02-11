<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Campaign;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CampaignBootstrapSeeder extends Seeder
{
    /**
     * IMPORTANT: This seeder should ONLY be used for initial bootstrap.
     *
     * In production, all campaigns should be created via the Admin Campaign Management UI.
     * This seeder creates ONE example campaign to demonstrate the system.
     *
     * Business teams should use the admin panel at /admin/campaigns to:
     * - Create new campaigns
     * - Set approval workflows
     * - Schedule campaigns
     * - Monitor usage analytics
     */
    public function run(): void
    {
        // Only seed if campaigns table is empty
        if (Campaign::count() > 0) {
            $this->command->warn('⚠️  Campaigns already exist. Skipping bootstrap seeder.');
            $this->command->warn('   Use Admin Panel to manage campaigns: /admin/campaigns');
            return;
        }

        $this->command->info('🚀 Bootstrapping Campaign Management System...');

        // Get the first admin user to set as creator/approver
        $adminUser = User::whereHas('roles', function ($query) {
            $query->where('name', 'admin')->orWhere('name', 'super-admin');
        })->first();

        if (!$adminUser) {
            $this->command->error('❌ No admin user found. Please create an admin user first.');
            return;
        }

        // Create ONE example welcome campaign
        $campaign = Campaign::create([
            'title' => 'Welcome Bonus - New Investors',
            'subtitle' => 'Get ₹500 off on your first investment',
            'code' => 'WELCOME500',
            'description' => 'Welcome to PreIPOsip! Get ₹500 discount on your first investment of ₹10,000 or more.',
            'long_description' => 'As a welcome gesture, we\'re offering you ₹500 off on your first investment. This is our way of saying thank you for trusting us with your investment journey. Start building your pre-IPO portfolio today!',
            'discount_type' => 'fixed_amount',
            'discount_percent' => null,
            'discount_amount' => 500.00,
            'min_investment' => 10000.00,
            'max_discount' => null,
            'usage_limit' => 1000, // Total 1000 uses
            'usage_count' => 0,
            'user_usage_limit' => 1, // One per user
            'start_at' => now(),
            'end_at' => now()->addMonths(3),
            'image_url' => 'https://via.placeholder.com/400x200/4F46E5/ffffff?text=Welcome+Bonus',
            'hero_image' => 'https://via.placeholder.com/1200x400/4F46E5/ffffff?text=Welcome+to+PreIPOsip',
            'video_url' => null,
            'features' => [
                '₹500 instant discount on first investment',
                'Valid for 3 months from signup',
                'Minimum investment: ₹10,000',
                'Can be used on any active deal',
            ],
            'terms' => [
                'Valid for new users only',
                'One-time use per user',
                'Cannot be combined with other offers',
                'Minimum investment of ₹10,000 required',
                'Valid for 3 months from campaign start date',
                'PreIPOsip reserves the right to modify or cancel this campaign',
            ],
            'is_featured' => true,
            'is_active' => true,
            'created_by' => $adminUser->id,
            'approved_by' => $adminUser->id,
            'approved_at' => now(),
        ]);

        $this->command->info('✅ Bootstrap campaign created successfully!');
        $this->command->info('');
        $this->command->info('📊 Campaign Details:');
        $this->command->info("   Code: {$campaign->code}");
        $this->command->info("   Discount: ₹{$campaign->discount_amount}");
        $this->command->info("   Min Investment: ₹{$campaign->min_investment}");
        $this->command->info("   Valid Until: {$campaign->end_at->format('M d, Y')}");
        $this->command->info('');
        $this->command->info('🎯 Next Steps:');
        $this->command->info('   1. Access Admin Panel: /admin/campaigns');
        $this->command->info('   2. Create new campaigns via UI');
        $this->command->info('   3. Set approval workflows');
        $this->command->info('   4. Monitor campaign analytics');
        $this->command->info('');
        $this->command->warn('⚠️  IMPORTANT: Do NOT use seeders for live campaigns!');
        $this->command->warn('   All production campaigns MUST be created via Admin UI.');
    }
}
