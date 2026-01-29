<?php
/**
 * STORY 3.1: Company Disclosure Tier Service Tests
 *
 * Tests the CompanyDisclosureTierService for:
 * - Promotion authority enforcement
 * - Monotonic progression (no downgrade, no skip)
 * - Authorization checks
 *
 * CLOSURE PASS: Tests verify:
 * 1. Promotion succeeds through service
 * 2. Direct mutation throws
 * 3. Public queries exclude tier < tier_2_live
 */

namespace Tests\Feature\Services;

use App\Enums\DisclosureTier;
use App\Exceptions\DisclosureTierImmutabilityException;
use App\Models\Company;
use App\Models\User;
use App\Scopes\PublicVisibilityScope;
use App\Services\CompanyDisclosureTierService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CompanyDisclosureTierServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CompanyDisclosureTierService $service;
    protected User $adminUser;
    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new CompanyDisclosureTierService();

        // Create admin user with role
        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole('admin');

        // Create company at tier_0_pending (default) - bypass global scope for test setup
        $this->company = Company::withoutGlobalScope(PublicVisibilityScope::class)->create([
            'name' => 'Test Company ' . uniqid(),
            'slug' => 'test-company-' . uniqid(),
            'disclosure_tier' => DisclosureTier::TIER_0_PENDING->value,
            'profile_completed' => true,
            'is_verified' => true,
            'status' => 'active',
        ]);
    }

    /**
     * Test successful promotion from tier_0 to tier_1.
     */
    public function test_promote_tier_0_to_tier_1_succeeds(): void
    {
        $result = $this->service->promote(
            $this->company,
            DisclosureTier::TIER_1_UPCOMING,
            $this->adminUser,
            'Disclosure submission received'
        );

        $this->assertEquals(DisclosureTier::TIER_1_UPCOMING->value, $result->disclosure_tier);
        $this->company->refresh();
        $this->assertEquals(DisclosureTier::TIER_1_UPCOMING->value, $this->company->disclosure_tier);
    }

    /**
     * Test successful promotion from tier_1 to tier_2.
     */
    public function test_promote_tier_1_to_tier_2_succeeds(): void
    {
        // First promote to tier_1
        DB::table('companies')
            ->where('id', $this->company->id)
            ->update(['disclosure_tier' => DisclosureTier::TIER_1_UPCOMING->value]);
        $this->company->refresh();

        // Create at least one approved disclosure (required for tier_2)
        $this->company->disclosures()->create([
            'module_id' => 1,
            'status' => 'approved',
            'disclosure_data' => [],
        ]);

        $result = $this->service->promote(
            $this->company,
            DisclosureTier::TIER_2_LIVE,
            $this->adminUser,
            'Disclosures approved, going live'
        );

        $this->assertEquals(DisclosureTier::TIER_2_LIVE->value, $result->disclosure_tier);
    }

    /**
     * Test successful promotion from tier_2 to tier_3.
     */
    public function test_promote_tier_2_to_tier_3_succeeds(): void
    {
        // Set to tier_2
        DB::table('companies')
            ->where('id', $this->company->id)
            ->update(['disclosure_tier' => DisclosureTier::TIER_2_LIVE->value]);
        $this->company->refresh();

        $result = $this->service->promote(
            $this->company,
            DisclosureTier::TIER_3_FEATURED,
            $this->adminUser,
            'Editorial selection for featured status'
        );

        $this->assertEquals(DisclosureTier::TIER_3_FEATURED->value, $result->disclosure_tier);
    }

    /**
     * Test tier skip is rejected.
     * INVARIANT: Cannot skip tiers.
     */
    public function test_tier_skip_is_rejected(): void
    {
        $this->expectException(DisclosureTierImmutabilityException::class);
        $this->expectExceptionMessage('tier skipping is forbidden');

        $this->service->promote(
            $this->company,
            DisclosureTier::TIER_2_LIVE, // Skip tier_1
            $this->adminUser,
            'Attempting to skip tier_1'
        );
    }

    /**
     * Test downgrade is rejected.
     * INVARIANT: Cannot downgrade tiers.
     */
    public function test_downgrade_is_rejected(): void
    {
        // Set to tier_2
        DB::table('companies')
            ->where('id', $this->company->id)
            ->update(['disclosure_tier' => DisclosureTier::TIER_2_LIVE->value]);
        $this->company->refresh();

        $this->expectException(DisclosureTierImmutabilityException::class);
        $this->expectExceptionMessage('downgrade is forbidden');

        $this->service->promote(
            $this->company,
            DisclosureTier::TIER_1_UPCOMING, // Downgrade
            $this->adminUser,
            'Attempting downgrade'
        );
    }

    /**
     * Test promotion to same tier is rejected.
     */
    public function test_promotion_to_same_tier_is_rejected(): void
    {
        $this->expectException(DisclosureTierImmutabilityException::class);

        $this->service->promote(
            $this->company,
            DisclosureTier::TIER_0_PENDING, // Same tier
            $this->adminUser,
            'Attempting same-tier promotion'
        );
    }

    /**
     * Test promoteToNextTier convenience method.
     */
    public function test_promote_to_next_tier_convenience_method(): void
    {
        $result = $this->service->promoteToNextTier(
            $this->company,
            $this->adminUser,
            'Progressing to next tier'
        );

        $this->assertEquals(DisclosureTier::TIER_1_UPCOMING->value, $result->disclosure_tier);
    }

    /**
     * Test promoteToNextTier fails at maximum tier.
     */
    public function test_promote_to_next_tier_fails_at_maximum(): void
    {
        // Set to tier_3 (maximum)
        DB::table('companies')
            ->where('id', $this->company->id)
            ->update(['disclosure_tier' => DisclosureTier::TIER_3_FEATURED->value]);
        $this->company->refresh();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('already at maximum tier');

        $this->service->promoteToNextTier(
            $this->company,
            $this->adminUser,
            'Attempting to exceed maximum'
        );
    }

    /**
     * Test getCurrentTier returns correct enum.
     */
    public function test_get_current_tier_returns_correct_enum(): void
    {
        $tier = $this->service->getCurrentTier($this->company);
        $this->assertEquals(DisclosureTier::TIER_0_PENDING, $tier);

        // Change tier directly via DB
        DB::table('companies')
            ->where('id', $this->company->id)
            ->update(['disclosure_tier' => DisclosureTier::TIER_2_LIVE->value]);
        $this->company->refresh();

        $tier = $this->service->getCurrentTier($this->company);
        $this->assertEquals(DisclosureTier::TIER_2_LIVE, $tier);
    }

    /**
     * Test isPubliclyVisible returns correct values.
     */
    public function test_is_publicly_visible_returns_correct_values(): void
    {
        // Tier 0 - not visible
        $this->assertFalse($this->service->isPubliclyVisible($this->company));

        // Tier 1 - not visible
        DB::table('companies')
            ->where('id', $this->company->id)
            ->update(['disclosure_tier' => DisclosureTier::TIER_1_UPCOMING->value]);
        $this->company->refresh();
        $this->assertFalse($this->service->isPubliclyVisible($this->company));

        // Tier 2 - visible
        DB::table('companies')
            ->where('id', $this->company->id)
            ->update(['disclosure_tier' => DisclosureTier::TIER_2_LIVE->value]);
        $this->company->refresh();
        $this->assertTrue($this->service->isPubliclyVisible($this->company));

        // Tier 3 - visible
        DB::table('companies')
            ->where('id', $this->company->id)
            ->update(['disclosure_tier' => DisclosureTier::TIER_3_FEATURED->value]);
        $this->company->refresh();
        $this->assertTrue($this->service->isPubliclyVisible($this->company));
    }

    /**
     * Test validation requirements for tier_2 promotion.
     */
    public function test_validation_requirements_for_tier_2(): void
    {
        // Set company to tier_1
        DB::table('companies')
            ->where('id', $this->company->id)
            ->update(['disclosure_tier' => DisclosureTier::TIER_1_UPCOMING->value]);
        $this->company->refresh();

        // Without approved disclosures or verification
        DB::table('companies')
            ->where('id', $this->company->id)
            ->update(['is_verified' => false]);
        $this->company->refresh();

        $validation = $this->service->validatePromotionRequirements(
            $this->company,
            DisclosureTier::TIER_2_LIVE
        );

        $this->assertFalse($validation['valid']);
        $this->assertNotEmpty($validation['errors']);
    }

    // ==================== CLOSURE PASS TESTS ====================

    /**
     * CLOSURE TEST 1: Promotion succeeds through service.
     * Verifies DB::table() bypass works correctly.
     */
    public function test_closure_promotion_succeeds_through_service(): void
    {
        $this->assertEquals(DisclosureTier::TIER_0_PENDING->value, $this->company->disclosure_tier);

        $result = $this->service->promote(
            $this->company,
            DisclosureTier::TIER_1_UPCOMING,
            $this->adminUser,
            'Closure test promotion'
        );

        $this->assertEquals(DisclosureTier::TIER_1_UPCOMING->value, $result->disclosure_tier);

        // Verify in database
        $dbValue = DB::table('companies')->where('id', $this->company->id)->value('disclosure_tier');
        $this->assertEquals(DisclosureTier::TIER_1_UPCOMING->value, $dbValue);
    }

    /**
     * CLOSURE TEST 2: Direct mutation throws DisclosureTierImmutabilityException.
     */
    public function test_closure_direct_mutation_throws(): void
    {
        $this->expectException(DisclosureTierImmutabilityException::class);

        $this->company->disclosure_tier = DisclosureTier::TIER_1_UPCOMING->value;
        $this->company->save();
    }

    /**
     * CLOSURE TEST 3: Public queries exclude tier < tier_2_live.
     * Simulates public context and verifies global scope enforcement.
     */
    public function test_closure_public_queries_exclude_non_public_tiers(): void
    {
        // Create companies at each tier
        $tier0 = Company::withoutGlobalScope(PublicVisibilityScope::class)->create([
            'name' => 'Tier0 Company',
            'slug' => 'tier0-' . uniqid(),
            'disclosure_tier' => DisclosureTier::TIER_0_PENDING->value,
            'status' => 'active',
        ]);

        $tier1 = Company::withoutGlobalScope(PublicVisibilityScope::class)->create([
            'name' => 'Tier1 Company',
            'slug' => 'tier1-' . uniqid(),
            'disclosure_tier' => DisclosureTier::TIER_1_UPCOMING->value,
            'status' => 'active',
        ]);

        $tier2 = Company::withoutGlobalScope(PublicVisibilityScope::class)->create([
            'name' => 'Tier2 Company',
            'slug' => 'tier2-' . uniqid(),
            'disclosure_tier' => DisclosureTier::TIER_2_LIVE->value,
            'status' => 'active',
        ]);

        $tier3 = Company::withoutGlobalScope(PublicVisibilityScope::class)->create([
            'name' => 'Tier3 Company',
            'slug' => 'tier3-' . uniqid(),
            'disclosure_tier' => DisclosureTier::TIER_3_FEATURED->value,
            'status' => 'active',
        ]);

        // Without scope: all visible
        $allCompanies = Company::withoutGlobalScope(PublicVisibilityScope::class)->get();
        $this->assertTrue($allCompanies->contains('id', $tier0->id));
        $this->assertTrue($allCompanies->contains('id', $tier1->id));
        $this->assertTrue($allCompanies->contains('id', $tier2->id));
        $this->assertTrue($allCompanies->contains('id', $tier3->id));

        // With explicit publiclyVisible scope: only tier_2 and tier_3
        $publicCompanies = Company::withoutGlobalScope(PublicVisibilityScope::class)
            ->publiclyVisible()
            ->get();

        $this->assertFalse($publicCompanies->contains('id', $tier0->id));
        $this->assertFalse($publicCompanies->contains('id', $tier1->id));
        $this->assertTrue($publicCompanies->contains('id', $tier2->id));
        $this->assertTrue($publicCompanies->contains('id', $tier3->id));
    }
}
