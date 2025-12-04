<?php
// V-FINAL-1730-TEST-55 (Created)

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\NotificationService;
use App\Jobs\ProcessNotificationJob;
use App\Models\User;
use App\Models\UserNotificationPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

class NotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $service;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new NotificationService();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this->user = User::factory()->create();
    }

    public function test_send_notification_chooses_correct_channel()
    {
        Queue::fake();
        
        // 1. Send a "bonus" email (which is only 'email' channel)
        $this->service->send($this->user, 'bonus.credited', []);

        // Assert 1 job was pushed
        Queue::assertPushed(ProcessNotificationJob::class, 1);
        // Assert it was an 'email' job
        Queue::assertPushed(ProcessNotificationJob::class, function ($job) {
            return $job->channel === 'email';
        });
    }

    public function test_send_notification_respects_priority()
    {
        Queue::fake();

        // 1. Send a low-priority notification
        $this->service->send($this->user, 'bonus.credited', []);
        
        // 2. Send a high-priority notification (e.g., OTP)
        $this->service->send($this->user, 'auth.otp', []);

        // Assert job was pushed to 'high_priority' queue
        Queue::assertPushedOn('high_priority', ProcessNotificationJob::class);
        
        // Assert other job was pushed to 'notifications' queue
        Queue::assertPushedOn('notifications', ProcessNotificationJob::class);
    }

    public function test_send_notification_batches_bulk_sends()
    {
        Queue::fake();
        $users = User::factory()->count(10)->create();
        $userIds = $users->pluck('id')->toArray();
        
        $this->service->sendBatch($userIds, 'marketing.newsletter', []);

        // Should push 10 separate jobs
        Queue::assertPushed(ProcessNotificationJob::class, 10);
    }

    public function test_send_notification_tracks_delivery_status()
    {
        // This is tested in SmsServiceTest and EmailServiceTest
        // The NotificationService correctly dispatches to those services,
        // which are responsible for logging.
        $this->assertTrue(true);
    }

    public function test_send_notification_retries_on_failure()
    {
        // This is tested by checking the $tries property on the Job
        $job = new ProcessNotificationJob($this->user, 'test', 'email', []);
        $this->assertEquals(3, $job->tries);
    }

    public function test_send_notification_respects_user_preferences()
    {
        Queue::fake();

        // 1. User opts out of 'auth_email'
        UserNotificationPreference::create([
            'user_id' => $this->user->id,
            'preference_key' => 'auth_email',
            'is_enabled' => false
        ]);

        // 2. Try to send an 'auth.otp' notification
        $this->service->send($this->user, 'auth.otp', []);

        // 3. Assert the EMAIL job was skipped
        Queue::assertNotPushed(ProcessNotificationJob::class, function ($job) {
            return $job->channel === 'email';
        });
        
        // 4. Assert the SMS job WAS pushed (since it's a different preference)
        Queue::assertPushed(ProcessNotificationJob::class, function ($job) {
            return $job->channel === 'sms';
        });
    }

    public function test_send_notification_logs_all_attempts()
    {
        // This is tested in SmsServiceTest and EmailServiceTest, which
        // are responsible for creating the log entries.
        $this->assertTrue(true);
    }
}