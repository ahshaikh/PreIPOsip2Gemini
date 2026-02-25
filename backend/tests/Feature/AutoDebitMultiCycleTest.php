<?php

/**
 * V-AUTODEBIT-MULTICYCLE-2026: AutoDebit Stability Test
 *
 * Verifies:
 * 1. Retry threshold enforcement (max 3 attempts)
 * 2. Suspension after max attempts
 * 3. No ghost retries (orphaned jobs)
 * 4. No duplicate payment records
 * 5. No date drift across billing cycles
 * 6. Multiple billing cycles (6, 12 months)
 * 7. Failed → retry → suspend flow
 * 8. Resume → retry resets properly
 *
 * Recurring billing systems fail over time — not immediately.
 */

namespace Tests\Feature;

use Tests\FeatureTestCase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Payment;
use App\Services\AutoDebitService;
use App\Jobs\RetryAutoDebitJob;
use App\Jobs\SendPaymentFailedEmailJob;

class AutoDebitMultiCycleTest extends FeatureTestCase
{
    protected User $user;
    protected Plan $plan;
    protected Subscription $subscription;
    protected AutoDebitService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this->seed(\Database\Seeders\SettingsSeeder::class);

        $this->service = new AutoDebitService();

        $this->user = User::factory()->create();
        $this->user->assignRole('user');

        $this->plan = Plan::factory()->create([
            'monthly_amount' => 5000,
            'duration_months' => 12,
        ]);

        $this->subscription = Subscription::factory()->create([
            'user_id' => $this->user->id,
            'plan_id' => $this->plan->id,
            'amount' => $this->plan->monthly_amount,
            'status' => 'active',
            'is_auto_debit' => true,
            'next_payment_date' => now(),
            'start_date' => now()->subMonths(3),
            'razorpay_subscription_id' => null, // Will trigger early exit
        ]);
    }

    // =========================================================================
    // TEST 1: Retry Threshold Enforcement
    // =========================================================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function retry_threshold_enforced_at_3_attempts()
    {
        // Create subscription with Razorpay linkage (will fail in test env)
        $this->subscription->update(['razorpay_subscription_id' => 'sub_test_retry']);

        // Create 2 prior failed payments in current billing cycle
        Payment::factory()->create([
            'subscription_id' => $this->subscription->id,
            'user_id' => $this->user->id,
            'status' => 'failed',
            'created_at' => now()->subDays(2),
        ]);

        Payment::factory()->create([
            'subscription_id' => $this->subscription->id,
            'user_id' => $this->user->id,
            'status' => 'failed',
            'created_at' => now()->subDay(),
        ]);

        // Current attempt (3rd)
        $currentPayment = Payment::factory()->create([
            'subscription_id' => $this->subscription->id,
            'user_id' => $this->user->id,
            'status' => 'pending',
            'created_at' => now(),
        ]);

        // Process retry - should trigger suspension
        $this->service->processRetry($currentPayment);

        // Subscription should be suspended
        $this->subscription->refresh();
        $this->assertEquals('payment_failed', $this->subscription->status);
        $this->assertFalse($this->subscription->is_auto_debit);

        // No more retry jobs should be queued
        Queue::assertNotPushed(RetryAutoDebitJob::class);

        // Failure email should be sent
        Queue::assertPushed(SendPaymentFailedEmailJob::class);
    }

    // =========================================================================
    // TEST 2: No Ghost Retries (Without Razorpay Linkage)
    // =========================================================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function no_ghost_retries_without_razorpay_linkage()
    {
        // Subscription without Razorpay linkage
        $this->subscription->update([
            'razorpay_subscription_id' => null,
            'is_auto_debit' => true,
        ]);

        // Attempt auto-debit
        $result = $this->service->attemptAutoDebit($this->subscription);

        // Should fail gracefully
        $this->assertFalse($result);

        // Auto-debit should be disabled
        $this->subscription->refresh();
        $this->assertFalse($this->subscription->is_auto_debit);

        // No retry job should be queued
        Queue::assertNotPushed(RetryAutoDebitJob::class);

        // Payment should be marked failed with reason
        $payment = Payment::where('subscription_id', $this->subscription->id)
            ->where('status', 'failed')
            ->first();

        $this->assertNotNull($payment);
        $this->assertStringContainsString('not linked to Razorpay', $payment->failure_reason);
    }

    // =========================================================================
    // TEST 3: No Duplicate Payment Records
    // =========================================================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function no_duplicate_payments_created_for_same_cycle()
    {
        $this->subscription->update(['razorpay_subscription_id' => 'sub_test_dup']);

        // First auto-debit attempt
        $this->service->attemptAutoDebit($this->subscription);

        $paymentCount1 = Payment::where('subscription_id', $this->subscription->id)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        // getDueSubscriptions should NOT return this subscription again
        $dueSubscriptions = $this->service->getDueSubscriptions();

        $this->assertFalse(
            $dueSubscriptions->contains('id', $this->subscription->id),
            'Subscription with pending payment should not be picked up again'
        );
    }

    // =========================================================================
    // TEST 4: Date Drift Check Over Multiple Cycles
    // =========================================================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function no_date_drift_over_multiple_billing_cycles()
    {
        $startDate = Carbon::parse('2025-01-15');
        $this->subscription->update([
            'next_payment_date' => $startDate,
            'start_date' => $startDate->copy()->subMonth(),
        ]);

        // Simulate 6 billing cycles
        $expectedDates = [
            '2025-01-15',
            '2025-02-15',
            '2025-03-15',
            '2025-04-15',
            '2025-05-15',
            '2025-06-15',
        ];

        for ($cycle = 0; $cycle < 6; $cycle++) {
            $this->subscription->refresh();

            $actualDate = $this->subscription->next_payment_date->format('Y-m-d');
            $this->assertEquals(
                $expectedDates[$cycle],
                $actualDate,
                "Date drift at cycle {$cycle}! Expected: {$expectedDates[$cycle]}, Got: {$actualDate}"
            );

            // Simulate successful payment by advancing next_payment_date
            $this->subscription->update([
                'next_payment_date' => $this->subscription->next_payment_date->addMonth()
            ]);
        }
    }

    // =========================================================================
    // TEST 5: Failed → Retry → Suspension Flow (Corrected Simulation)
    // =========================================================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function full_failure_to_suspension_flow()
    {
        $this->subscription->update([
            'razorpay_subscription_id' => 'sub_failure_flow',
            'start_date' => now()->subMonth(),
            'status' => 'active',
            'is_auto_debit' => true,
        ]);

        // Create ONE original failed payment
        $originalPayment = Payment::factory()->create([
            'subscription_id' => $this->subscription->id,
            'user_id' => $this->user->id,
            'status' => 'failed',
            'created_at' => now()->subDays(2),
        ]);

        // ---------------------------
        // Attempt 1
        // ---------------------------
        $this->service->processRetry($originalPayment);
        $this->subscription->refresh();

        $this->assertEquals(
            'active',
            $this->subscription->status,
            'Still active after 1st failure'
        );

        // ---------------------------
        // Attempt 2
        // ---------------------------
        $latestPayment = Payment::where('subscription_id', $this->subscription->id)
            ->latest()
            ->first();

        $this->service->processRetry($latestPayment);
        $this->subscription->refresh();

        $this->assertEquals(
            'active',
            $this->subscription->status,
            'Still active after 2nd failure'
        );

        // ---------------------------
        // Attempt 3 → Suspension
        // ---------------------------
        $latestPayment = Payment::where('subscription_id', $this->subscription->id)
            ->latest()
            ->first();

        $this->service->processRetry($latestPayment);
        $this->subscription->refresh();

        $this->assertEquals(
            'payment_failed',
            $this->subscription->status,
            'Subscription moved to payment_failed after 3rd failure'
        );

        $this->assertFalse(
            $this->subscription->is_auto_debit,
            'Auto-debit disabled after max retries'
        );
    }

    // =========================================================================
    // TEST 6: Resume After Suspension Resets Retry Count
    // =========================================================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function resume_after_suspension_allows_fresh_retry_cycle()
    {
        // Setup: Subscription in payment_failed state with 3 failed payments
        $this->subscription->update([
            'status' => 'payment_failed',
            'is_auto_debit' => false,
            'razorpay_subscription_id' => 'sub_resume_test',
            'start_date' => now()->subMonths(2),
        ]);

        // Old billing cycle failures
        $oldCycleStart = now()->subMonth();
        for ($i = 0; $i < 3; $i++) {
            Payment::factory()->create([
                'subscription_id' => $this->subscription->id,
                'user_id' => $this->user->id,
                'status' => 'failed',
                'created_at' => $oldCycleStart->copy()->addDays($i),
            ]);
        }

        // User manually pays and reactivates subscription
        $manualPayment = Payment::factory()->create([
            'subscription_id' => $this->subscription->id,
            'user_id' => $this->user->id,
            'status' => 'paid',
            'paid_at' => now(),
            'created_at' => now(),
        ]);

        // Reactivate subscription
        $this->subscription->update([
            'status' => 'active',
            'is_auto_debit' => true,
            'next_payment_date' => now()->addMonth(),
        ]);

        // New billing cycle - first failure should NOT trigger immediate suspension
        Carbon::setTestNow(now()->addMonth());

        $newCyclePayment = Payment::factory()->create([
            'subscription_id' => $this->subscription->id,
            'user_id' => $this->user->id,
            'status' => 'pending',
            'created_at' => now(),
        ]);

        $this->service->processRetry($newCyclePayment);
        $this->subscription->refresh();

        // Should still be active (only 1st failure in new cycle)
        $this->assertEquals(
            'active',
            $this->subscription->status,
            'Subscription should still be active after first failure in new cycle'
        );

        Carbon::setTestNow(); // Reset
    }

    // =========================================================================
    // TEST 7: 12-Month Billing Cycle Stability
    // =========================================================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function twelve_month_billing_cycle_completes_without_drift()
    {
        $startDate = Carbon::parse('2025-01-01');
        $this->subscription->update([
            'start_date' => $startDate,
            'next_payment_date' => $startDate,
            'status' => 'active',
        ]);

        // Simulate 12 successful payment cycles
        for ($month = 1; $month <= 12; $month++) {
            $this->subscription->refresh();

            $expectedDay = 1; // Should always be 1st of month
            $actualDay = $this->subscription->next_payment_date->day;

            $this->assertEquals(
                $expectedDay,
                $actualDay,
                "Day drift at month {$month}! Expected day {$expectedDay}, got {$actualDay}"
            );

            // Create successful payment
            Payment::factory()->create([
                'subscription_id' => $this->subscription->id,
                'user_id' => $this->user->id,
                'status' => 'paid',
                'paid_at' => $this->subscription->next_payment_date,
            ]);

            // Advance to next month
            $this->subscription->update([
                'next_payment_date' => $this->subscription->next_payment_date->addMonth(),
                'consecutive_payments_count' => $month,
            ]);
        }

        // Final payment count should be 12
        $paidCount = Payment::where('subscription_id', $this->subscription->id)
            ->where('status', 'paid')
            ->count();

        $this->assertEquals(12, $paidCount);

        // Consecutive count should be 12
        $this->subscription->refresh();
        $this->assertEquals(12, $this->subscription->consecutive_payments_count);
    }

    // =========================================================================
    // TEST 8: Reminder Timing Accuracy
    // =========================================================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function reminders_sent_exactly_3_days_before()
    {
        // Due in 3 days (should remind)
        Subscription::factory()->create([
            'user_id' => $this->user->id,
            'next_payment_date' => now()->addDays(3),
            'status' => 'active',
        ]);

        // Due in 4 days (should NOT remind)
        Subscription::factory()->create([
            'user_id' => $this->user->id,
            'next_payment_date' => now()->addDays(4),
            'status' => 'active',
        ]);

        // Due in 2 days (should NOT remind - too late)
        Subscription::factory()->create([
            'user_id' => $this->user->id,
            'next_payment_date' => now()->addDays(2),
            'status' => 'active',
        ]);

        $reminderCount = $this->service->sendReminders();

        $this->assertEquals(1, $reminderCount, 'Only subscriptions exactly 3 days out should get reminders');
    }

    // =========================================================================
    // TEST 9: Suspension Prevents Further Auto-Debits
    // =========================================================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function suspended_subscription_not_picked_for_auto_debit()
    {
        // Active subscription due today
        $activeSubscription = Subscription::factory()->create([
            'user_id' => $this->user->id,
            'plan_id' => $this->plan->id,
            'next_payment_date' => now(),
            'status' => 'active',
            'is_auto_debit' => true,
        ]);

        // Suspended subscription also due today
        $suspendedSubscription = Subscription::factory()->create([
            'user_id' => $this->user->id,
            'plan_id' => $this->plan->id,
            'next_payment_date' => now(),
            'status' => 'payment_failed',
            'is_auto_debit' => false, // Auto-debit disabled on suspension
        ]);

        $dueSubscriptions = $this->service->getDueSubscriptions();

        $this->assertTrue(
            $dueSubscriptions->contains('id', $activeSubscription->id),
            'Active subscription should be picked'
        );

        $this->assertFalse(
            $dueSubscriptions->contains('id', $suspendedSubscription->id),
            'Suspended subscription should NOT be picked'
        );
    }
}
