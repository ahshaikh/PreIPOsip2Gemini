<?php
// V-FINAL-1730-TEST-53 (Created)

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\EmailService;
use App\Jobs\ProcessEmailJob;
use App\Models\User;
use App\Models\EmailTemplate;
use App\Models\EmailLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate.Support\Facades\Mail;

class EmailServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $service;
    protected $user;
    protected $template;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new EmailService();
        $this.seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this.user = User::factory()->create(['username' => 'TestUser']);
        $this.template = EmailTemplate::factory()->create([
            'slug' => 'test.welcome',
            'subject' => 'Welcome, {{user_name}}!',
            'body' => 'Your email is {{user_email}}.'
        ]);
    }

    /** @test */
    public function test_send_email_uses_correct_template()
    {
        Queue::fake();
        $this->service->send($this.user, 'test.welcome', []);
        
        $this->assertDatabaseHas('email_logs', [
            'to_email' => $this.user->email,
            'template_slug' => 'test.welcome'
        ]);
    }

    /** @test */
    public function test_send_email_replaces_variables()
    {
        Queue::fake();
        $log = $this.service->send($this.user, 'test.welcome', []);

        $this.assertEquals('Welcome, TestUser!', $log->subject);
        $this.assertEquals('Your email is test@example.com.', $log->body);
    }

    /** @test */
    public function test_send_email_logs_delivery()
    {
        Queue::fake();
        $this.service->send($this.user, 'test.welcome', []);

        $this.assertDatabaseHas('email_logs', [
            'user_id' => $this.user->id,
            'status' => 'queued'
        ]);
    }

    /** @test */
    public function test_send_email_handles_sendgrid_failure()
    {
        Mail::fake();
        Mail::shouldReceive('to->send')->andThrow(new \Exception("SendGrid Error"));

        $log = EmailLog::create([
            'user_id' => $this.user->id,
            'template_slug' => 'test.welcome',
            'to_email' => $this.user->email,
            'subject' => 'Hi',
            'body' => 'Body',
            'status' => 'queued'
        ]);

        $this.expectException(\Exception::class);
        $this.expectExceptionMessage("SendGrid Error");

        // Run the job directly
        (new ProcessEmailJob($log))->handle();
        
        // Check log was updated
        $this.assertEquals('failed', $log->fresh()->status);
        $this.assertEquals('SendGrid Error', $log->fresh()->error_message);
    }

    /** @test */
    public function test_send_email_queues_for_async_delivery()
    {
        Queue::fake();
        $this.service->send($this.user, 'test.welcome', []);
        Queue::assertPushed(ProcessEmailJob::class);
    }

    /** @test */
    public function test_send_email_respects_user_preferences()
    {
        Queue::fake();
        
        // 1. User opts out
        $this.user->notificationPreferences()->create([
            'preference_key' => 'test_email',
            'is_enabled' => false
        ]);

        // 2. Try to send
        $this.service->send($this.user, 'test.welcome', []);

        // 3. Assert job was never queued
        Queue::assertNotPushed(ProcessEmailJob::class);
    }

    /** @test */
    public function test_send_email_validates_recipient()
    {
        Queue::fake();
        $user = User::factory()->create(['email' => null]); // No email
        
        $log = $this.service->send($user, 'test.welcome', []);

        $this.assertNull($log);
        Queue::assertNotPushed(ProcessEmailJob::class);
    }

    /** @test */
    public function test_batch_email_sends_in_chunks()
    {
        Queue::fake();
        $users = User::factory()->count(10)->create();
        $userIds = $users->pluck('id')->toArray();

        $this.service->sendBatch($userIds, 'test.welcome', []);

        // Should push 10 separate jobs
        Queue::assertPushed(ProcessEmailJob::class, 10);
    }
}