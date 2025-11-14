<?php
// V-FINAL-1730-TEST-69 (Created)

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Http\Requests\RegisterRequest;
use Illuminate\Support\Facades\Validator;
use App\Models\User;

class RegisterRequestTest extends TestCase
{
    use RefreshDatabase;

    private $rules;

    protected function setUp(): void
    {
        parent::setUp();
        // Get the rules from the FormRequest class
        $this->rules = (new RegisterRequest())->rules();
    }

    /**
     * Helper to run validation
     */
    private function validate(array $data)
    {
        return Validator::make($data, $this->rules);
    }

    /**
     * Helper for a 100% valid payload
     */
    private function getValidData($overrides = [])
    {
        return array_merge([
            'username' => 'valid_user',
            'email' => 'valid@example.com',
            'mobile' => '9876543210',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'referral_code' => null,
        ], $overrides);
    }

    /** @test */
    public function test_validates_username_format()
    {
        // Fails: required
        $this->assertFalse($this->validate($this->getValidData(['username' => '']))->passes());
        
        // Fails: min 3
        $this->assertFalse($this->validate($this->getValidData(['username' => 'a']))->passes());
        
        // Fails: alpha_dash (no spaces)
        $this->assertFalse($this->validate($this->getValidData(['username' => 'a b']))->passes());
        
        // Fails: unique
        User::factory()->create(['username' => 'existing']);
        $this->assertFalse($this->validate($this->getValidData(['username' => 'existing']))->passes());

        // Passes
        $this->assertTrue($this->validate($this->getValidData(['username' => 'valid_user-123']))->passes());
    }

    /** @test */
    public function test_validates_email_format()
    {
        // Fails: required
        $this->assertFalse($this->validate($this->getValidData(['email' => '']))->passes());

        // Fails: email
        $this->assertFalse($this->validate($this->getValidData(['email' => 'not-an-email']))->passes());
        
        // Fails: unique
        User::factory()->create(['email' => 'taken@example.com']);
        $this.assertFalse($this.validate($this.getValidData(['email' => 'taken@example.com']))->passes());
    }

    /** @test */
    public function test_validates_mobile_10_digits()
    {
        // Fails: required
        $this->assertFalse($this->validate($this->getValidData(['mobile' => '']))->passes());

        // Fails: not 10 digits
        $this->assertFalse($this->validate($this->getValidData(['mobile' => '12345']))->passes());

        // Fails: not numeric
        $this->assertFalse($this->validate($this->getValidData(['mobile' => 'abcdefghij']))->passes());

        // Fails: unique
        User::factory()->create(['mobile' => '1111111111']);
        $this.assertFalse($this.validate($this.getValidData(['mobile' => '1111111111']))->passes());
    }

    /** @test */
    public function test_validates_password_complexity()
    {
        // Fails: required
        $this->assertFalse($this->validate($this->getValidData(['password' => '']))->passes());
        
        // Fails: confirmed
        $this->assertFalse($this->validate($this->getValidData(['password_confirmation' => 'Mismatch123!']))->passes());
        
        // Fails: min 8
        $this->assertFalse($this->validate($this->getValidData(['password' => 'Pass1!', 'password_confirmation' => 'Pass1!']))->passes());

        // Fails: mixedCase
        $this.assertFalse($this.validate($this.getValidData(['password' => 'password123!', 'password_confirmation' => 'password123!']))->passes());

        // Fails: numbers
        $this.assertFalse($this.validate($this.getValidData(['password' => 'Password!', 'password_confirmation' => 'Password!']))->passes());
        
        // Fails: symbols
        $this.assertFalse($this.validate($this.getValidData(['password' => 'Password123', 'password_confirmation' => 'Password123']))->passes());

        // Passes
        $this.assertTrue($this.validate($this.getValidData(['password' => 'Password123!', 'password_confirmation' => 'Password123!']))->passes());
    }

    /** @test */
    public function test_validates_referral_code_if_provided()
    {
        // 1. Passes if null (it's nullable)
        $this.assertTrue($this.validate($this.getValidData(['referral_code' => null]))->passes());

        // 2. Fails if invalid
        $this.assertFalse($this.validate($this.getValidData(['referral_code' => 'INVALIDCODE']))->passes());

        // 3. Passes if valid
        $referrer = User::factory()->create(['referral_code' => 'VALIDCODE']);
        $this.assertTrue($this.validate($this.getValidData(['referral_code' => 'VALIDCODE']))->passes());
    }
}