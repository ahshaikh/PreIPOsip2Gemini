<?php
// V-FINAL-1730-TEST-33

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\BulkPurchase;
use App\Models\UserInvestment;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserInvestmentTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $product;
    protected $purchase;
    protected $investment;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        
        $this->product = Product::factory()->create([
            'face_value_per_unit' => 100,
            'current_market_price' => 150, // Product has a 50% gain
        ]);
        
        $this->purchase = BulkPurchase::factory()->create([
            'product_id' => $this->product->id,
        ]);

        $this->investment = UserInvestment::create([
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
            'bulk_purchase_id' => $this->purchase->id,
            'units_allocated' => 10,  // 10 units
            'value_allocated' => 1000, // at ₹100 face value = ₹1000 cost
            'source' => 'investment',
        ]);
    }

    public function test_investment_belongs_to_user()
    {
        $this->assertInstanceOf(User::class, $this->investment->user);
        $this->assertEquals($this->user->id, $this->investment->user->id);
    }

    public function test_investment_belongs_to_product()
    {
        $this->assertInstanceOf(Product::class, $this->investment->product);
        $this->assertEquals($this->product->id, $this->investment->product->id);
    }

    public function test_investment_belongs_to_bulk_purchase()
    {
        $this->assertInstanceOf(BulkPurchase::class, $this->investment->bulkPurchase);
        $this->assertEquals($this->purchase->id, $this->investment->bulkPurchase->id);
    }

    public function test_investment_tracks_allocation_type()
    {
        $bonus = UserInvestment::create([
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
            'bulk_purchase_id' => $this->purchase->id,
            'units_allocated' => 2,
            'value_allocated' => 200,
            'source' => 'bonus',
        ]);

        $this->assertEquals('investment', $this->investment->source);
        $this->assertEquals('bonus', $bonus->source);
    }

    public function test_investment_tracks_units_allocated()
    {
        $this->assertEquals(10, $this->investment->units_allocated);
    }

    public function test_investment_calculates_current_value()
    {
        // 10 units * ₹150 current_market_price = ₹1500
        $this->assertEquals(1500, $this->investment->current_value);
    }

    public function test_investment_calculates_profit_loss()
    {
        // 1500 (Current Value) - 1000 (Cost Basis) = 500
        $this->assertEquals(500, $this->investment->profit_loss);
    }

    public function test_investment_calculates_roi_percentage()
    {
        // 500 (Profit) / 1000 (Cost Basis) * 100 = 50%
        $this->assertEquals(50.0, $this->investment->roi_percentage);
    }
}