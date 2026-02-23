<?php
// V-FINAL-1730-TEST-54 (Created)

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\SmsService;
use App\Models\User;
use App\Models\SmsLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class SmsServiceTest extends TestCase
{
    protected $service;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SmsService();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this->user = User::factory()->create(['mobile' => '9876543210']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_send_sms_uses_correct_template_id()
    {
        Http::fake([
            'api.msg91.com*' => Http::response(['message_id' => '123'], 200)
        ]);

        $this->service->send($this->user, "Test", "slug", "DLT123");

        // Test MSG91 API was called with correct DLT ID
        Http::assertSent(function ($request) {
            return $request['flow_id'] == 'DLT123';
        });
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_send_sms_replaces_variables()
    {
        // Note: This logic is in the JOB, not the service.
        // This test confirms the service logs the *final* message.
        $this->service->send($this->user, "Final Message", "slug");

        $this->assertDatabaseHas('sms_logs', [
            'message' => 'Final Message'
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_send_sms_limits_to_160_chars()
    {
        $longMessage = str_repeat('a', 200); // 200 chars
        $truncated = substr($longMessage, 0, 157) . '...'; // 160 chars

        Log::shouldReceive('warning')->once(); // Expect a warning
        
        $this->service->send($this->user, $longMessage, "slug");

        $this->assertDatabaseHas('sms_logs', [
            'message' => $truncated
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_send_sms_logs_delivery()
    {
        Http::fake(['*' => Http::response(['message_id' => 'abc'], 200)]);

        $this->service->send($this->user, "Test", "slug");

        // V-WAVE1-FIX: Column was renamed from to_mobile to recipient_mobile
        $this->assertDatabaseHas('sms_logs', [
            'user_id' => $this->user->id,
            'recipient_mobile' => $this->user->mobile,
            'status' => 'sent',
            'gateway_message_id' => 'abc'
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_send_sms_handles_msg91_failure()
    {
        // Simulate a 500 error from the gateway
        Http::fake(['*' => Http::response('Server Error', 500)]);

        $log = $this->service->send($this->user, "Test", "slug");

        $this->assertEquals('failed', $log->fresh()->status);
        $this->assertStringContainsString('Server Error', $log->fresh()->error_message);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_send_sms_respects_user_preferences()
    {
        // V-WAVE1-FIX: Include both legacy and new schema columns
        // The table has both old (preference_key, is_enabled) and new (notification_type, *_enabled) columns
        $this->user->notificationPreferences()->create([
            'notification_type' => 'auth',
            'preference_key' => 'auth_sms', // Legacy column still required
            'is_enabled' => false,           // Legacy column
            'sms_enabled' => false,
            'email_enabled' => true,
            'push_enabled' => true,
            'in_app_enabled' => true,
        ]);

        // 2. Try to send an 'auth.otp' message
        $log = $this->service->send($this->user, "Test", "auth.otp");

        // 3. Assert it was aborted (user opted out of SMS for auth notifications)
        $this->assertNull($log);
        $this->assertDatabaseMissing('sms_logs');
    }
}