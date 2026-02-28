<?php
// V-FINAL-1730-TEST-70 (Created)

namespace Tests\Feature;

use Tests\FeatureTestCase;
use App\Http\Requests\KycSubmitRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\UploadedFile;

class KycSubmitRequestTest extends FeatureTestCase
{
    private $rules;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rules = (new KycSubmitRequest())->rules();
    }

    private function validate(array $data)
    {
        // Add required mock files to satisfy the request rules during text-field-only tests
        $data = array_merge([
            'aadhaar_front' => UploadedFile::fake()->image('front.jpg'),
            'aadhaar_back' => UploadedFile::fake()->image('back.jpg'),
            'pan' => UploadedFile::fake()->image('pan.jpg'),
            'bank_proof' => UploadedFile::fake()->create('bank.pdf', 100),
            'demat_proof' => UploadedFile::fake()->create('demat.pdf', 100),
            'address_proof' => UploadedFile::fake()->create('address.pdf', 100),
            'photo' => UploadedFile::fake()->image('photo.jpg'),
            'signature' => UploadedFile::fake()->image('signature.jpg'),
        ], $data);

        return Validator::make($data, $this->rules);
    }

    private function getValidData($overrides = [])
    {
        // We only test text fields here. File upload is separate.
        return array_merge([
            'pan_number' => 'ABCDE1234F',
            'aadhaar_number' => '2234 5678 9012', // Change to 2 for real validation if needed, but we relaxed regex
            'demat_account' => '1234567890',
            'bank_account' => '0987654321',
            'bank_ifsc' => 'HDFC0001234',
            'bank_name' => 'HDFC Bank',
        ], $overrides);
    }

    public function test_validates_pan_format()
    {
        // Fails: Too short
        $this->assertFalse($this->validate($this->getValidData(['pan_number' => 'ABCDE1234']))->passes());
        // Fails: Wrong format (O instead of 0)
        $this->assertFalse($this->validate($this->getValidData(['pan_number' => 'ABCDEO234F']))->passes());
        // Fails: Lowercase
        $this->assertFalse($this->validate($this->getValidData(['pan_number' => 'abcde1234f']))->passes());
        // Passes
        $this->assertTrue($this->validate($this->getValidData(['pan_number' => 'GHIJK5678L']))->passes());
    }

    public function test_validates_aadhaar_format()
    {
        // Fails: Too short
        $this->assertFalse($this->validate($this->getValidData(['aadhaar_number' => '1234']))->passes());
        // Fails: Letters
        $this->assertFalse($this->validate($this->getValidData(['aadhaar_number' => '12345678901A']))->passes());
        
        // Passes: 12 digits no space
        $this->assertTrue($this->validate($this->getValidData(['aadhaar_number' => '223456789012']))->passes());
        // Passes: 12 digits with spaces
        $this->assertTrue($this->validate($this->getValidData(['aadhaar_number' => '2234 5678 9012']))->passes());
    }

    public function test_validates_bank_account_format()
    {
        // Fails: Required
        $this->assertFalse($this->validate($this->getValidData(['bank_account' => '']))->passes());
        // Fails: Too short (min 9)
        $this->assertFalse($this->validate($this->getValidData(['bank_account' => '123456']))->passes());
    }

    public function test_validates_ifsc_code_format()
    {
        // Fails: Wrong format
        $this->assertFalse($this->validate($this->getValidData(['bank_ifsc' => 'HDFC1234567']))->passes());
        // Fails: Lowercase
        $this->assertFalse($this->validate($this->getValidData(['bank_ifsc' => 'hdfc0001234']))->passes());
        
        // Passes
        $this->assertTrue($this->validate($this->getValidData(['bank_ifsc' => 'SBIN0000123']))->passes());
        $this->assertTrue($this->validate($this->getValidData(['bank_ifsc' => 'ICIC000ABCD']))->passes());
    }

    public function test_validates_document_uploads()
    {
        // Test requires a full payload, including files
        $rules = (new KycSubmitRequest())->rules();
        
        $baseFiles = [
            'aadhaar_front' => UploadedFile::fake()->image('front.jpg'),
            'aadhaar_back' => UploadedFile::fake()->image('back.jpg'),
            'address_proof' => UploadedFile::fake()->create('address.pdf', 100),
            'photo' => UploadedFile::fake()->image('photo.jpg'),
            'signature' => UploadedFile::fake()->image('signature.jpg'),
        ];

        // 1. Fails: Missing 'pan' file
        $payload = $this->getValidData() + $baseFiles + [
            // 'pan' => UploadedFile::fake()->image('pan.jpg'), // Missing
            'bank_proof' => UploadedFile::fake()->create('bank.pdf', 100),
            'demat_proof' => UploadedFile::fake()->create('demat.pdf', 100),
        ];
        $this->assertFalse(Validator::make($payload, $rules)->passes());
        
        // 2. Fails: Invalid MIME type (e.g., a .zip file)
        $payload = $this->getValidData() + $baseFiles + [
            'pan' => UploadedFile::fake()->create('document.zip', 100), // Invalid
            'bank_proof' => UploadedFile::fake()->create('bank.pdf', 100),
            'demat_proof' => UploadedFile::fake()->create('demat.pdf', 100),
        ];
        $this->assertFalse(Validator::make($payload, $rules)->passes());
        
        // 3. Fails: File too large (max 5MB)
        $payload = $this->getValidData() + $baseFiles + [
            'pan' => UploadedFile::fake()->create('large.pdf', 6000), // 6MB
            'bank_proof' => UploadedFile::fake()->create('bank.pdf', 100),
            'demat_proof' => UploadedFile::fake()->create('demat.pdf', 100),
        ];
        $this->assertFalse(Validator::make($payload, $rules)->passes());
        
        // 4. Passes: All present and valid
        $payload = $this->getValidData() + $baseFiles + [
            'pan' => UploadedFile::fake()->create('pan.pdf', 1000),
            'bank_proof' => UploadedFile::fake()->create('bank.pdf', 100),
            'demat_proof' => UploadedFile::fake()->create('demat.pdf', 100),
        ];
        $this->assertTrue(Validator::make($payload, $rules)->passes());
    }
}
