<?php
/**
 * STORY 4.1: Deal Tier Gate Tests
 *
 * Tests that deal operations are properly gated by company disclosure tier.
 *
 * INVARIANTS TESTED:
 * - Deal creation requires company.disclosure_tier >= tier_1_upcoming
 * - Deal activation requires company.disclosure_tier >= tier_2_live
 * - Deal featuring requires company.disclosure_tier >= tier_3_featured
 */

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Deal;
use App\Models\Company;
use App\Models\Product;
use App\Enums\DisclosureTier;
use App\Exceptions\DealTierGateException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class DealTierGateTest extends TestCase
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

    /**
     * Helper to create a company with a specific disclosure tier.
     * Uses DB::table() to bypass model guards (similar to tier promotion service).
     */
    private function createCompanyWithTier(DisclosureTier $tier): Company
    {
        // Create company without global scope
        $company = Company::withoutGlobalScopes()->create([
            'name' => 'Test Company ' . uniqid(),
            'slug' => 'test-company-' . uniqid(),
            'email' => 'test' . uniqid() . '@example.com',
            'status' => 'active',
            'is_verified' => true,
        ]);

        // Set tier using direct DB update to bypass model guards
        DB::table('companies')
            ->where('id', $company->id)
            ->update(['disclosure_tier' => $tier->value]);

        return $company->fresh();
    }

    /**
     * Helper to create a deal with common defaults.
     */
    private function createDeal(Company $company, array $overrides = []): Deal
    {
        $defaults = [
            'product_id' => $this->product->id,
            'company_id' => $company->id,
            'title' => 'Test Deal ' . uniqid(),
            'slug' => 'test-deal-' . uniqid(),
            'description' => 'Test deal description',
            'deal_type' => 'upcoming',
            'min_investment' => 10000,
            'max_investment' => 100000,
            'valuation' => 1000000,
            'share_price' => 100,
            'status' => 'draft', // Default to draft to avoid activation gate
            'is_featured' => false,
        ];

        return Deal::create(array_merge($defaults, $overrides));
    }

    // =========================================================================
    // GATE 1: Deal Creation (requires tier_1_upcoming or higher)
    // =========================================================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_deal_creation_blocked_for_tier_0_pending()
    {
        $company = $this->createCompanyWithTier(DisclosureTier::TIER_0_PENDING);

        $this->expectException(DealTierGateException::class);
        $this->expectExceptionMessage('Deal creation blocked');

        $this->createDeal($company);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_deal_creation_allowed_for_tier_1_upcoming()
    {
        $company = $this->createCompanyWithTier(DisclosureTier::TIER_1_UPCOMING);

        $deal = $this->createDeal($company);

        $this->assertInstanceOf(Deal::class, $deal);
        $this->assertEquals($company->id, $deal->company_id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_deal_creation_allowed_for_tier_2_live()
    {
        $company = $this->createCompanyWithTier(DisclosureTier::TIER_2_LIVE);

        $deal = $this->createDeal($company);

        $this->assertInstanceOf(Deal::class, $deal);
        $this->assertEquals($company->id, $deal->company_id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_deal_creation_allowed_for_tier_3_featured()
    {
        $company = $this->createCompanyWithTier(DisclosureTier::TIER_3_FEATURED);

        $deal = $this->createDeal($company);

        $this->assertInstanceOf(Deal::class, $deal);
        $this->assertEquals($company->id, $deal->company_id);
    }

    // =========================================================================
    // GATE 2: Deal Activation (requires tier_2_live or higher)
    // =========================================================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_deal_activation_blocked_for_tier_0_pending()
    {
        $company = $this->createCompanyWithTier(DisclosureTier::TIER_0_PENDING);

        $this->expectException(DealTierGateException::class);
        $this->expectExceptionMessage('Deal creation blocked');

        // Even trying to create with active status will fail at creation gate first
        $this->createDeal($company, ['status' => 'active']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_deal_activation_blocked_for_tier_1_upcoming()
    {
        $company = $this->createCompanyWithTier(DisclosureTier::TIER_1_UPCOMING);

        // Can create with draft status
        $deal = $this->createDeal($company, ['status' => 'draft']);

        $this->expectException(DealTierGateException::class);
        $this->expectExceptionMessage('Deal activation blocked');

        // Cannot activate
        $deal->update(['status' => 'active']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_deal_activation_allowed_for_tier_2_live()
    {
        $company = $this->createCompanyWithTier(DisclosureTier::TIER_2_LIVE);

        $deal = $this->createDeal($company, ['status' => 'active']);

        $this->assertEquals('active', $deal->status);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_deal_activation_allowed_for_tier_3_featured()
    {
        $company = $this->createCompanyWithTier(DisclosureTier::TIER_3_FEATURED);

        $deal = $this->createDeal($company, ['status' => 'active']);

        $this->assertEquals('active', $deal->status);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_deal_status_update_to_active_blocked_for_tier_1()
    {
        $company = $this->createCompanyWithTier(DisclosureTier::TIER_1_UPCOMING);

        $deal = $this->createDeal($company, ['status' => 'draft']);

        $this->expectException(DealTierGateException::class);
        $this->expectExceptionMessage('Deal activation blocked');

        $deal->status = 'active';
        $deal->save();
    }

    // =========================================================================
    // GATE 3: Deal Featuring (requires tier_3_featured)
    // =========================================================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_deal_featuring_blocked_for_tier_1_upcoming()
    {
        $company = $this->createCompanyWithTier(DisclosureTier::TIER_1_UPCOMING);

        $this->expectException(DealTierGateException::class);
        $this->expectExceptionMessage('Deal featuring blocked');

        // Try to create featured deal
        $this->createDeal($company, ['status' => 'draft', 'is_featured' => true]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_deal_featuring_blocked_for_tier_2_live()
    {
        $company = $this->createCompanyWithTier(DisclosureTier::TIER_2_LIVE);

        $this->expectException(DealTierGateException::class);
        $this->expectExceptionMessage('Deal featuring blocked');

        // Try to create featured deal
        $this->createDeal($company, ['status' => 'active', 'is_featured' => true]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_deal_featuring_allowed_for_tier_3_featured()
    {
        $company = $this->createCompanyWithTier(DisclosureTier::TIER_3_FEATURED);

        $deal = $this->createDeal($company, ['status' => 'active', 'is_featured' => true]);

        $this->assertTrue($deal->is_featured);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_deal_update_to_featured_blocked_for_tier_2()
    {
        $company = $this->createCompanyWithTier(DisclosureTier::TIER_2_LIVE);

        $deal = $this->createDeal($company, ['status' => 'active', 'is_featured' => false]);

        $this->expectException(DealTierGateException::class);
        $this->expectExceptionMessage('Deal featuring blocked');

        $deal->is_featured = true;
        $deal->save();
    }

    // =========================================================================
    // Exception Context Tests
    // =========================================================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_tier_gate_exception_has_context()
    {
        $company = $this->createCompanyWithTier(DisclosureTier::TIER_0_PENDING);

        try {
            $this->createDeal($company);
            $this->fail('Expected DealTierGateException was not thrown');
        } catch (DealTierGateException $e) {
            $context = $e->context();

            $this->assertArrayHasKey('company_id', $context);
            $this->assertArrayHasKey('current_tier', $context);
            $this->assertArrayHasKey('required_tier', $context);
            $this->assertArrayHasKey('operation', $context);

            $this->assertEquals($company->id, $context['company_id']);
            $this->assertEquals(DisclosureTier::TIER_0_PENDING->value, $context['current_tier']);
            $this->assertEquals(DisclosureTier::TIER_1_UPCOMING->value, $context['required_tier']);
            $this->assertEquals('create', $context['operation']);
        }
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_activation_exception_shows_correct_operation()
    {
        $company = $this->createCompanyWithTier(DisclosureTier::TIER_1_UPCOMING);

        $deal = $this->createDeal($company, ['status' => 'draft']);

        try {
            $deal->update(['status' => 'active']);
            $this->fail('Expected DealTierGateException was not thrown');
        } catch (DealTierGateException $e) {
            $this->assertEquals('activate', $e->context()['operation']);
            $this->assertEquals(DisclosureTier::TIER_2_LIVE->value, $e->context()['required_tier']);
        }
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_featuring_exception_shows_correct_operation()
    {
        $company = $this->createCompanyWithTier(DisclosureTier::TIER_2_LIVE);

        $deal = $this->createDeal($company, ['status' => 'active', 'is_featured' => false]);

        try {
            $deal->update(['is_featured' => true]);
            $this->fail('Expected DealTierGateException was not thrown');
        } catch (DealTierGateException $e) {
            $this->assertEquals('feature', $e->context()['operation']);
            $this->assertEquals(DisclosureTier::TIER_3_FEATURED->value, $e->context()['required_tier']);
        }
    }

    // =========================================================================
    // Edge Cases
    // =========================================================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_non_featured_deal_at_tier_2_can_be_saved()
    {
        $company = $this->createCompanyWithTier(DisclosureTier::TIER_2_LIVE);

        $deal = $this->createDeal($company, ['status' => 'active', 'is_featured' => false]);

        // Update other fields should work
        $deal->title = 'Updated Title';
        $deal->save();

        $this->assertEquals('Updated Title', $deal->fresh()->title);
        $this->assertFalse($deal->is_featured);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_draft_deal_at_tier_1_can_be_saved()
    {
        $company = $this->createCompanyWithTier(DisclosureTier::TIER_1_UPCOMING);

        $deal = $this->createDeal($company, ['status' => 'draft']);

        // Update other fields should work
        $deal->description = 'Updated description';
        $deal->save();

        $this->assertEquals('Updated description', $deal->fresh()->description);
        $this->assertEquals('draft', $deal->status);
    }
}
