<?php
// V-DEPLOY-1730-006
// PHASE 1 AUDIT FIX: Products must be created with a company_id.
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Company; // <-- 1. IMPORT COMPANY MODEL
use App\Models\BulkPurchase;
use App\Models\User;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates dummy Pre-IPO products and inventory, ensuring they belong to a company.
     *
     * SEEDER INTEGRITY: Self-contained - creates required admin user if not exists.
     */
    public function run(): void
    {
        // Self-contained: Create seeder admin if none exists (removes UserSeeder coupling)
        $admin = User::where('username', 'admin')->first();
        if (!$admin) {
            $admin = User::create([
                'username' => 'seeder_admin',
                'email' => 'seeder_admin@preipo.local',
                'mobile' => '9000000001', // Required field
                'password' => bcrypt('seeder_password_not_for_production'),
                'status' => 'active',
                'email_verified_at' => now(),
                'mobile_verified_at' => now(),
            ]);
        }

        // 2. CREATE A DUMMY COMPANY FOR SEEDING
        // Using updateOrCreate to avoid creating duplicates on re-seeding
        $dummyCompany = Company::updateOrCreate(
            ['slug' => 'seeder-generated-company'],
            [
                'name' => 'Seeder Generated Company',
                'description' => 'A placeholder company for seeded products.',
                'sector' => 'Diversified',
                'status' => 'active',
                'is_verified' => true,
                'disclosure_tier' => 'tier_2_live', // Ensure it's a visible tier
            ]
        );

        $products = [
            [
                'name' => 'AHShaikh',
                'slug' => 'ahshaikh',
                'sector' => 'Food Tech',
                'face_value_per_unit' => 100,
                'min_investment' => 5000,
                'status' => 'draft', // Initially create as draft
                'expected_ipo_date' => '2026-06-01',
                'is_featured' => true,
                'description' => json_encode(['summary' => 'Leading food delivery platform in India.']),
            ],
            [
                'name' => 'Samara Electric',
                'slug' => 'samara-electric',
                'sector' => 'EV',
                'face_value_per_unit' => 75,
                'min_investment' => 2500,
                'status' => 'draft', // Initially create as draft
                'expected_ipo_date' => '2026-03-01',
                'description' => json_encode(['summary' => 'Electric vehicle arm of Ola.']),
            ],
            [
                'name' => 'Izrein Pharma',
                'slug' => 'izrein-pharma',
                'sector' => 'Health Tech',
                'face_value_per_unit' => 50,
                'min_investment' => 1000,
                'status' => 'draft', // Initially create as draft
                'description' => json_encode(['summary' => 'Online pharmacy and healthcare platform.']),
            ],
        ];

        foreach ($products as $productData) {
            // 3. INJECT THE company_id INTO THE DATA BEFORE CREATION
            $productData['company_id'] = $dummyCompany->id;

            // Use updateOrCreate to make seeder re-runnable
            $product = Product::updateOrCreate(
                ['slug' => $productData['slug']],
                $productData
            );

            // Simulate the workflow: draft -> submitted -> approved for testing
            // V-WAVE3-FIX: Match actual seeded slugs (not old placeholders)
            // ahshaikh and samara-electric will be approved
            if (in_array($product->slug, ['ahshaikh', 'samara-electric'])) {
                if ($product->status === 'draft') {
                    $product->status = 'submitted';
                    $product->save();
                }
                if ($product->status === 'submitted') {
                    $product->status = 'approved';
                    $product->save();
                }
            }
            // izrein-pharma will remain in 'submitted' state for admin review
            else if ($product->slug === 'izrein-pharma') {
                if ($product->status === 'draft') {
                    $product->status = 'submitted';
                    $product->save();
                }
            }

            // Note: product status must be 'approved' for inventory/deals
            if ($product->status == 'approved') {
                // Add dummy inventory (Bulk Purchase) for this product
                $faceValue = 1000000; // 10 Lakh
                $cost = $faceValue * 0.88; // 12% discount
                $extraAlloc = 20; // 20%
                $totalValue = $faceValue * (1 + ($extraAlloc / 100)); // 12 Lakh

                BulkPurchase::updateOrCreate(
                    ['product_id' => $product->id, 'seller_name' => 'Dummy Seller Ventures'],
                    [
                        'admin_id' => $admin->id,
                        'company_id' => $dummyCompany->id,
                        'source_type' => 'manual_entry',
                        'manual_entry_reason' => 'Seeder-generated inventory for testing and development purposes. This is an automated entry.', // Must be >= 50 chars
                        'source_documentation' => 'N/A - Seeder generated',
                        'approved_by_admin_id' => $admin->id, // Must be set for manual entry
                        'verified_at' => now(), // Must be set for manual entry
                        'face_value_purchased' => $faceValue,
                        'actual_cost_paid' => $cost,
                        'discount_percentage' => 12.00,
                        'extra_allocation_percentage' => $extraAlloc,
                        'total_value_received' => $totalValue,
                        'value_remaining' => $totalValue,
                        'purchase_date' => now()->subMonth(),
                    ]
                );
            }
        }
    }
}