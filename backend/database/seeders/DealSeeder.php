<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Deal;
use App\Models\BulkPurchase;
use App\Models\User;
use App\Enums\DisclosureTier;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DealSeeder extends Seeder
{
    public function run(): void
    {
        $nexgen = Company::where('slug', 'nexgen-ai-solutions')->first();
        $medicare = Company::where('slug', 'medicare-plus-healthtech')->first();
        $finsecure = Company::where('slug', 'finsecure-digital-lending')->first();
        $eduverse = Company::where('slug', 'eduverse-learning-platform')->first();
        $greenpower = Company::where('slug', 'greenpower-energy-solutions')->first();

        $admin = User::where('email', 'admin@preiposip.com')->first();

        DB::transaction(function () use ($nexgen, $medicare, $finsecure, $eduverse, $greenpower, $admin) {

            /*
            |--------------------------------------------------------------------------
            | STEP 1: Elevate Disclosure Tier
            |--------------------------------------------------------------------------
            */

            foreach ([$nexgen, $medicare, $finsecure, $eduverse, $greenpower] as $company) {
                if (!$company) continue;

                if ($company->getDisclosureTierEnum()->rank() < DisclosureTier::TIER_3_FEATURED->rank()) {
                    $company->updateQuietly([
                        'disclosure_tier' => DisclosureTier::TIER_3_FEATURED->value,
                    ]);
                }
            }

            /*
            |--------------------------------------------------------------------------
            | STEP 2: Define Company Configurations Properly
            |--------------------------------------------------------------------------
            */

            $companyConfigs = [
                ['company' => $nexgen, 'slug' => 'nexgen-series-b-offering', 'name' => 'NexGen Series B Offering', 'price' => 750.00],
                ['company' => $medicare, 'slug' => 'medicare-series-c-offering', 'name' => 'MediCare Series C Offering', 'price' => 1000.00],
                ['company' => $finsecure, 'slug' => 'finsecure-esop-package', 'name' => 'FinSecure ESOP Package', 'price' => 4500.00],
                ['company' => $eduverse, 'slug' => 'eduverse-series-e-offering', 'name' => 'EduVerse Series E Offering', 'price' => 1500.00],
                ['company' => $greenpower, 'slug' => 'greenpower-series-c-offering', 'name' => 'GreenPower Series C Offering', 'price' => 800.00],
            ];

            $products = [];

            /*
            |--------------------------------------------------------------------------
            | STEP 3: Create Products + Inventory
            |--------------------------------------------------------------------------
            */

            foreach ($companyConfigs as $config) {

                $company = $config['company'];
                if (!$company) continue;

                $product = $company->products()->firstOrCreate(
                    ['slug' => $config['slug']],
                    [
                        'name' => $config['name'],
                        'sector' => $company->sector,
                        'description' => 'Dev bootstrap product',
                        'face_value_per_unit' => $config['price'],
                        'min_investment' => 1000.00,
                        'status' => 'draft',
                    ]
                );

                $products[] = [
                    'company' => $company,
                    'product' => $product,
                ];

                if (!$product->bulkPurchases()->where('value_remaining', '>', 0)->exists()) {

                    $faceValue = 5000000;
                    $discount = 12;
                    $extra = 25;

                    $actualCost = $faceValue * (1 - $discount / 100);
                    $totalReceived = $faceValue * (1 + $extra / 100);

                    BulkPurchase::create([
                        'product_id' => $product->id,
                        'company_id' => $company->id,
                        'admin_id' => $admin->id,

                        'face_value_purchased' => $faceValue,
                        'face_value_purchased_paise' => $faceValue * 100,

                        'actual_cost_paid' => $actualCost,
                        'actual_cost_paid_paise' => $actualCost * 100,

                        'discount_percentage' => $discount,
                        'extra_allocation_percentage' => $extra,

                        'total_value_received' => $totalReceived,
                        'total_value_received_paise' => $totalReceived * 100,

                        'value_remaining' => $totalReceived,
                        'value_remaining_paise' => $totalReceived * 100,

                        'seller_name' => $company->name,
                        'purchase_date' => now(),
                        'source_type' => 'manual_entry',
                        'approved_by_admin_id' => $admin->id,
                        'source_documentation' => json_encode(['type' => 'seed_injection', 'verified' => true]),
                        'manual_entry_reason' => "SEED DATA INJECTION: Initialized inventory for {$company->name} IPO inventory initialization. Verified via simulated manual ledger entry for primary allocation of 10,000 units.",
                        'verified_at' => now(),
                    ]);
                }
            }

            /*
            |--------------------------------------------------------------------------
            | STEP 4: Create Deals
            |--------------------------------------------------------------------------
            */

            foreach ($products as $entry) {

                $company = $entry['company'];
                $product = $entry['product'];

                Deal::updateOrCreate(
                    [
                        'company_id' => $company->id,
                        'title' => "{$company->name} Dev Investment Round",
                    ],
                    [
                        'company_id' => $company->id,
                        'product_id' => $product->id,
                        'title' => "{$company->name} Dev Investment Round",
                        'slug' => "{$product->slug}-deal",
                        'description' => 'Dev bootstrap deal',
                        'sector' => $company->sector,
                        'deal_type' => 'live',
                        'share_price' => $product->face_value_per_unit,
                        'min_investment' => 25000.00,
                        'max_investment' => 1000000.00,
                        'deal_opens_at' => now()->subDays(5),
                        'deal_closes_at' => now()->addDays(60),
                        'days_remaining' => 60,
                        'status' => 'active',
                        'is_featured' => true,
                        'sort_order' => 1,
                    ]
                );
            }

            $this->command->info('✓ Deals seeded successfully.');
        });
    }
}
