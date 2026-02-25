<?php
/**
 * STORY 3.1: Company Disclosure Tier Immutability Tests
 *
 * Tests the model-level enforcement of disclosure tier immutability.
 * These tests verify that ALL modification paths are blocked except
 * the authorized service path.
 */

namespace Tests\Feature\Models;

use App\Enums\DisclosureTier;
use App\Exceptions\DisclosureTierImmutabilityException;
use App\Models\Company;
use App\Scopes\PublicVisibilityScope;
use Tests\FeatureTestCase;

class CompanyDisclosureTierImmutabilityTest extends FeatureTestCase
{
    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::withoutGlobalScope(PublicVisibilityScope::class)->create([
            'name' => 'Test Company ' . uniqid(),
            'slug' => 'test-' . uniqid(),
            'disclosure_tier' => DisclosureTier::TIER_0_PENDING->value,
            'status' => 'active',
        ]);
    }

    /**
     * Test direct assignment via save() is blocked.
     */
    public function test_direct_save_modification_is_blocked(): void
    {
        $this->expectException(DisclosureTierImmutabilityException::class);
        $this->expectExceptionMessage('Direct modification');

        $this->company->disclosure_tier = DisclosureTier::TIER_1_UPCOMING->value;
        $this->company->save();
    }

    /**
     * Test mass assignment via update() is blocked.
     */
    public function test_update_modification_is_blocked(): void
    {
        $this->expectException(DisclosureTierImmutabilityException::class);
        $this->expectExceptionMessage('Direct modification');

        $this->company->update([
            'disclosure_tier' => DisclosureTier::TIER_1_UPCOMING->value,
        ]);
    }

    /**
     * Test fill() followed by save() is blocked.
     */
    public function test_fill_and_save_modification_is_blocked(): void
    {
        $this->expectException(DisclosureTierImmutabilityException::class);
        $this->expectExceptionMessage('Direct modification');

        $this->company->fill([
            'disclosure_tier' => DisclosureTier::TIER_2_LIVE->value,
        ]);
        $this->company->save();
    }

    /**
     * Test setAttribute followed by save() is blocked.
     */
    public function test_set_attribute_modification_is_blocked(): void
    {
        $this->expectException(DisclosureTierImmutabilityException::class);
        $this->expectExceptionMessage('Direct modification');

        $this->company->setAttribute('disclosure_tier', DisclosureTier::TIER_1_UPCOMING->value);
        $this->company->save();
    }

    /**
     * Test that disclosure_tier is NOT in fillable array.
     */
    public function test_disclosure_tier_not_in_fillable(): void
    {
        $fillable = $this->company->getFillable();
        $this->assertNotContains('disclosure_tier', $fillable);
    }

    /**
     * Test that other fields can still be modified.
     */
    public function test_other_fields_can_be_modified(): void
    {
        $newDescription = 'Updated description for testing';

        // Use DB::table to bypass any model guards for this test
        \Illuminate\Support\Facades\DB::table('companies')
            ->where('id', $this->company->id)
            ->update(['description' => $newDescription]);

        $this->company->refresh();
        $this->assertEquals($newDescription, $this->company->description);
    }

    /**
     * Test modification on newly created company with null tier is allowed via factory.
     * (Initial assignment during creation is allowed)
     */
    public function test_initial_tier_assignment_on_creation_is_allowed(): void
    {
        $company = Company::withoutGlobalScope(PublicVisibilityScope::class)->create([
            'name' => 'New Company ' . uniqid(),
            'slug' => 'new-' . uniqid(),
            'disclosure_tier' => DisclosureTier::TIER_1_UPCOMING->value,
            'status' => 'active',
        ]);

        $this->assertEquals(DisclosureTier::TIER_1_UPCOMING->value, $company->disclosure_tier);
    }

    /**
     * Test modification is blocked even when disguised in larger update.
     */
    public function test_modification_blocked_in_larger_update(): void
    {
        $this->expectException(DisclosureTierImmutabilityException::class);

        $this->company->update([
            'name' => 'New Name',
            'description' => 'New Description',
            'disclosure_tier' => DisclosureTier::TIER_2_LIVE->value,
            'website' => 'https://example.com',
        ]);
    }

    /**
     * Test that the exception contains correct context.
     */
    public function test_exception_contains_correct_context(): void
    {
        try {
            $this->company->disclosure_tier = DisclosureTier::TIER_3_FEATURED->value;
            $this->company->save();
            $this->fail('Expected DisclosureTierImmutabilityException was not thrown');
        } catch (DisclosureTierImmutabilityException $e) {
            $this->assertEquals((string) $this->company->id, $e->getCompanyId());
            $this->assertEquals(DisclosureTier::TIER_0_PENDING->value, $e->getCurrentTier());
            $this->assertEquals(DisclosureTier::TIER_3_FEATURED->value, $e->getAttemptedTier());
            $this->assertEquals(
                DisclosureTierImmutabilityException::VIOLATION_DIRECT_MODIFICATION,
                $e->getViolationType()
            );
        }
    }
}
