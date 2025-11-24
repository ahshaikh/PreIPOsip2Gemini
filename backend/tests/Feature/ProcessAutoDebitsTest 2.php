<?php
// V-FINAL-1730-TEST-83 (Created)

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Services\AutoDebitService;
use App\Models\User;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Payment;
use App\Jobs\RetryAutoDebitJob;
use App\Jobs\SendPaymentReminderJob;
use Illuminate\Support\Facades\Queue;
use Mockery\MockInterface;

class ProcessAutoDebitsTest extends TestCase
{
    use RefreshDatabase;

    protected $serviceMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this->seed(\Database\Seeders\SettingsSeeder::class);

        // Mock the AutoDebitService in the service container
        // This ensures our command receives this FAKE service.
        $this->serviceMock = $this->mock(AutoDebitService::class);
    }

    /**
     * @test
     * Test: test_identifies_subscriptions_due_for_payment
     * Test: test_initiates_payment_for_each_subscription
     */
    public function test_initiates_payment_for_each_due_subscription()
    {
        // 1. Arrange
        $user = User::factory()->create();
        $dueSubs = Subscription::factory()->count(3)->create(['user_id' => $user->id]);

        // 2. Mock Expectations
        // Expect the service's "getDueSubscriptions" method to be called once...
        $this->serviceMock->shouldReceive('getDueSubscriptions')
            ->once()
            ->andReturn($dueSubs); // ...and return our 3 fake subscriptions.
        
        // Expect "attemptAutoDebit" to be called 3 times (once for each sub)
        $this->serviceMock->shouldReceive('attemptAutoDebit')
            ->times(3)
            ->andReturn(true); // Simulate success
            
        // Expect "sendReminders" to be called
        $this->serviceMock->shouldReceive('sendReminders')->once()->andReturn(0);

        // 3. Act
        // Run the Artisan command
        $this->artisan('app:process-auto-debits');

        // 4. Assert (Assertions are handled by the mock expectations)
    }

    /** @test */
    public function test_retries_failed_payments()
    {
        // This test ensures that if the service reports a failure,
        // the command *does not* crash and completes its run.
        // The *service* is responsible for queuing the retry job.
        
        $user = User::factory()->create();
        $sub = Subscription::factory()->create(['user_id' => $user->id]);

        $this->serviceMock->shouldReceive('getDueSubscriptions')->once()->andReturn(collect([$sub]));
        $this->serviceMock->shouldReceive('sendReminders')->once();
        
        // Force 'attemptAutoDebit' to return FALSE (payment failed)
        $this->serviceMock->shouldReceive('attemptAutoDebit')
            ->once()
            ->with($sub)
            ->andReturn(false); // FAILED

        // Act & Assert
        $this->artisan('app:process-auto-debits')
             ->expectsOutput('Starting auto-debit process...')
             ->expectsOutput('Found 1 subscriptions due.')
             ->expectsOutput('Sent 0 payment reminders.')
             ->assertExitCode(0); // Command finished successfully
    }

    /** @test */
    public function test_sends_reminder_before_due_date()
    {
        // This test verifies the command calls the reminder function.
        $this->serviceMock->shouldReceive('getDueSubscriptions')->once()->andReturn(collect([]));
        
        // Expect 'sendReminders' to be called and simulate it found 3 users
        $this->serviceMock->shouldReceive('sendReminders')->once()->andReturn(3);

        // Run the command and expect its output
        $this->artisan('app:process-auto-debits')
             ->expectsOutput('Starting auto-debit process...')
             ->expectsOutput('Found 0 subscriptions due.')
             ->expectsOutput('Sent 3 payment reminders.')
             ->assertExitCode(0);
    }

    /** @test */
    public function test_suspends_subscription_after_max_failures()
    {
        // This logic is in the Retry Job (RetryAutoDebitJob),
        // not the main command (ProcessAutoDebits).
        // This test is correctly placed in AutoDebitServiceTest.
        $this->markTestSkipped(
            'Suspension logic is in RetryAutoDebitJob, not the main command. Tested in AutoDebitServiceTest.'
        );
    }
}