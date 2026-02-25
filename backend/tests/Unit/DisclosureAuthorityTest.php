<?php

namespace Tests\Unit\Phase1Audit;

use App\Exceptions\DisclosureAuthorityViolationException;
use App\Models\CompanyDisclosure;
use App\Models\DisclosureVersion;
use App\Models\Company;
use App\Models\DisclosureModule;
use App\Models\User;
use App\Repositories\ApprovedDisclosureRepository;
use Illuminate\Support\Facades\DB;
use Tests\UnitTestCase;

// * PHASE 1 AUDIT: Disclosure Authority Tests
// *
// * These tests verify the critical invariants enforced by Phase 1 audit fixes:
// * 1. Approved disclosures are immutable
// * 2. Investors only see approved disclosures
// * 3. Disclosure data comes from locked versions, not mutable disclosure records
// * 4. Invariant violations cause hard failures, not silent skips

class DisclosureAuthorityTest extends UnitTestCase
{
    protected Company $company;
    protected DisclosureModule $module;
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test data
        $this->company = Company::factory()->create();
        $this->admin = User::factory()->create(['is_admin' => true]);
        $this->module = DisclosureModule::factory()->create([
            'code' => 'test_module',
            'name' => 'Test Module',
        ]);
    }

    // TEST: DISCLOSURE VERSION IMMUTABILITY

    public function disclosure_version_data_fields_cannot_be_modified(): void
    {
        // Create an approved disclosure with version
        $disclosure = $this->createApprovedDisclosure();
        $version = $disclosure->currentVersion;

        $originalData = $version->disclosure_data;

        // Attempt to modify disclosure_data - should be blocked
        $version->disclosure_data = ['modified' => true];
        $result = $version->save();

        // Verify update was blocked
        $this->assertFalse($result);

        // Verify data unchanged in database
        $version->refresh();
        $this->assertEquals($originalData, $version->disclosure_data);
    }

    public function disclosure_version_cannot_be_deleted(): void
    {
        $disclosure = $this->createApprovedDisclosure();
        $version = $disclosure->currentVersion;
        $versionId = $version->id;

        // Attempt to delete - should be blocked
        $result = $version->delete();

        // Verify delete was blocked
        $this->assertFalse($result);

        // Verify version still exists
        $this->assertDatabaseHas('disclosure_versions', ['id' => $versionId]);
    }

    public function disclosure_version_metadata_can_be_updated(): void
    {
        $disclosure = $this->createApprovedDisclosure();
        $version = $disclosure->currentVersion;

        // Update allowed metadata fields
        $version->investor_view_count = 10;
        $version->was_investor_visible = true;
        $result = $version->save();

        // Verify update succeeded
        $this->assertTrue($result);

        // Verify changes persisted
        $version->refresh();
        $this->assertEquals(10, $version->investor_view_count);
        $this->assertTrue($version->was_investor_visible);
    }

    // TEST: APPROVED DISCLOSURE IMMUTABILITY

    public function approved_disclosure_data_cannot_be_modified(): void
    {
        $disclosure = $this->createApprovedDisclosure();
        $originalData = $disclosure->disclosure_data;

        // Attempt to modify disclosure_data
        $disclosure->disclosure_data = ['modified' => true];
        $result = $disclosure->save();

        // Verify update was blocked
        $this->assertFalse($result);

        // Verify data unchanged
        $disclosure->refresh();
        $this->assertEquals($originalData, $disclosure->disclosure_data);
    }

    public function approved_disclosure_status_cannot_be_changed(): void
    {
        $disclosure = $this->createApprovedDisclosure();

        // Attempt to change status
        $disclosure->status = 'draft';
        $result = $disclosure->save();

        // Verify update was blocked
        $this->assertFalse($result);

        // Verify status unchanged
        $disclosure->refresh();
        $this->assertEquals('approved', $disclosure->status);
    }

    public function approved_disclosure_internal_notes_can_be_updated(): void
    {
        $disclosure = $this->createApprovedDisclosure();

        // Update allowed field
        $disclosure->internal_notes = 'Updated admin notes';
        $result = $disclosure->save();

        // Verify update succeeded
        $this->assertTrue($result);

        // Verify change persisted
        $disclosure->refresh();
        $this->assertEquals('Updated admin notes', $disclosure->internal_notes);
    }

    // TEST: INVESTOR VISIBLE DATA ACCESSOR

    public function investor_visible_data_returns_immutable_version_data(): void
    {
        $disclosure = $this->createApprovedDisclosure();

        // Verify accessor returns version data, not disclosure data
        $investorData = $disclosure->investor_visible_data;

        $this->assertNotNull($investorData);
        $this->assertEquals(
            $disclosure->currentVersion->disclosure_data,
            $investorData
        );
    }

    public function investor_visible_data_throws_for_approved_without_version(): void
    {
        // Create approved disclosure without setting current_version_id
        $disclosure = CompanyDisclosure::create([
            'company_id' => $this->company->id,
            'disclosure_module_id' => $this->module->id,
            'disclosure_data' => ['test' => 'data'],
            'status' => 'approved',
            'is_visible' => true,
            'visibility' => 'public',
            'current_version_id' => null, // Invalid state
            'is_locked' => true,
        ]);

        $this->expectException(DisclosureAuthorityViolationException::class);

        // This should throw
        $_ = $disclosure->investor_visible_data;
    }

    public function investor_visible_data_returns_null_for_draft(): void
    {
        $disclosure = CompanyDisclosure::create([
            'company_id' => $this->company->id,
            'disclosure_module_id' => $this->module->id,
            'disclosure_data' => ['test' => 'data'],
            'status' => 'draft',
            'is_visible' => true,
            'visibility' => 'public',
        ]);

        // Drafts should return null, not throw
        $this->assertNull($disclosure->investor_visible_data);
    }

    // TEST: APPROVED DISCLOSURE REPOSITORY

    public function repository_only_returns_approved_disclosures(): void
    {
        // Create disclosures in various states
        $this->createApprovedDisclosure();
        $this->createDraftDisclosure();
        $this->createSubmittedDisclosure();

        $repository = new ApprovedDisclosureRepository();
        $result = $repository->getApprovedDisclosuresForInvestor($this->company->id);

        // Only approved disclosure should be returned
        $this->assertCount(1, $result);
    }

    public function repository_returns_immutable_version_data(): void
    {
        $disclosure = $this->createApprovedDisclosure();
        $versionData = $disclosure->currentVersion->disclosure_data;

        $repository = new ApprovedDisclosureRepository();
        $result = $repository->getApprovedDisclosuresForInvestor($this->company->id);

        // Verify data matches version, not disclosure
        $this->assertEquals($versionData, $result[$disclosure->id]['data']);
    }

    public function repository_throws_for_approved_without_version(): void
    {
        // Create invalid state: approved without version
        CompanyDisclosure::create([
            'company_id' => $this->company->id,
            'disclosure_module_id' => $this->module->id,
            'disclosure_data' => ['test' => 'data'],
            'status' => 'approved',
            'is_visible' => true,
            'visibility' => 'public',
            'current_version_id' => null,
            'is_locked' => true,
        ]);

        $repository = new ApprovedDisclosureRepository();

        $this->expectException(DisclosureAuthorityViolationException::class);

        $repository->getApprovedDisclosuresForInvestor($this->company->id);
    }

    // TEST: VERSION HASH INTEGRITY

    public function version_integrity_can_be_verified(): void
    {
        $disclosure = $this->createApprovedDisclosure();
        $version = $disclosure->currentVersion;

        // Verify integrity check passes
        $this->assertTrue($version->verifyIntegrity());
    }

    public function tampered_version_fails_integrity_check(): void
    {
        $disclosure = $this->createApprovedDisclosure();
        $version = $disclosure->currentVersion;

        // Directly modify database (bypassing Eloquent) to simulate tampering
        DB::table('disclosure_versions')
            ->where('id', $version->id)
            ->update(['disclosure_data' => json_encode(['tampered' => true])]);

        $version->refresh();

        // Integrity check should fail
        $this->assertFalse($version->verifyIntegrity());
    }

    // TEST HELPERS

    protected function createApprovedDisclosure(): CompanyDisclosure
    {
        $disclosureData = [
            'field1' => 'value1',
            'field2' => 'value2',
        ];

        // Create disclosure
        $disclosure = CompanyDisclosure::create([
            'company_id' => $this->company->id,
            'disclosure_module_id' => $this->module->id,
            'disclosure_data' => $disclosureData,
            'status' => 'draft',
            'is_visible' => true,
            'visibility' => 'public',
            'completion_percentage' => 100,
            'version_number' => 1,
        ]);

        // Create version
        $version = DisclosureVersion::create([
            'company_disclosure_id' => $disclosure->id,
            'company_id' => $this->company->id,
            'disclosure_module_id' => $this->module->id,
            'version_number' => 1,
            'version_hash' => hash('sha256', json_encode($disclosureData)),
            'disclosure_data' => $disclosureData,
            'is_locked' => true,
            'locked_at' => now(),
            'approved_at' => now(),
            'approved_by' => $this->admin->id,
        ]);

        // Approve disclosure
        $disclosure->update([
            'status' => 'approved',
            'current_version_id' => $version->id,
            'is_locked' => true,
            'approved_at' => now(),
            'approved_by' => $this->admin->id,
        ]);

        return $disclosure->fresh('currentVersion');
    }

    protected function createDraftDisclosure(): CompanyDisclosure
    {
        return CompanyDisclosure::create([
            'company_id' => $this->company->id,
            'disclosure_module_id' => $this->module->id,
            'disclosure_data' => ['draft' => true],
            'status' => 'draft',
            'is_visible' => true,
            'visibility' => 'public',
        ]);
    }

    protected function createSubmittedDisclosure(): CompanyDisclosure
    {
        return CompanyDisclosure::create([
            'company_id' => $this->company->id,
            'disclosure_module_id' => $this->module->id,
            'disclosure_data' => ['submitted' => true],
            'status' => 'submitted',
            'is_visible' => true,
            'visibility' => 'public',
        ]);
    }
}
