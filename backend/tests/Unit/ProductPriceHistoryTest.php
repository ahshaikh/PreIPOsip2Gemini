<?php
// V-FINAL-1730-TEST-64 (Created)

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Product;
use App\Models\ProductPriceHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class ProductPriceHistoryTest extends TestCase
{
    use RefreshDatabase;

    protected $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->product = Product::factory()->create();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_price_history_belongs_to_product()
    {
        $history = ProductPriceHistory::create([
            'product_id' => $this->product->id,
            'price' => 100,
            'recorded_at' => now()
        ]);

        $this->assertInstanceOf(Product::class, $history->product);
        $this->assertEquals($this->product->id, $history->product->id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_price_history_tracks_price_changes()
    {
        $history = ProductPriceHistory::create([
            'product_id' => $this->product->id,
            'price' => 120.50,
            'recorded_at' => now()
        ]);

        $this->assertDatabaseHas('product_price_histories', [
            'price' => 120.50
        ]);
        $this->assertEquals(120.50, $history->fresh()->price);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_price_history_validates_price_positive()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Price must be positive");

        ProductPriceHistory::create([
            'product_id' => $this->product->id,
            'price' => -50, // Invalid
            'recorded_at' => now()
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_price_history_orders_by_date_descending()
    {
        // Create 3 records out of order
        $h1 = ProductPriceHistory::factory()->create([
            'product_id' => $this->product->id,
            'recorded_at' => now()->subDays(5)
        ]);
        
        $h2 = ProductPriceHistory::factory()->create([
            'product_id' => $this->product->id,
            'recorded_at' => now() // Newest
        ]);
        
        $h3 = ProductPriceHistory::factory()->create([
            'product_id' => $this->product->id,
            'recorded_at' => now()->subDays(2)
        ]);

        // Fetch using 'latest' (which means order by date descending)
        $latest = $this->product->priceHistory()->latest('recorded_at')->first();

        $this->assertEquals($h2->id, $latest->id);
    }
}