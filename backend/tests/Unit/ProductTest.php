<?php
// V-FINAL-1730-TEST-62 (Created)

namespace Tests\Unit;

use Tests\UnitTestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\BulkPurchase;
use App\Models\UserInvestment;
use App\Models\ProductPriceHistory;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;

class ProductTest extends UnitTestCase
{
    protected $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->product = Product::factory()->create();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_product_has_bulk_purchases_relationship()
    {
        BulkPurchase::factory()->create(['product_id' => $this->product->id]);
        
        $this->assertTrue($this->product->bulkPurchases()->exists());
        $this->assertEquals(1, $this->product->bulkPurchases->count());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_product_has_investments_relationship()
    {
        UserInvestment::factory()->create(['product_id' => $this->product->id]);

        $this->assertTrue($this->product->investments()->exists());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_product_has_price_history_relationship()
    {
        ProductPriceHistory::factory()->create([
            'product_id' => $this->product->id,
            'price' => 100,
            'recorded_at' => now()->subDay()
        ]);

        $this->assertTrue($this->product->priceHistory()->exists());
        $this->assertEquals(100, $this->product->priceHistory->first()->price);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_product_slug_is_unique()
    {
        $this->expectException(QueryException::class);

        Product::factory()->create(['slug' => $this->product->slug]); // Fails
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_product_validates_face_value_positive()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Face value must be positive");

        Product::factory()->create(['face_value_per_unit' => 0]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_product_status_enum_validates()
    {
        $validStatuses = ['active', 'upcoming', 'listed', 'closed'];
        
        $validator = Validator::make(['status' => 'active'], ['status' => 'in:' . implode(',', $validStatuses)]);
        $this->assertTrue($validator->passes());
        
        $validator = Validator::make(['status' => 'sold_out'], ['status' => 'in:' . implode(',', $validStatuses)]);
        $this->assertFalse($validator->passes());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_product_calculates_total_allocated()
    {
        // No allocations yet
        $this->assertEquals(0, $this->product->total_allocated);
        
        // Allocate
        UserInvestment::factory()->create(['product_id' => $this->product->id, 'value_allocated' => 5000]);
        UserInvestment::factory()->create(['product_id' => $this->product->id, 'value_allocated' => 3000]);
        
        // Use the accessor
        $this->assertEquals(8000, $this->product->fresh()->total_allocated);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_product_soft_deletes_correctly()
    {
        $productId = $this->product->id;
        
        $this->product->delete(); // Soft delete

        $this->assertNull(Product::find($productId));
        $this->assertNotNull(Product::withTrashed()->find($productId));
    }
}
