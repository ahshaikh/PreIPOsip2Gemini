<?php
// V-FINAL-1730-TEST-65 (Created)

namespace Tests\Unit;

use Tests\UnitTestCase;
use App\Services\InventoryService;
use App\Models\User;
use App\Models\Product;
use App\Models\BulkPurchase;
use App\Models\UserInvestment;
class InventoryServiceTest extends UnitTestCase
{
    protected $service;
    protected $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new InventoryService();
        $this->product = Product::factory()->create();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_check_available_inventory_calculates_correctly()
    {
        // 100k available in first batch
        BulkPurchase::create(BulkPurchase::factory()->raw([
            'product_id' => $this->product->id,
            'face_value_purchased' => 100000,
            'extra_allocation_percentage' => 0,
            'actual_cost_paid' => 90000,
        ]));
        
        // 50k available in second batch
        BulkPurchase::create(BulkPurchase::factory()->raw([
            'product_id' => $this->product->id,
            'face_value_purchased' => 50000,
            'extra_allocation_percentage' => 0,
            'actual_cost_paid' => 45000,
        ]));

        $available = $this->service->getAvailableInventory($this->product);
        
        $this->assertEquals(150000, $available);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_low_stock_alert_triggers_at_10_percent()
    {
        // Use a fresh product to avoid interference from other tests
        $product = Product::factory()->create();

        // 100k Total, 10k Remaining (10% Left, 90% Sold)
        // To get 10k remaining, we must have 90k allocated.
        // But the model sets remaining = total on create.
        // So we create it, then update it.
        $bp = BulkPurchase::create(BulkPurchase::factory()->raw([
            'product_id' => $product->id,
            'face_value_purchased' => 100000,
            'extra_allocation_percentage' => 0,
            'actual_cost_paid' => 90000,
        ]));
        $bp->update(['value_remaining' => 10000]);

        $this->assertTrue($this->service->checkLowStock($product));

        // 100k Total, 11k Remaining (11% Left, 89% Sold)
        $bp->update(['value_remaining' => 11000]);

        $this->assertFalse($this->service->checkLowStock($product));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_reorder_suggestion_based_on_allocation_rate()
    {
        // Use a fresh product
        $product = Product::factory()->create();

        // 1. Inventory: 30,000 available
        $bp = BulkPurchase::create(BulkPurchase::factory()->raw([
            'product_id' => $product->id,
            'face_value_purchased' => 100000,
            'extra_allocation_percentage' => 0,
            'actual_cost_paid' => 90000,
        ]));
        $bp->update(['value_remaining' => 30000]);

        // 2. Allocation Rate: 30,000 allocated in last 30 days
        UserInvestment::factory()->create([
            'product_id' => $product->id,
            'value_allocated' => 10000,
            'created_at' => now()->subDays(5)
        ]);
        UserInvestment::factory()->create([
            'product_id' => $product->id,
            'value_allocated' => 10000,
            'created_at' => now()->subDays(10)
        ]);
        UserInvestment::factory()->create([
            'product_id' => $product->id,
            'value_allocated' => 10000,
            'created_at' => now()->subDays(15)
        ]);
        
        // 3. Logic
        // Daily Burn Rate = 30,000 / 30 = 1000
        // Days Remaining = 30,000 / 1000 = 30
        
        $suggestion = $this->service->getReorderSuggestion($product);

        $this->assertStringContainsString("30 days", $suggestion);
    }
}
