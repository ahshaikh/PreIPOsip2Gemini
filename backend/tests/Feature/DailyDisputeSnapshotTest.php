<?php

// V-DISPUTE-RISK-2026-TEST-005: Daily Dispute Snapshot Feature Tests

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\DailyDisputeSnapshot;
use App\Models\Dispute;
use App\Models\Payment;
use App\Models\User;
use App\Models\Plan;
use App\Models\Company;
use Illuminate\Support\Facades\Artisan;

class DailyDisputeSnapshotTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    // ==================== SNAPSHOT UNIQUENESS TESTS ====================

    #[\PHPUnit\Framework\Attributes\Test]
    public function snapshot_enforces_unique_date_plan_constraint()
    {
        $snapshotDate = now()->subDay()->toDateString();

        // Create first snapshot for platform-wide (null plan_id)
        DailyDisputeSnapshot::create([
            'snapshot_date' => $snapshotDate,
            'plan_id' => null,
            'total_disputes' => 10,
        ]);

        // Attempting to create duplicate should fail
        $this->expectException(\Illuminate\Database\QueryException::class);

        DailyDisputeSnapshot::create([
            'snapshot_date' => $snapshotDate,
            'plan_id' => null,
            'total_disputes' => 20,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function snapshot_allows_same_date_different_plans()
    {
        $snapshotDate = now()->subDay()->toDateString();
        $plan1 = Plan::factory()->create();
        $plan2 = Plan::factory()->create();

        // Create snapshots for different plans on same date
        $snapshot1 = DailyDisputeSnapshot::create([
            'snapshot_date' => $snapshotDate,
            'plan_id' => null,
            'total_disputes' => 10,
        ]);

        $snapshot2 = DailyDisputeSnapshot::create([
            'snapshot_date' => $snapshotDate,
            'plan_id' => $plan1->id,
            'total_disputes' => 5,
        ]);

        $snapshot3 = DailyDisputeSnapshot::create([
            'snapshot_date' => $snapshotDate,
            'plan_id' => $plan2->id,
            'total_disputes' => 3,
        ]);

        $this->assertEquals(3, DailyDisputeSnapshot::where('snapshot_date', $snapshotDate)->count());
    }

    // ==================== AGGREGATION COMMAND TESTS ====================

    #[\PHPUnit\Framework\Attributes\Test]
    public function aggregate_command_creates_platform_snapshot()
    {
        $snapshotDate = now()->subDay()->toDateString();

        // Create some test data
        $this->createDispute('open', 'low');
        $this->createDispute('resolved', 'high');

        // Run aggregation command
        Artisan::call('dispute:aggregate-snapshots', [
            '--date' => $snapshotDate,
        ]);

        // Verify platform-wide snapshot was created
        $snapshot = DailyDisputeSnapshot::where('snapshot_date', $snapshotDate)
            ->whereNull('plan_id')
            ->first();

        $this->assertNotNull($snapshot);
        $this->assertEquals(2, $snapshot->total_disputes);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function aggregate_command_creates_per_plan_snapshots()
    {
        $snapshotDate = now()->subDay()->toDateString();
        $plan = Plan::factory()->create(['is_active' => true]);

        // Run aggregation command
        Artisan::call('dispute:aggregate-snapshots', [
            '--date' => $snapshotDate,
        ]);

        // Verify plan-specific snapshot was created
        $planSnapshot = DailyDisputeSnapshot::where('snapshot_date', $snapshotDate)
            ->where('plan_id', $plan->id)
            ->first();

        $this->assertNotNull($planSnapshot);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function aggregate_command_skips_existing_without_force()
    {
        $snapshotDate = now()->subDay()->toDateString();

        // Create existing snapshot
        DailyDisputeSnapshot::create([
            'snapshot_date' => $snapshotDate,
            'plan_id' => null,
            'total_disputes' => 999,
        ]);

        // Run without force
        Artisan::call('dispute:aggregate-snapshots', [
            '--date' => $snapshotDate,
        ]);

        // Snapshot should not be updated
        $snapshot = DailyDisputeSnapshot::where('snapshot_date', $snapshotDate)
            ->whereNull('plan_id')
            ->first();

        $this->assertEquals(999, $snapshot->total_disputes);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function aggregate_command_overwrites_with_force()
    {
        $snapshotDate = now()->subDay()->toDateString();

        // Create existing snapshot
        DailyDisputeSnapshot::create([
            'snapshot_date' => $snapshotDate,
            'plan_id' => null,
            'total_disputes' => 999,
        ]);

        // Create actual dispute
        $this->createDispute('open', 'low');

        // Run with force
        Artisan::call('dispute:aggregate-snapshots', [
            '--date' => $snapshotDate,
            '--force' => true,
        ]);

        // Snapshot should be updated
        $snapshot = DailyDisputeSnapshot::where('snapshot_date', $snapshotDate)
            ->whereNull('plan_id')
            ->first();

        $this->assertEquals(1, $snapshot->total_disputes);
    }

    // ==================== SNAPSHOT DATA ACCURACY TESTS ====================

    #[\PHPUnit\Framework\Attributes\Test]
    public function snapshot_records_correct_severity_counts()
    {
        $snapshotDate = now()->subDay()->toDateString();

        // Create disputes with various severities
        $this->createDispute('open', 'low');
        $this->createDispute('open', 'low');
        $this->createDispute('open', 'medium');
        $this->createDispute('open', 'high');
        $this->createDispute('open', 'critical');
        $this->createDispute('open', 'critical');

        Artisan::call('dispute:aggregate-snapshots', [
            '--date' => $snapshotDate,
        ]);

        $snapshot = DailyDisputeSnapshot::where('snapshot_date', $snapshotDate)
            ->whereNull('plan_id')
            ->first();

        $this->assertEquals(2, $snapshot->low_severity_count);
        $this->assertEquals(1, $snapshot->medium_severity_count);
        $this->assertEquals(1, $snapshot->high_severity_count);
        $this->assertEquals(2, $snapshot->critical_severity_count);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function snapshot_records_correct_status_counts()
    {
        $snapshotDate = now()->subDay()->toDateString();

        $this->createDispute('open', 'low');
        $this->createDispute('open', 'low');
        $this->createDispute('under_investigation', 'medium');
        $this->createDispute('resolved', 'low');
        $this->createDispute('escalated', 'high');

        Artisan::call('dispute:aggregate-snapshots', [
            '--date' => $snapshotDate,
        ]);

        $snapshot = DailyDisputeSnapshot::where('snapshot_date', $snapshotDate)
            ->whereNull('plan_id')
            ->first();

        $this->assertEquals(2, $snapshot->open_disputes);
        $this->assertEquals(1, $snapshot->under_investigation_disputes);
        $this->assertEquals(1, $snapshot->resolved_disputes);
        $this->assertEquals(1, $snapshot->escalated_disputes);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function snapshot_records_chargeback_metrics()
    {
        $snapshotDate = now()->subDay()->toDateString();
        $user = User::factory()->create();

        // Create chargebacks
        Payment::factory()->create([
            'user_id' => $user->id,
            'status' => Payment::STATUS_CHARGEBACK_CONFIRMED,
            'amount_paise' => 100000,
            'chargeback_amount_paise' => 100000,
            'chargeback_confirmed_at' => now()->subDay(),
        ]);

        Payment::factory()->create([
            'user_id' => $user->id,
            'status' => Payment::STATUS_CHARGEBACK_CONFIRMED,
            'amount_paise' => 50000,
            'chargeback_amount_paise' => 50000,
            'chargeback_confirmed_at' => now()->subDay(),
        ]);

        Artisan::call('dispute:aggregate-snapshots', [
            '--date' => $snapshotDate,
        ]);

        $snapshot = DailyDisputeSnapshot::where('snapshot_date', $snapshotDate)
            ->whereNull('plan_id')
            ->first();

        $this->assertEquals(2, $snapshot->confirmed_chargeback_count);
        $this->assertEquals(150000, $snapshot->confirmed_chargeback_amount_paise);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function snapshot_records_blocked_user_count()
    {
        $snapshotDate = now()->subDay()->toDateString();

        User::factory()->create(['is_blocked' => true]);
        User::factory()->create(['is_blocked' => true]);
        User::factory()->create(['is_blocked' => false]);

        Artisan::call('dispute:aggregate-snapshots', [
            '--date' => $snapshotDate,
        ]);

        $snapshot = DailyDisputeSnapshot::where('snapshot_date', $snapshotDate)
            ->whereNull('plan_id')
            ->first();

        $this->assertEquals(2, $snapshot->blocked_users_count);
    }

    // ==================== MODEL ACCESSOR TESTS ====================

    #[\PHPUnit\Framework\Attributes\Test]
    public function snapshot_model_calculates_rupee_amounts()
    {
        $snapshot = DailyDisputeSnapshot::create([
            'snapshot_date' => now()->subDay()->toDateString(),
            'plan_id' => null,
            'total_chargeback_amount_paise' => 150000,
            'confirmed_chargeback_amount_paise' => 100000,
        ]);

        $this->assertEquals(1500.00, $snapshot->total_chargeback_amount);
        $this->assertEquals(1000.00, $snapshot->confirmed_chargeback_amount);
    }

    // ==================== SCOPE TESTS ====================

    #[\PHPUnit\Framework\Attributes\Test]
    public function snapshot_date_range_scope_works()
    {
        DailyDisputeSnapshot::create([
            'snapshot_date' => now()->subDays(5)->toDateString(),
            'plan_id' => null,
        ]);
        DailyDisputeSnapshot::create([
            'snapshot_date' => now()->subDays(3)->toDateString(),
            'plan_id' => null,
        ]);
        DailyDisputeSnapshot::create([
            'snapshot_date' => now()->subDays(1)->toDateString(),
            'plan_id' => null,
        ]);

        $inRange = DailyDisputeSnapshot::dateRange(
            now()->subDays(4)->toDateString(),
            now()->subDays(2)->toDateString()
        )->count();

        $this->assertEquals(1, $inRange);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function snapshot_platform_wide_scope_works()
    {
        $plan = Plan::factory()->create();

        DailyDisputeSnapshot::create([
            'snapshot_date' => now()->subDay()->toDateString(),
            'plan_id' => null,
        ]);
        DailyDisputeSnapshot::create([
            'snapshot_date' => now()->subDay()->toDateString(),
            'plan_id' => $plan->id,
        ]);

        $platformWide = DailyDisputeSnapshot::platformWide()->count();

        $this->assertEquals(1, $platformWide);
    }

    // ==================== HELPER METHODS ====================

    protected function createDispute(string $status, string $severity): Dispute
    {
        // FIX: Create company fixture instead of assuming one exists
        $company = Company::factory()->create();

        return Dispute::create([
            'company_id' => $company->id,
            'user_id' => User::factory()->create()->id,
            'status' => $status,
            'severity' => $severity,
            'category' => 'other',
            'title' => 'Test Dispute',
            'description' => 'Test description',
            'opened_at' => now()->subDay(),
            'created_at' => now()->subDay(),
        ]);
    }
}
