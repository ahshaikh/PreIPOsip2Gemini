<?php
// V-FINAL-1730-TEST-32

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\BulkPurchase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BulkPurchaseTest extends TestCase
{
    use RefreshDatabase;

    protected $product;
    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->product = Product::factory()->create();
        $this->admin = User::factory()->create();
    }

    private function createPurchase($overrides = [])
    {
        $defaults = [
            'product_id' => $this->product->id,
            'admin_id' => $this->admin->id,
            'face_value_purchased' => 100000,
            'actual_cost_paid' => 80000,
            'extra_allocation_percentage' => 25,
            'purchase_date' => now(),
        ];
        
        // This will trigger the 'creating' boot method
        return BulkPurchase::create(array_merge($defaults, $overrides));
    }

    /** @test */
    public function test_bulk_purchase_belongs_to_product()
    {
        $purchase = $this->createPurchase();
        $this->assertInstanceOf(Product::class, $purchase->product);
    }

    /** @test */
    public function test_bulk_purchase_calculates_discount_percentage()
    {
        // 100k (face) - 80k (cost) = 20k discount. 20k / 100k = 20%
        $purchase = $this->createPurchase();
        $this->assertEquals(20.00, $purchase->discount_percentage);
    }

    /** @test */
    public function test_bulk_purchase_calculates_extra_allocation_percentage()
    {
        $purchase = $this->createPurchase(['extra_allocation_percentage' => 30]);
        $this->assertEquals(30.00, $purchase->extra_allocation_percentage);
    }

    /** @test */
    public function test_bulk_purchase_calculates_total_value_received()
    {
        // 100k (face) * (1 + 0.25) = 125,000
        $purchase = $this->createPurchase();
        $this->assertEquals(125000, $purchase->total_value_received);
    }

    /** @test */
    public function test_bulk_purchase_calculates_gross_margin()
    {
        // 125k (total) - 80k (cost) = 45,000 margin
        $purchase = $this->createPurchase();
        $this->assertEquals(45000, $purchase->gross_margin);
    }

    /** @test */
    public function test_bulk_purchase_calculates_gross_margin_percentage()
    {
        // 45k (margin) / 80k (cost) * 100 = 56.25%
        $purchase = $this->createPurchase();
        $this->assertEquals(56.25, $purchase->gross_margin_percentage);
    }

    /** @test */
    public function test_bulk_purchase_tracks_allocated_amount()
    {
        $purchase = $this->createPurchase(); // Total: 125k, Remaining: 125k
        
        // Simulate AllocationService decrementing
        $purchase->decrement('value_remaining', 25000);
        
        // Allocated = Total - Remaining = 125k - 100k = 25k
        $this->assertEquals(25000, $purchase->fresh()->allocated_amount);
    }

    /** @test */
    public function test_bulk_purchase_calculates_available_amount()
    {
        $purchase = $this->createPurchase(); // Total: 125k, Remaining: 125k
        
        $purchase->decrement('value_remaining', 25000);
        
        // Available = value_remaining
        $this->assertEquals(100000, $purchase->fresh()->available_amount);
    }

    /** @test */
    public function test_bulk_purchase_validates_cost_positive()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Actual cost cannot be negative");
        
        $this->createPurchase(['actual_cost_paid' => -100]);
    }

    /** @test */
    public function test_bulk_purchase_validates_face_value_positive()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Face value must be positive");
        
        $this->createPurchase(['face_value_purchased' => 0]);
    }
}