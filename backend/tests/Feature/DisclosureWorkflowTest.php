<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CompanyDisclosure;
use App\Models\DisclosureApproval;
use App\Models\DisclosureClarification;
use App\Models\DisclosureModule;
use App\Models\DisclosureVersion;
use App\Models\User;
use Tests\FeatureTestCase;

//  * PHASE 1 REMEDIATION - Disclosure Workflow Tests
//  *
//  * Tests critical disclosure workflows:
//  * 1. Submit disclosure for review
//  * 2. Admin approves disclosure
//  * 3. Admin rejects disclosure
//  * 4. Request clarifications
//  * 5. Answer clarifications
//  * 6. Version creation on approval

class DisclosureWorkflowTest extends FeatureTestCase
{
    protected User $companyUser;
    protected \App\Models\CompanyUser $realCompanyUser;
    protected User $admin;
    protected Company $company;
    protected DisclosureModule $module;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test users
        $this->companyUser = User::factory()->create(['email' => 'company@test.com']);
        $this->admin = User::factory()->create(['email' => 'admin@test.com']);

        // Create test company and module
        $this->company = Company::factory()->create();
        $this->module = DisclosureModule::factory()->businessModel()->create();

        // Create real CompanyUser record for FK constraints
        $this->realCompanyUser = \App\Models\CompanyUser::factory()->create([
            'company_id' => $this->company->id,
            'status' => 'active'
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function disclosure_can_be_submitted_for_review()
    {
        // Create complete disclosure (100%)
        $disclosure = CompanyDisclosure::factory()->create([
            'company_id' => $this->company->id,
            'disclosure_module_id' => $this->module->id,
            'status' => 'draft',
            'completion_percentage' => 100,
        ]);

        // Submit disclosure
        $disclosure->submit($this->realCompanyUser->id, \App\Models\CompanyUser::class);

        // Assert status changed
        $this->assertEquals('submitted', $disclosure->fresh()->status);
        $this->assertNotNull($disclosure->fresh()->submitted_at);
        $this->assertEquals($this->realCompanyUser->id, $disclosure->fresh()->submitted_by_id);

        // Assert approval record created
        $this->assertDatabaseHas('disclosure_approvals', [
            'company_disclosure_id' => $disclosure->id,
            'request_type' => 'initial_submission',
            'status' => 'pending',
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function cannot_submit_incomplete_disclosure()
    {
        $disclosure = CompanyDisclosure::factory()->create([
            'company_id' => $this->company->id,
            'disclosure_module_id' => $this->module->id,
            'status' => 'draft',
            'completion_percentage' => 75, // Incomplete
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot submit incomplete disclosure');

        $disclosure->submit($this->realCompanyUser->id, \App\Models\CompanyUser::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function cannot_submit_locked_disclosure()
    {
        $disclosure = CompanyDisclosure::factory()->create([
            'company_id' => $this->company->id,
            'disclosure_module_id' => $this->module->id,
            'status' => 'approved',
            'is_locked' => true,
            'completion_percentage' => 100,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot submit locked disclosure');

        $disclosure->submit($this->realCompanyUser->id, \App\Models\CompanyUser::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_approve_disclosure()
    {
        $disclosure = CompanyDisclosure::factory()->submitted()->create([
            'company_id' => $this->company->id,
            'disclosure_module_id' => $this->module->id,
        ]);

        // Create the approval record that submit() would normally create
        DisclosureApproval::create([
            'company_disclosure_id' => $disclosure->id,
            'company_id' => $this->company->id,
            'disclosure_module_id' => $this->module->id,
            'request_type' => 'initial_submission',
            'requested_by' => $this->realCompanyUser->id,
            'requested_at' => now(),
            'disclosure_version_number' => 1,
            'status' => 'pending',
        ]);

        // Approve disclosure
        $disclosure->approve($this->admin->id, 'All requirements met');

        // Assert status changed
        $refreshed = $disclosure->fresh();
        $this->assertEquals('approved', $refreshed->status);
        $this->assertNotNull($refreshed->approved_at);
        $this->assertEquals($this->admin->id, $refreshed->approved_by);
        $this->assertTrue($refreshed->is_locked);

        // Assert version created
        $this->assertDatabaseHas('disclosure_versions', [
            'company_disclosure_id' => $disclosure->id,
            'version_number' => 1,
            'approved_by' => $this->admin->id,
        ]);

        // Assert approval record updated
        $this->assertDatabaseHas('disclosure_approvals', [
            'company_disclosure_id' => $disclosure->id,
            'status' => 'approved',
            'reviewed_by' => $this->admin->id,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function approval_creates_immutable_version_snapshot()
    {
        $disclosureData = [
            'business_description' => 'Test business model',
            'revenue_streams' => [
                ['name' => 'Subscriptions', 'percentage' => 70],
                ['name' => 'Ads', 'percentage' => 30],
            ],
        ];

        $disclosure = CompanyDisclosure::factory()->submitted()->create([
            'company_id' => $this->company->id,
            'disclosure_module_id' => $this->module->id,
            'disclosure_data' => $disclosureData,
        ]);

        // Need approval record for approve() to work without failing on latest() update
        DisclosureApproval::create([
            'company_disclosure_id' => $disclosure->id,
            'company_id' => $this->company->id,
            'disclosure_module_id' => $this->module->id,
            'request_type' => 'initial_submission',
            'requested_by' => $this->realCompanyUser->id,
            'requested_at' => now(),
            'disclosure_version_number' => 1,
            'status' => 'pending',
        ]);

        $disclosure->approve($this->admin->id, 'Approved');

        // Get created version
        $version = $disclosure->fresh()->currentVersion;

        $this->assertNotNull($version);
        $this->assertEquals($disclosureData, $version->disclosure_data);
        $this->assertTrue($version->is_locked);
        $this->assertNotNull($version->locked_at);
        $this->assertEquals(hash('sha256', json_encode($disclosureData)), $version->version_hash);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_reject_disclosure()
    {
        $disclosure = CompanyDisclosure::factory()->submitted()->create([
            'company_id' => $this->company->id,
            'disclosure_module_id' => $this->module->id,
        ]);

        // Create the approval record
        DisclosureApproval::create([
            'company_disclosure_id' => $disclosure->id,
            'company_id' => $this->company->id,
            'disclosure_module_id' => $this->module->id,
            'request_type' => 'initial_submission',
            'requested_by' => $this->realCompanyUser->id,
            'requested_at' => now(),
            'disclosure_version_number' => 1,
            'status' => 'pending',
        ]);

        $disclosure->reject($this->admin->id, 'Incomplete financial data');

        $refreshed = $disclosure->fresh();
        $this->assertEquals('rejected', $refreshed->status);
        $this->assertNotNull($refreshed->rejected_at);
        $this->assertEquals($this->admin->id, $refreshed->rejected_by);
        $this->assertEquals('Incomplete financial data', $refreshed->rejection_reason);
        $this->assertFalse($refreshed->is_locked);

        // Assert approval record updated
        $this->assertDatabaseHas('disclosure_approvals', [
            'company_disclosure_id' => $disclosure->id,
            'status' => 'rejected',
            'reviewed_by' => $this->admin->id,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_request_clarifications()
    {
        $disclosure = CompanyDisclosure::factory()->submitted()->create([
            'company_id' => $this->company->id,
            'disclosure_module_id' => $this->module->id,
        ]);

        $clarifications = [
            [
                'question_subject' => 'Revenue growth clarification',
                'question_body' => 'Please explain 100% YoY revenue growth claim',
                'question_type' => 'verification',
                'priority' => 'high',
                'due_date' => now()->addDays(7),
                'field_path' => 'disclosure_data.revenue_streams',
            ],
        ];

        $disclosure->requestClarifications($this->admin->id, $clarifications);

        $this->assertEquals('clarification_required', $disclosure->fresh()->status);

        $this->assertDatabaseHas('disclosure_clarifications', [
            'company_disclosure_id' => $disclosure->id,
            'question_subject' => 'Revenue growth clarification',
            'asked_by' => $this->admin->id,
            'status' => 'open',
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function company_can_answer_clarification()
    {
        $clarification = DisclosureClarification::factory()->create([
            'company_id' => $this->company->id,
            'status' => 'open',
        ]);

        $clarification->submitAnswer(
            $this->realCompanyUser->id,
            'Growth was due to new product launch in Q3',
            [
                ['file_path' => 'docs/revenue-proof.pdf', 'uploaded_at' => now()->toIso8601String()],
            ],
            \App\Models\CompanyUser::class
        );

        $refreshed = $clarification->fresh();
        $this->assertEquals('answered', $refreshed->status);
        $this->assertEquals('Growth was due to new product launch in Q3', $refreshed->answer_body);
        $this->assertEquals($this->realCompanyUser->id, $refreshed->answered_by_id);
        $this->assertNotNull($refreshed->answered_at);
        $this->assertNotNull($refreshed->supporting_documents);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_accept_clarification_answer()
    {
        $clarification = DisclosureClarification::factory()->answered()->create([
            'company_id' => $this->company->id,
        ]);

        $clarification->acceptAnswer($this->admin->id, 'Explanation is satisfactory');

        $refreshed = $clarification->fresh();
        $this->assertEquals('accepted', $refreshed->status);
        $this->assertEquals($this->admin->id, $refreshed->resolved_by);
        $this->assertNotNull($refreshed->resolved_at);
        $this->assertEquals('Explanation is satisfactory', $refreshed->resolution_notes);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_dispute_clarification_answer()
    {
        $clarification = DisclosureClarification::factory()->answered()->create([
            'company_id' => $this->company->id,
        ]);

        $clarification->disputeAnswer($this->admin->id, 'Answer is insufficient. Please provide bank statements.');

        $refreshed = $clarification->fresh();
        $this->assertEquals('disputed', $refreshed->status);
        $this->assertEquals($this->admin->id, $refreshed->resolved_by);
        $this->assertNotNull($refreshed->resolved_at);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function disclosure_data_can_be_updated_when_not_locked()
    {
        $disclosure = CompanyDisclosure::factory()->draft()->create([
            'company_id' => $this->company->id,
            'disclosure_module_id' => $this->module->id,
        ]);

        $newData = [
            'business_description' => 'Updated business model',
            'revenue_streams' => [['name' => 'New stream', 'percentage' => 100]],
        ];

        $disclosure->updateDisclosureData($newData, $this->realCompanyUser->id, \App\Models\CompanyUser::class);

        $refreshed = $disclosure->fresh();
        $this->assertEquals($newData, $refreshed->disclosure_data);
        $this->assertEquals($this->realCompanyUser->id, $refreshed->last_modified_by_id);
        $this->assertNotNull($refreshed->last_modified_at);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function cannot_update_locked_disclosure_data()
    {
        $disclosure = CompanyDisclosure::factory()->approved()->create([
            'company_id' => $this->company->id,
            'disclosure_module_id' => $this->module->id,
            'is_locked' => true,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot update locked disclosure');

        $disclosure->updateDisclosureData(['test' => 'data'], $this->realCompanyUser->id, \App\Models\CompanyUser::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function version_number_increments_on_subsequent_approvals()
    {
        $disclosure = CompanyDisclosure::factory()->create([
            'company_id' => $this->company->id,
            'disclosure_module_id' => $this->module->id,
            'version_number' => 1,
            'status' => 'approved',
            'is_locked' => false, // Temporarily unlock for test
        ]);

        // Update and resubmit
        $disclosure->update(['status' => 'submitted', 'is_locked' => false]);
        $disclosure->update(['version_number' => 2]);

        // Approve again
        $disclosure->approve($this->admin->id, 'Version 2 approved');

        // Check version created with correct number
        $version = DisclosureVersion::where('company_disclosure_id', $disclosure->id)
            ->where('version_number', 2)
            ->first();

        $this->assertNotNull($version);
        $this->assertEquals(2, $version->version_number);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function disclosure_has_pending_clarifications_check()
    {
        $disclosure = CompanyDisclosure::factory()->create([
            'company_id' => $this->company->id,
        ]);

        // No clarifications
        $this->assertFalse($disclosure->hasPendingClarifications());

        // Create open clarification
        DisclosureClarification::factory()->open()->create([
            'company_disclosure_id' => $disclosure->id,
            'company_id' => $this->company->id,
        ]);

        $this->assertTrue($disclosure->hasPendingClarifications());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function disclosure_all_clarifications_answered_check()
    {
        $disclosure = CompanyDisclosure::factory()->create([
            'company_id' => $this->company->id,
        ]);

        // Create 3 clarifications
        DisclosureClarification::factory()->answered()->create([
            'company_disclosure_id' => $disclosure->id,
            'company_id' => $this->company->id,
        ]);
        DisclosureClarification::factory()->answered()->create([
            'company_disclosure_id' => $disclosure->id,
            'company_id' => $this->company->id,
        ]);
        DisclosureClarification::factory()->accepted()->create([
            'company_disclosure_id' => $disclosure->id,
            'company_id' => $this->company->id,
        ]);

        $this->assertTrue($disclosure->allClarificationsAnswered());

        // Add one open clarification
        DisclosureClarification::factory()->open()->create([
            'company_disclosure_id' => $disclosure->id,
            'company_id' => $this->company->id,
        ]);

        $this->assertFalse($disclosure->allClarificationsAnswered());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function disclosure_approval_tracks_sla()
    {
        $approval = DisclosureApproval::factory()->create([
            'company_id' => $this->company->id,
            'requested_by' => $this->realCompanyUser->id,
            'requested_at' => now()->subDays(6),
            'sla_due_date' => now()->subDays(1), // Overdue
            'status' => 'pending',
        ]);

        $this->assertTrue($approval->fresh()->sla_due_date->isPast());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function cannot_approve_disclosure_with_open_clarifications()
    {
        // Create submitted disclosure
        $disclosure = CompanyDisclosure::factory()->submitted()->create([
            'company_id' => $this->company->id,
            'disclosure_module_id' => $this->module->id,
        ]);

        // Create open clarification
        DisclosureClarification::factory()->open()->create([
            'company_disclosure_id' => $disclosure->id,
            'company_id' => $this->company->id,
        ]);

        // Attempt to approve should fail
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot approve disclosure with open clarifications');

        $disclosure->approve($this->admin->id, 'Attempting to approve');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function can_approve_disclosure_when_all_clarifications_answered_and_accepted()
    {
        // Create submitted disclosure
        $disclosure = CompanyDisclosure::factory()->submitted()->create([
            'company_id' => $this->company->id,
            'disclosure_module_id' => $this->module->id,
        ]);

        // Create clarifications that are all answered/accepted (no open ones)
        DisclosureClarification::factory()->answered()->create([
            'company_disclosure_id' => $disclosure->id,
            'company_id' => $this->company->id,
        ]);
        DisclosureClarification::factory()->accepted()->create([
            'company_disclosure_id' => $disclosure->id,
            'company_id' => $this->company->id,
        ]);

        // Should approve successfully (no open clarifications)
        $disclosure->approve($this->admin->id, 'All clarifications resolved');

        $this->assertEquals('approved', $disclosure->fresh()->status);
    }
}
