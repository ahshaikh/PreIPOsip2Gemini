<?php
// V-DEPLOY-1730-006
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\BulkPurchase;
use App\Models\User;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates dummy Pre-IPO products and inventory.
     */
    public function run(): void
    {
        $admin = User::where('username', 'admin')->first();

        $products = [
            [
                'name' => 'Swiggy',
                'slug' => 'swiggy',
                'sector' => 'Food Tech',
                'face_value_per_unit' => 100,
                'min_investment' => 5000,
                'expected_ipo_date' => '2026-06-01',
                'is_featured' => true,
                'description' => json_encode(['summary' => 'Leading food delivery platform in India.']),
            ],
            [
                'name' => 'Ola Electric',
                'slug' => 'ola-electric',
                'sector' => 'EV',
                'face_value_per_unit' => 75,
                'min_investment' => 2500,
                'expected_ipo_date' => '2026-03-01',
                'description' => json_encode(['summary' => 'Electric vehicle arm of Ola.']),
            ],
            [
                'name' => 'PharmEasy',
                'slug' => 'pharmeasy',
                'sector' => 'Health Tech',
                'face_value_per_unit' => 50,
                'min_investment' => 1000,
                'status' => 'upcoming',
                'description' => json_encode(['summary' => 'Online pharmacy and healthcare platform.']),
            ],
        ];

        foreach ($products as $productData) {
            $product = Product::create($productData);

            if ($product->status == 'active') {
                // Add dummy inventory (Bulk Purchase) for this product
                $faceValue = 1000000; // 10 Lakh
                $cost = $faceValue * 0.88; // 12% discount
                $extraAlloc = 20; // 20%
                $totalValue = $faceValue * (1 + ($extraAlloc / 100)); // 12 Lakh

                BulkPurchase::create([
                    'product_id' => $product->id,
                    'admin_id' => $admin->id,
                    'face_value_purchased' => $faceValue,
                    'actual_cost_paid' => $cost,
                    'discount_percentage' => 12.00,
                    'extra_allocation_percentage' => $extraAlloc,
                    'total_value_received' => $totalValue,
                    'value_remaining' => $totalValue, // Full inventory
                    'seller_name' => 'Dummy Seller Ventures',
                    'purchase_date' => now()->subMonth(),
                ]);
            }
        }
    }
}