<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserKyc;
use App\Models\KycDocument;
use App\Models\Product;
use App\Models\ProductHighlight;
use App\Models\ProductFounder;
use App\Models\ProductFundingRound;
use App\Models\ProductKeyMetric;
use App\Models\ProductRiskDisclosure;
use App\Models\ProductPriceHistory;
use App\Models\Plan;
use App\Models\PlanFeature;
use App\Models\PlanConfig;
use App\Models\Subscription;
use App\Models\Payment;
use App\Models\Wallet;
use App\Models\Transaction;
use App\Models\Withdrawal;
use App\Models\UserInvestment;
use App\Models\BulkPurchase;
use App\Models\BonusTransaction;
use App\Models\ReferralCampaign;
use App\Models\Referral;
use App\Models\SupportTicket;
use App\Models\SupportMessage;
use App\Models\Page;
use App\Models\BlogPost;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Banner;
use App\Models\KbCategory;
use App\Models\KbArticle;
use App\Models\Redirect;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class ComprehensiveFakerSeeder extends Seeder
{
    /**
     * Run the comprehensive database seeds.
     * Seeds data for all three user classes: public, user, and admin.
     */
    public function run(): void
    {
        $this->command->info('🌱 Starting comprehensive database seeding...');

        // Create Roles and Permissions
        $this->seedRolesAndPermissions();

        // 1. Create Admin Users (3)
        $this->command->info('Creating admin users...');
        $admins = $this->seedAdminUsers();

        // 2. Create Regular Users (50)
        $this->command->info('Creating regular users...');
        $users = $this->seedRegularUsers();

        // 3. Create Public/Pending Users (20)
        $this->command->info('Creating public/pending users...');
        $publicUsers = $this->seedPublicUsers();

        $allUsers = collect($admins)->merge($users)->merge($publicUsers);

        // 4. Create Products with all related data (15 products)
        $this->command->info('Creating products with all related data...');
        $products = $this->seedProducts();

        // 5. Create Plans with features and configs (5 plans)
        $this->command->info('Creating subscription plans...');
        $plans = $this->seedPlans();

        // 6. Create Bulk Purchases (10)
        $this->command->info('Creating bulk purchases...');
        $this->seedBulkPurchases($products, $admins);

        // 7. Create Subscriptions and Payments for users (100 subscriptions)
        $this->command->info('Creating subscriptions and payments...');
        $subscriptions = $this->seedSubscriptionsAndPayments($users, $plans);

        // 8. Create Wallets with Transactions (all users)
        $this->command->info('Creating wallet transactions...');
        $this->seedWalletTransactions($allUsers);

        // 9. Create Withdrawals (20)
        $this->command->info('Creating withdrawal requests...');
        $this->seedWithdrawals($users, $admins);

        // 10. Create User Investments (150)
        $this->command->info('Creating user investments...');
        $this->seedUserInvestments($users, $products, $subscriptions);

        // 11. Create Bonus and Referral System
        $this->command->info('Creating bonus and referral system...');
        $this->seedBonusAndReferrals($users, $subscriptions);

        // 12. Create Support Tickets and Messages (40 tickets)
        $this->command->info('Creating support tickets...');
        $this->seedSupportSystem($allUsers, $admins);

        // 13. Create CMS Pages (15 pages)
        $this->command->info('Creating CMS pages...');
        $this->seedPages();

        // 14. Create Blog Posts (25 posts)
        $this->command->info('Creating blog posts...');
        $this->seedBlogPosts($admins);

        // 15. Create Menus and Menu Items
        $this->command->info('Creating menus...');
        $this->seedMenus();

        // 16. Create Banners (5 banners)
        $this->command->info('Creating banners...');
        $this->seedBanners();

        // 17. Create Knowledge Base
        $this->command->info('Creating knowledge base...');
        $this->seedKnowledgeBase($admins);

        // 18. Create Redirects (10 redirects)
        $this->command->info('Creating redirects...');
        $this->seedRedirects();

        // 19. Create Activity Logs (200 activities)
        $this->command->info('Creating activity logs...');
        $this->seedActivityLogs($allUsers);

        // 20. Create Notifications (100 notifications)
        $this->command->info('Creating notifications...');
        $this->seedNotifications($allUsers);

        $this->command->info('✅ Comprehensive seeding completed successfully!');
        $this->command->info('');
        $this->command->info('Summary:');
        $this->command->info('- Admin Users: ' . count($admins));
        $this->command->info('- Regular Users: ' . count($users));
        $this->command->info('- Public Users: ' . count($publicUsers));
        $this->command->info('- Products: ' . count($products));
        $this->command->info('- Plans: ' . count($plans));
        $this->command->info('- Subscriptions: ' . count($subscriptions));
    }

    private function seedRolesAndPermissions(): void
    {
        // Create roles if they don't exist
        if (!Role::where('name', 'admin')->exists()) {
            Role::create(['name' => 'admin', 'guard_name' => 'web']);
        }
        if (!Role::where('name', 'user')->exists()) {
            Role::create(['name' => 'user', 'guard_name' => 'web']);
        }
        if (!Role::where('name', 'manager')->exists()) {
            Role::create(['name' => 'manager', 'guard_name' => 'web']);
        }
    }

    private function seedAdminUsers(): array
    {
        $admins = [];

        // Create Super Admin
        $superAdmin = User::create([
            'username' => 'superadmin',
            'email' => 'admin@preipo.com',
            'mobile' => '9999999999',
            'email_verified_at' => now(),
            'mobile_verified_at' => now(),
            'password' => Hash::make('password'),
            'referral_code' => Str::upper(Str::random(10)),
            'status' => 'active',
        ]);
        $superAdmin->assignRole('admin');
        $this->createUserProfile($superAdmin, 'Super', 'Admin');
        $this->createUserKyc($superAdmin, 'verified');
        $admins[] = $superAdmin;

        // Create 2 more admin users
        for ($i = 1; $i <= 2; $i++) {
            $admin = User::create([
                'username' => 'admin' . $i,
                'email' => 'admin' . $i . '@preipo.com',
                'mobile' => '999999999' . $i,
                'email_verified_at' => now(),
                'mobile_verified_at' => now(),
                'password' => Hash::make('password'),
                'referral_code' => Str::upper(Str::random(10)),
                'status' => 'active',
            ]);
            $admin->assignRole('admin');
            $this->createUserProfile($admin, 'Admin', 'User ' . $i);
            $this->createUserKyc($admin, 'verified');
            $admins[] = $admin;
        }

        return $admins;
    }

    private function seedRegularUsers(): array
    {
        $users = [];

        for ($i = 1; $i <= 50; $i++) {
            $user = User::create([
                'username' => 'user' . $i,
                'email' => 'user' . $i . '@example.com',
                'mobile' => '9' . str_pad($i, 9, '0', STR_PAD_LEFT),
                'email_verified_at' => now(),
                'mobile_verified_at' => now(),
                'password' => Hash::make('password'),
                'referral_code' => Str::upper(Str::random(10)),
                'status' => 'active',
            ]);
            $user->assignRole('user');

            $this->createUserProfile($user, 'User', 'Number ' . $i);

            // Mix of KYC statuses
            $kycStatus = fake()->randomElement(['verified', 'verified', 'submitted', 'pending', 'rejected']);
            $this->createUserKyc($user, $kycStatus);

            $users[] = $user;
        }

        return $users;
    }

    private function seedPublicUsers(): array
    {
        $publicUsers = [];

        for ($i = 1; $i <= 20; $i++) {
            $user = User::create([
                'username' => 'guest' . $i,
                'email' => 'guest' . $i . '@example.com',
                'mobile' => '8' . str_pad($i, 9, '0', STR_PAD_LEFT),
                'email_verified_at' => null,
                'mobile_verified_at' => null,
                'password' => Hash::make('password'),
                'referral_code' => Str::upper(Str::random(10)),
                'status' => 'pending',
            ]);

            $this->createUserProfile($user, 'Guest', 'User ' . $i);
            $this->createUserKyc($user, 'pending');

            $publicUsers[] = $user;
        }

        return $publicUsers;
    }

    private function createUserProfile(User $user, string $firstName, string $lastName): void
    {
        UserProfile::create([
            'user_id' => $user->id,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'dob' => fake()->date('Y-m-d', '-21 years'),
            'gender' => fake()->randomElement(['male', 'female', 'other']),
            'address_line_1' => fake()->streetAddress(),
            'address_line_2' => fake()->secondaryAddress(),
            'city' => fake()->city(),
            'state' => fake()->state(),
            'pincode' => fake()->numerify('######'),
            'country' => 'India',
        ]);
    }

    private function createUserKyc(User $user, string $status): void
    {
        $kyc = UserKyc::create([
            'user_id' => $user->id,
            'pan_number' => strtoupper(fake()->bothify('?????####?')),
            'aadhaar_number' => fake()->numerify('############'),
            'demat_account' => fake()->numerify('IN##########'),
            'bank_account' => fake()->numerify('##############'),
            'bank_ifsc' => strtoupper(fake()->bothify('????0######')),
            'status' => $status,
            'verified_at' => $status === 'verified' ? now() : null,
            'submitted_at' => in_array($status, ['verified', 'submitted', 'rejected']) ? now()->subDays(2) : null,
            'rejection_reason' => $status === 'rejected' ? 'Documents not clear' : null,
        ]);

        // Create KYC documents
        if (in_array($status, ['verified', 'submitted', 'rejected'])) {
            foreach (['pan_card', 'aadhaar_front', 'aadhaar_back'] as $docType) {
                KycDocument::create([
                    'user_kyc_id' => $kyc->id,
                    'doc_type' => $docType,
                    'file_path' => 'kyc/' . fake()->uuid() . '.pdf',
                    'file_name' => $docType . '.pdf',
                    'mime_type' => 'application/pdf',
                    'status' => $status === 'verified' ? 'approved' : ($status === 'rejected' ? 'rejected' : 'pending'),
                    'verified_at' => $status === 'verified' ? now() : null,
                ]);
            }
        }
    }

    private function seedProducts(): array
    {
        $products = Product::factory(15)->create();

        foreach ($products as $product) {
            // Create highlights (3-5 per product)
            ProductHighlight::factory(rand(3, 5))->create(['product_id' => $product->id]);

            // Create founders (2-4 per product)
            ProductFounder::factory(rand(2, 4))->create(['product_id' => $product->id]);

            // Create funding rounds (2-5 per product)
            ProductFundingRound::factory(rand(2, 5))->create(['product_id' => $product->id]);

            // Create key metrics (4-8 per product)
            ProductKeyMetric::factory(rand(4, 8))->create(['product_id' => $product->id]);

            // Create risk disclosures (3-6 per product)
            ProductRiskDisclosure::factory(rand(3, 6))->create(['product_id' => $product->id]);

            // Create price history (10-30 entries per product)
            for ($i = 0; $i < rand(10, 30); $i++) {
                ProductPriceHistory::create([
                    'product_id' => $product->id,
                    'price' => fake()->randomFloat(2, 100, 5000),
                    'recorded_at' => now()->subDays(rand(1, 365))->format('Y-m-d'),
                ]);
            }
        }

        return $products->all();
    }

    private function seedPlans(): array
    {
        $plans = Plan::factory(5)->create();

        foreach ($plans as $plan) {
            // Create plan features (5-10 per plan)
            PlanFeature::factory(rand(5, 10))->create(['plan_id' => $plan->id]);

            // Create plan configs (3-5 per plan)
            $configKeys = ['bonus_multiplier', 'transaction_fee', 'withdrawal_limit', 'features', 'benefits'];
            foreach (array_slice($configKeys, 0, rand(3, 5)) as $key) {
                PlanConfig::create([
                    'plan_id' => $plan->id,
                    'config_key' => $key,
                    'value' => json_encode(['enabled' => true, 'value' => rand(1, 100)]),
                ]);
            }
        }

        return $plans->all();
    }

    private function seedBulkPurchases(array $products, array $admins): void
    {
        foreach (array_slice($products, 0, 10) as $product) {
            BulkPurchase::factory()->create([
                'product_id' => $product->id,
                'admin_id' => fake()->randomElement($admins)->id,
            ]);
        }
    }

    private function seedSubscriptionsAndPayments(array $users, array $plans): array
    {
        $subscriptions = [];

        foreach (array_slice($users, 0, 40) as $user) {
            $plan = fake()->randomElement($plans);

            $subscription = Subscription::factory()->create([
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'amount' => $plan->monthly_amount,
            ]);

            $subscriptions[] = $subscription;

            // Create 1-12 payments for each subscription
            $paymentCount = rand(1, 12);
            for ($i = 0; $i < $paymentCount; $i++) {
                Payment::factory()->create([
                    'user_id' => $user->id,
                    'subscription_id' => $subscription->id,
                    'amount' => $plan->monthly_amount,
                    'status' => fake()->randomElement(['completed', 'completed', 'completed', 'pending', 'failed']),
                    'paid_at' => $i < $paymentCount - 1 ? now()->subMonths($paymentCount - $i - 1) : null,
                ]);
            }
        }

        return $subscriptions;
    }

    private function seedWalletTransactions(array $users): void
    {
        foreach ($users as $user) {
            $wallet = Wallet::where('user_id', $user->id)->first();

            if (!$wallet) {
                $wallet = Wallet::create([
                    'user_id' => $user->id,
                    'balance' => fake()->randomFloat(2, 0, 50000),
                    'locked_balance' => fake()->randomFloat(2, 0, 5000),
                ]);
            }

            // Create 5-15 transactions per wallet
            for ($i = 0; $i < rand(5, 15); $i++) {
                Transaction::factory()->create([
                    'user_id' => $user->id,
                    'wallet_id' => $wallet->id,
                ]);
            }
        }
    }

    private function seedWithdrawals(array $users, array $admins): void
    {
        for ($i = 0; $i < 20; $i++) {
            $user = fake()->randomElement($users);
            $wallet = Wallet::where('user_id', $user->id)->first();

            if ($wallet) {
                Withdrawal::factory()->create([
                    'user_id' => $user->id,
                    'wallet_id' => $wallet->id,
                    'admin_id' => fake()->randomElement($admins)->id,
                ]);
            }
        }
    }

    private function seedUserInvestments(array $users, array $products, array $subscriptions): void
    {
        foreach (array_slice($users, 0, 30) as $user) {
            // Create 2-8 investments per user
            for ($i = 0; $i < rand(2, 8); $i++) {
                $product = fake()->randomElement($products);
                $subscription = fake()->randomElement($subscriptions);
                $payment = Payment::where('subscription_id', $subscription->id)->where('status', 'completed')->first();
                $bulkPurchase = BulkPurchase::where('product_id', $product->id)->first();

                if ($payment && $bulkPurchase) {
                    UserInvestment::factory()->create([
                        'user_id' => $user->id,
                        'product_id' => $product->id,
                        'payment_id' => $payment->id,
                        'bulk_purchase_id' => $bulkPurchase->id,
                    ]);
                }
            }
        }
    }

    private function seedBonusAndReferrals(array $users, array $subscriptions): void
    {
        // Create referral campaigns
        $campaigns = [];
        for ($i = 0; $i < 3; $i++) {
            $campaigns[] = ReferralCampaign::factory()->create();
        }

        // Create referrals
        foreach (array_slice($users, 0, 20) as $i => $user) {
            if ($i > 0 && $i < 20) {
                Referral::factory()->create([
                    'referrer_id' => $users[$i - 1]->id,
                    'referred_id' => $user->id,
                    'referral_campaign_id' => fake()->randomElement($campaigns)->id,
                ]);
            }
        }

        // Create bonus transactions
        foreach (array_slice($subscriptions, 0, 30) as $subscription) {
            $payment = Payment::where('subscription_id', $subscription->id)->where('status', 'completed')->first();

            if ($payment) {
                BonusTransaction::factory()->create([
                    'user_id' => $subscription->user_id,
                    'subscription_id' => $subscription->id,
                    'payment_id' => $payment->id,
                ]);
            }
        }
    }

    private function seedSupportSystem(array $users, array $admins): void
    {
        for ($i = 0; $i < 40; $i++) {
            $user = fake()->randomElement($users);

            $ticket = SupportTicket::factory()->create([
                'user_id' => $user->id,
                'assigned_to' => fake()->optional(0.7)->randomElement($admins)?->id,
            ]);

            // Create 1-8 messages per ticket
            for ($j = 0; $j < rand(1, 8); $j++) {
                SupportMessage::factory()->create([
                    'support_ticket_id' => $ticket->id,
                    'user_id' => $j % 2 === 0 ? $user->id : fake()->randomElement($admins)->id,
                ]);
            }
        }
    }

    private function seedPages(): void
    {
        Page::factory(15)->create();
    }

    private function seedBlogPosts(array $admins): void
    {
        BlogPost::factory(25)->create([
            'author_id' => fake()->randomElement($admins)->id,
        ]);
    }

    private function seedMenus(): void
    {
        $menus = Menu::factory(3)->create();

        foreach ($menus as $menu) {
            MenuItem::factory(rand(5, 10))->create(['menu_id' => $menu->id]);
        }
    }

    private function seedBanners(): void
    {
        Banner::factory(5)->create();
    }

    private function seedKnowledgeBase(array $admins): void
    {
        $categories = KbCategory::factory(5)->create();

        foreach ($categories as $category) {
            KbArticle::factory(rand(3, 8))->create([
                'kb_category_id' => $category->id,
                'author_id' => fake()->randomElement($admins)->id,
            ]);
        }
    }

    private function seedRedirects(): void
    {
        Redirect::factory(10)->create();
    }

    private function seedActivityLogs(array $users): void
    {
        foreach ($users as $user) {
            ActivityLog::factory(rand(2, 10))->create([
                'user_id' => $user->id,
            ]);
        }
    }

    private function seedNotifications(array $users): void
    {
        foreach (array_slice($users, 0, 30) as $user) {
            for ($i = 0; $i < rand(2, 8); $i++) {
                \Illuminate\Support\Facades\DB::table('notifications')->insert([
                    'id' => Str::uuid(),
                    'type' => fake()->randomElement([
                        'App\\Notifications\\InvestmentConfirmed',
                        'App\\Notifications\\KYCVerified',
                        'App\\Notifications\\PaymentReceived',
                        'App\\Notifications\\SubscriptionCreated',
                        'App\\Notifications\\WithdrawalProcessed',
                    ]),
                    'notifiable_type' => User::class,
                    'notifiable_id' => $user->id,
                    'data' => json_encode([
                        'title' => fake()->sentence(4),
                        'message' => fake()->sentence(10),
                        'action_url' => fake()->optional()->url(),
                    ]),
                    'read_at' => fake()->optional()->dateTimeBetween('-30 days', 'now'),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
