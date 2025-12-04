<?php
// V-FINAL-1730-TEST-17

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\VerificationService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Razorpay\Api\Api;
use Mockery;

class VerificationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new VerificationService();
        // We force env to 'production' for these tests to bypass the "local mock" in the service
        // and actually test the API logic (which we will mock via Http facade)
        config(['app.env' => 'production']);
        config(['services.kyc.url' => 'https://api.vendor.com']);
        config(['services.kyc.key' => 'secret']);
    }

    // --- PAN TESTS ---
    #[\PHPUnit\Framework\Attributes\Test]
    public function test_verify_pan_calls_income_tax_api()
    {
        Http::fake([
            'https://api.vendor.com/pan/verify' => Http::response([
                'full_name' => 'John Doe', 
                'dob' => '1990-01-01'
            ], 200)
        ]);

        $result = $this->service->verifyPan('ABCDE1234F', 'John Doe', '1990-01-01');

        Http::assertSent(function ($request) {
            return $request->url() == 'https://api.vendor.com/pan/verify' &&
                   $request['pan'] == 'ABCDE1234F';
        });

        $this->assertTrue($result['valid']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_verify_pan_validates_name_match()
    {
        Http::fake([
            '*' => Http::response(['full_name' => 'Jane Doe', 'dob' => '1990-01-01'], 200)
        ]);

        // Input "John Doe" vs API "Jane Doe" -> Mismatch
        $result = $this->service->verifyPan('ABCDE1234F', 'John Doe', '1990-01-01');

        $this->assertFalse($result['valid']);
        $this->assertEquals('Name mismatch on PAN', $result['error']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_verify_pan_validates_dob_match()
    {
        Http::fake([
            '*' => Http::response(['full_name' => 'John Doe', 'dob' => '1990-01-01'], 200)
        ]);

        // Input "1995-01-01" vs API "1990-01-01" -> Mismatch
        $result = $this->service->verifyPan('ABCDE1234F', 'John Doe', '1995-01-01');

        $this->assertFalse($result['valid']);
        $this->assertEquals('DOB mismatch on PAN', $result['error']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_verify_pan_handles_api_failure()
    {
        Http::fake([
            '*' => Http::response(null, 500)
        ]);

        $result = $this->service->verifyPan('ABCDE1234F', 'John Doe');

        $this->assertFalse($result['valid']);
        $this->assertEquals('Service unavailable', $result['error']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_verify_pan_caches_successful_result()
    {
        Cache::shouldReceive('has')->once()->andReturn(false);
        Cache::shouldReceive('put')->once(); // Assert we save to cache
        
        Http::fake([
            '*' => Http::response(['full_name' => 'John Doe'], 200)
        ]);

        $this->service->verifyPan('ABCDE1234F', 'John Doe');
    }

    // --- AADHAAR TESTS ---
    #[\PHPUnit\Framework\Attributes\Test]
    public function test_verify_aadhaar_calls_digilocker_api()
    {
        Http::fake([
            '*/aadhaar/verify' => Http::response(['name' => 'John Doe', 'dob' => '1990-01-01'], 200)
        ]);

        $this->service->verifyAadhaar('123456789012', 'John Doe');

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/aadhaar/verify') &&
                   $request['aadhaar_number'] == '123456789012';
        });
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_verify_aadhaar_validates_name_match()
    {
        Http::fake([
            '*' => Http::response(['name' => 'Jane Doe'], 200)
        ]);

        $result = $this->service->verifyAadhaar('1234', 'John Doe');
        $this->assertFalse($result['valid']);
        $this->assertEquals('Name mismatch', $result['error']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_verify_aadhaar_handles_api_failure()
    {
        Http::fake(['*' => Http::response(null, 500)]);
        
        $result = $this->service->verifyAadhaar('1234', 'John');
        $this->assertFalse($result['valid']);
    }

    // --- BANK TESTS ---
    #[\PHPUnit\Framework\Attributes\Test]
    public function test_verify_bank_account_validates_ifsc_code()
    {
        // Invalid IFSC format
        $result = $this->service->verifyBank('123456', 'INVALID', 'John');
        
        $this->assertFalse($result['valid']);
        $this->assertEquals('Invalid IFSC format', $result['error']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_verification_logs_all_attempts()
    {
        Log::shouldReceive('info')->atLeast()->once();
        
        // Need to stub cache/http to prevent real errors from stopping logic
        Cache::shouldReceive('has')->andReturn(false);
        Http::fake(['*' => Http::response(['full_name' => 'John'], 200)]);

        $this->service->verifyPan('ABCDE1234F', 'John');
    }
}