<?php
// V-FINAL-1730-TEST-16

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\UserKyc;
use App\Models\KycDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;

class KycDocumentTest extends TestCase
{
    use RefreshDatabase;

    protected $kyc;
    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $user = User::factory()->create();
        $this->kyc = UserKyc::create(['user_id' => $user->id]);
        
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_document_belongs_to_kyc()
    {
        $doc = KycDocument::create([
            'user_kyc_id' => $this->kyc->id,
            'doc_type' => 'pan',
            'file_path' => 'path/to/file.jpg',
            'file_name' => 'file.jpg',
            'mime_type' => 'image/jpeg'
        ]);

        $this->assertInstanceOf(UserKyc::class, $doc->kyc);
        $this->assertEquals($this->kyc->id, $doc->kyc->id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_document_stores_file_path()
    {
        $doc = KycDocument::create([
            'user_kyc_id' => $this->kyc->id,
            'doc_type' => 'pan',
            'file_path' => 'secure/docs/123.jpg',
            'file_name' => 'my_pan.jpg',
            'mime_type' => 'image/jpeg'
        ]);

        $this->assertEquals('secure/docs/123.jpg', $doc->file_path);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_document_type_enum_validates()
    {
        // We check this using the model's helper method or standard validation rules
        $validTypes = KycDocument::getValidTypes();
        
        $this->assertTrue(in_array('pan', $validTypes));
        $this->assertTrue(in_array('aadhaar_front', $validTypes));
        $this->assertFalse(in_array('invalid_type', $validTypes));
        
        // Controller validation simulation
        $validator = Validator::make(['doc_type' => 'pan'], ['doc_type' => 'in:' . implode(',', $validTypes)]);
        $this->assertTrue($validator->passes());
        
        $validator = Validator::make(['doc_type' => 'selfie'], ['doc_type' => 'in:' . implode(',', $validTypes)]);
        $this->assertFalse($validator->passes());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_document_status_enum_validates()
    {
        $validStatuses = KycDocument::getValidStatuses();
        
        $this->assertTrue(in_array('pending', $validStatuses));
        $this->assertTrue(in_array('approved', $validStatuses));
        $this->assertFalse(in_array('archived', $validStatuses));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_document_can_be_approved()
    {
        $doc = KycDocument::create([
            'user_kyc_id' => $this->kyc->id,
            'doc_type' => 'pan',
            'file_path' => 'path',
            'file_name' => 'name',
            'mime_type' => 'img',
            'status' => 'pending'
        ]);

        $doc->update([
            'status' => 'approved',
            'verified_by' => $this->admin->id,
            'verified_at' => now()
        ]);

        $this->assertEquals('approved', $doc->fresh()->status);
        $this->assertEquals($this->admin->id, $doc->fresh()->verified_by);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_document_can_be_rejected()
    {
        $doc = KycDocument::create([
            'user_kyc_id' => $this->kyc->id,
            'doc_type' => 'pan',
            'file_path' => 'path', 'file_name' => 'name', 'mime_type' => 'img'
        ]);

        $doc->update(['status' => 'rejected']);

        $this->assertEquals('rejected', $doc->fresh()->status);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_document_tracks_verified_by_admin()
    {
        $doc = KycDocument::create([
            'user_kyc_id' => $this->kyc->id,
            'doc_type' => 'pan',
            'file_path' => 'path', 'file_name' => 'name', 'mime_type' => 'img'
        ]);

        $doc->update(['verified_by' => $this->admin->id]);

        $this->assertTrue($doc->verifier()->exists());
        $this->assertEquals($this->admin->id, $doc->verifier->id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_document_has_verification_notes()
    {
        $doc = KycDocument::create([
            'user_kyc_id' => $this->kyc->id,
            'doc_type' => 'pan',
            'file_path' => 'path', 'file_name' => 'name', 'mime_type' => 'img'
        ]);

        $note = "Image is blurry, please re-upload.";
        $doc->update([
            'status' => 'rejected',
            'verification_notes' => $note
        ]);

        $this->assertEquals($note, $doc->fresh()->verification_notes);
    }
}