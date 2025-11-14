<?php
// V-FINAL-1730-TEST-14

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\OtpService;
use App\Models\User;
use App\Models\Otp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class OtpServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $service;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new OtpService();
        $this->user = User::factory()->create();
    }

    /** @test */
    public function test_generate_otp_creates_6_digit_code()
    {
        $otp = $this->service->generate($this->user, 'email');
        
        $this->assertNotNull($otp);
        $this->assertEquals(6, strlen($otp->otp_code));
        $this->assertMatchesRegularExpression('/^[0-9]+$/', $otp->otp_code);
    }

    /** @test */
    public function test_generate_otp_sets_10_minute_expiry()
    {
        $otp = $this->service->generate($this->user, 'email');
        
        // Check if expires_at is roughly 10 minutes from now (allow 5s delta)
        $this->assertTrue(
            $otp->expires_at->diffInSeconds(now()->addMinutes(10)) < 5
        );
    }

    /** @test */
    public function test_generate_otp_stores_in_database()
    {
        $this->service->generate($this->user, 'mobile');

        $this->assertDatabaseHas('otps', [
            'user_id' => $this->user->id,
            'type' => 'mobile'
        ]);
    }

    /** @test */
    public function test_verify_otp_validates_correct_code()
    {
        $otp = $this->service->generate($this->user, 'email');
        
        $result = $this->service->verify($this->user, 'email', $otp->otp_code);
        
        $this->assertTrue($result);
        $this->assertDatabaseMissing('otps', ['id' => $otp->id]); // Should be deleted on success
    }

    /** @test */
    public function test_verify_otp_rejects_expired_code()
    {
        $otp = $this->service->generate($this->user, 'email');
        $otp->update(['expires_at' => now()->subMinute()]); // Expire it

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("OTP has expired");

        $this->service->verify($this->user, 'email', $otp->otp_code);
    }

    /** @test */
    public function test_verify_otp_rejects_incorrect_code()
    {
        $this->service->generate($this->user, 'email');
        
        $result = $this->service->verify($this->user, 'email', '000000');
        
        $this->assertFalse($result);
    }

    /** @test */
    public function test_verify_otp_increments_attempt_count()
    {
        $otp = $this->service->generate($this->user, 'email');
        
        $this->service->verify($this->user, 'email', '000000'); // Fail 1
        
        $this->assertEquals(1, $otp->fresh()->attempts);
    }

    /** @test */
    public function test_verify_otp_blocks_after_3_attempts()
    {
        $otp = $this->service->generate($this->user, 'email');
        
        $this->service->verify($this->user, 'email', '000000'); // Fail 1
        $this->service->verify($this->user, 'email', '000000'); // Fail 2
        $this->service->verify($this->user, 'email', '000000'); // Fail 3 (Blocks)

        $this->assertTrue($otp->fresh()->blocked);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Too many failed attempts");

        $this->service->verify($this->user, 'email', $otp->otp_code); // Fail 4 (Should throw)
    }

    /** @test */
    public function test_resend_otp_respects_cooldown_period()
    {
        // Generate first OTP
        $this->service->generate($this->user, 'email');
        
        // Immediately try to generate another
        $secondAttempt = $this->service->generate($this->user, 'email');
        
        $this->assertNull($secondAttempt); // Should fail due to cooldown

        // Travel 61 seconds into future
        $this->travel(61)->seconds();
        
        $thirdAttempt = $this->service->generate($this->user, 'email');
        $this->assertNotNull($thirdAttempt); // Should succeed
    }

    /** @test */
    public function test_otp_cleanup_deletes_expired_entries()
    {
        // Create an old OTP
        Otp::create([
            'user_id' => $this->user->id,
            'type' => 'email',
            'otp_code' => '123456',
            'expires_at' => now()->subHours(2), // 2 hours ago
            'last_sent_at' => now()->subHours(2)
        ]);

        $this->service->cleanup();

        $this->assertDatabaseCount('otps', 0);
    }
}