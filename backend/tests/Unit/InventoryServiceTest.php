<?php
// V-FINAL-1730-TEST-65 (Created)

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\InventoryService;
use App\Models\User;
use App\Models\Product;
use App\Models\BulkPurchase;
use App\Models\UserInvestment;
use Illuminate\Foundation\Testing\RefreshDatabase;

class InventoryServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $service;
    protected $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new InventoryService();
        $this->product = Product::factory()->create();
    }

    /** @test */
    public function test_check_available_inventory_calculates_correctly()
    {
        // 100k available in first batch
        BulkPurchase::create(BulkPurchase::factory()->raw([
            'product_id' => $this->product->id,
            'total_value_received' => 100000,
            'value_remaining' => 100000
        ]));
        
        // 50k available in second batch
        BulkPurchase::create(BulkPurchase::factory()->raw([
            'product_id' => $this->product->id,
            'total_value_received' => 50000,
            'value_remaining' => 50000
        ]));

        $available = $this->service->getAvailableInventory($this->product);
        
        $this->assertEquals(150000, $available);
    }

    /** @test */
    public function test_low_stock_alert_triggers_at_10_percent()
    {
        // 100k Total, 10k Remaining (10% Left, 90% Sold)
        BulkPurchase::create(BulkPurchase::factory()->raw([
            'product_id' => $this->product->id,
            'total_value_received' => 100000,
            'value_remaining' => 10000
        ]));

        $this->assertTrue($this->service->checkLowStock($this->product));

        // 100k Total, 11k Remaining (11% Left, 89% Sold)
        BulkPurchase::create(BulkPurchase::factory()->raw([
            'product_id' => $this->product->id,
            'total_value_received' => 100000,
            'value_remaining' => 11000
        ]));

        $this->assertFalse($this->service->checkLowStock($this->product));
    }

    /** @test */
    public function test_reorder_suggestion_based_on_allocation_rate()
    {
        // 1. Inventory: 30,000 available
        BulkPurchase::create(BulkPurchase::factory()->raw([
            'product_id' => $this->product->id,
            'total_value_received' => 100000,
            'value_remaining' => 30000
        ]));

        // 2. Allocation Rate: 30,000 allocated in last 30 days
        UserInvestment::factory()->create([
            'product_id' => $this->product->id,
            'value_allocated' => 10000,
            'created_at' => now()->subDays(5)
        ]);
        UserInvestment::factory()->create([
            'product_id' => $this->product->id,
            'value_allocated' => 10000,
            'created_at' => now()->subDays(10)
        ]);
        UserInvestment::factory()->create([
            'product_id' => $this->product->id,
            'value_allocated' => 10000,
            'created_at' => now()->subDays(15)
        ]);
        
        // 3. Logic
        // Daily Burn Rate = 30,000 / 30 = 1000
        // Days Remaining = 30,000 / 1000 = 30
        
        $suggestion = $this->service->getReorderSuggestion($this->product);

        $this->assertStringContainsString("30 days", $suggestion);
    }
}