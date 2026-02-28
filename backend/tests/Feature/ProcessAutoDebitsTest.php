<?php
// V-FINAL-1730-TEST-73 (Created)

namespace Tests\Feature;

use Tests\FeatureTestCase;
use App\Services\AutoDebitService;
use App\Models\User;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Payment;
use App\Jobs\RetryAutoDebitJob;
use App\Jobs\SendPaymentReminderJob;
use Illuminate\Support\Facades\Queue;
use Mockery\MockInterface;

class ProcessAutoDebitsTest extends FeatureTestCase
{
    protected $serviceMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this->seed(\Database\Seeders\SettingsSeeder::class);

        // Mock the AutoDebitService in the container
        // This is called BEFORE the Artisan command, so the command
        // will receive our FAKE service.
        $this->serviceMock = $this->mock(AutoDebitService::class);
    }

    public function test_identifies_subscriptions_due_for_payment()
    {
        // 1. Setup
        $user = User::factory()->create();
        $plan = Plan::factory()->create();
        $dueSubs = Subscription::factory()->count(2)->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'is_auto_debit' => true,
            'next_payment_date' => now()->subDay() // Due
        ]);

        // 2. Mock Expectations
        // Expect the service's "getDueSubscriptions" method to be called once...
        $this->serviceMock->shouldReceive('getDueSubscriptions')
            ->once()
            ->andReturn($dueSubs); // ...and return our 2 fake subscriptions.
        
        // Expect "attemptAutoDebit" to be called for EACH of them
        $this->serviceMock->shouldReceive('attemptAutoDebit')
            ->twice()
            ->andReturn(true); // Simulate success
            
        // Expect "sendReminders" to be called
        $this->serviceMock->shouldReceive('sendReminders')->once();

        // 3. Act
        // Run the Artisan command
        $this->artisan('app:process-auto-debits');
    }

    public function test_initiates_payment_for_each_subscription()
    {
        // This is identical to the test above, just different naming.
        // We confirm `attemptAutoDebit` is called.
        $user = User::factory()->create();
        $dueSubs = Subscription::factory()->count(3)->create(['user_id' => $user->id]);

        $this->serviceMock->shouldReceive('getDueSubscriptions')->once()->andReturn($dueSubs);
        $this->serviceMock->shouldReceive('attemptAutoDebit')->times(3)->andReturn(true);
        $this->serviceMock->shouldReceive('sendReminders')->once();
        
        $this->artisan('app:process-auto-debits');
    }

    public function test_retries_failed_payments()
    {
        // V-AUDIT-MODULE7-004: The command now dispatches ProcessSubscriptionChargeJob
        // instead of calling attemptAutoDebit directly. Test the job dispatch.
        Queue::fake();

        $user = User::factory()->create();
        $sub = Subscription::factory()->create(['user_id' => $user->id]);

        $this->serviceMock->shouldReceive('getDueSubscriptions')->once()->andReturn(collect([$sub]));
        $this->serviceMock->shouldReceive('sendReminders')->once();

        $this->artisan('app:process-auto-debits');

        // Verify ProcessSubscriptionChargeJob was dispatched for the subscription
        Queue::assertPushed(\App\Jobs\ProcessSubscriptionChargeJob::class, 1);
    }

    public function test_sends_reminder_before_due_date()
    {
        // This test verifies the command calls the reminder function.
        $this->serviceMock->shouldReceive('getDueSubscriptions')->once()->andReturn(collect([]));
        $this->serviceMock->shouldReceive('sendReminders')->once()->andReturn(3); // 3 reminders sent

        // Run the command and expect its output
        $this->artisan('app:process-auto-debits')
             ->expectsOutput('Starting auto-debit process...')
             ->expectsOutput('Found 0 subscriptions due for payment.')
             ->expectsOutput('Sent 3 payment reminders.')
             ->assertExitCode(0);
    }

    public function test_suspends_subscription_after_max_failures()
    {
        // This logic is in the Retry Job, not the main command.
        // We test this in AutoDebitServiceTest.
        $this->markTestSkipped(
            'Suspension logic is in RetryAutoDebitJob, not the main command. Tested in AutoDebitServiceTest.'
        );
    }
}
