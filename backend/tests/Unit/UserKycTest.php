<?php
// V-FINAL-1730-TEST-15

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\UserKyc;
use App\Models\KycDocument;
use Illuminate\Support\Facades\Validator;

class UserKycTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_kyc_belongs_to_user()
    {
        $user = User::factory()->create();
        $kyc = UserKyc::create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $kyc->user);
        $this->assertEquals($user->id, $kyc->user->id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_kyc_has_documents_relationship()
    {
        $user = User::factory()->create();
        $kyc = UserKyc::create(['user_id' => $user->id]);
        
        KycDocument::create([
            'user_kyc_id' => $kyc->id,
            'doc_type' => 'pan',
            'file_path' => 'dummy.jpg',
            'file_name' => 'dummy.jpg',
            'mime_type' => 'image/jpeg'
        ]);

        $this->assertTrue($kyc->documents()->exists());
        $this->assertEquals(1, $kyc->documents->count());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_verification_status_enum_validates()
    {
        // We test this using a Validator instance to simulate controller logic
        $rules = ['status' => 'in:pending,submitted,verified,rejected'];
        
        $this->assertTrue(Validator::make(['status' => 'verified'], $rules)->passes());
        $this->assertTrue(Validator::make(['status' => 'rejected'], $rules)->passes());
        
        $this->assertFalse(Validator::make(['status' => 'invalid_status'], $rules)->passes());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_kyc_can_be_approved()
    {
        $user = User::factory()->create();
        $kyc = UserKyc::create(['user_id' => $user->id, 'status' => 'submitted']);

        $kyc->update(['status' => 'verified', 'verified_at' => now()]);

        $this->assertEquals('verified', $kyc->fresh()->status);
        $this->assertNotNull($kyc->fresh()->verified_at);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_kyc_can_be_rejected_with_reason()
    {
        $user = User::factory()->create();
        $kyc = UserKyc::create(['user_id' => $user->id, 'status' => 'submitted']);

        $kyc->update([
            'status' => 'rejected', 
            'rejection_reason' => 'Blurry image'
        ]);

        $this->assertEquals('rejected', $kyc->fresh()->status);
        $this->assertEquals('Blurry image', $kyc->fresh()->rejection_reason);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_kyc_calculates_completion_percentage()
    {
        $user = User::factory()->create();
        $kyc = UserKyc::create([
            'user_id' => $user->id,
            'pan_number' => 'ABCDE1234F',
            'aadhaar_number' => '123456789012'
            // Bank and Demat missing
        ]);

        // 2 fields filled out of 6 steps (approx 33%)
        $this->assertEquals(33, $kyc->completion_percentage);

        // Add documents
        KycDocument::create([
            'user_kyc_id' => $kyc->id,
            'doc_type' => 'pan',
            'file_path' => 'path',
            'file_name' => 'name',
            'mime_type' => 'img'
        ]);

        // 3 steps done out of 6 (50%)
        $this->assertEquals(50, $kyc->fresh()->completion_percentage);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_kyc_checks_all_documents_uploaded()
    {
        $user = User::factory()->create();
        $kyc = UserKyc::create(['user_id' => $user->id]);

        // Only upload PAN
        KycDocument::create([
            'user_kyc_id' => $kyc->id,
            'doc_type' => 'pan',
            'file_path' => 'path', 'file_name' => 'name', 'mime_type' => 'img'
        ]);

        $this->assertFalse($kyc->fresh()->hasAllDocuments());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_kyc_marks_as_complete_when_all_docs_present()
    {
        $user = User::factory()->create();
        $kyc = UserKyc::create(['user_id' => $user->id]);

        $required = ['pan', 'aadhaar_front', 'aadhaar_back', 'bank_proof'];
        
        foreach ($required as $type) {
            KycDocument::create([
                'user_kyc_id' => $kyc->id,
                'doc_type' => $type,
                'file_path' => 'path', 'file_name' => 'name', 'mime_type' => 'img'
            ]);
        }

        $this->assertTrue($kyc->fresh()->hasAllDocuments());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_pan_number_format_validates()
    {
        // Valid PAN
        $this->assertMatchesRegularExpression(UserKyc::PAN_REGEX, 'ABCDE1234F');

        // Invalid PANs
        $this->assertDoesNotMatchRegularExpression(UserKyc::PAN_REGEX, '1234567890'); // Numbers only
        $this->assertDoesNotMatchRegularExpression(UserKyc::PAN_REGEX, 'ABCDE1234'); // Too short
        $this->assertDoesNotMatchRegularExpression(UserKyc::PAN_REGEX, 'abcdef1234g'); // Lowercase (regex expects uppercase usually, though input cleaning handles this)
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_aadhaar_number_format_validates()
    {
        // Valid Aadhaar (12 digits)
        $this->assertMatchesRegularExpression(UserKyc::AADHAAR_REGEX, '123456789012');

        // Invalid
        $this->assertDoesNotMatchRegularExpression(UserKyc::AADHAAR_REGEX, '1234'); // Too short
        $this->assertDoesNotMatchRegularExpression(UserKyc::AADHAAR_REGEX, '1234abc56789'); // Alphanumeric
    }
}