<?php
// V-FINAL-1730-TEST-60 (Created)

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\ReportService;
use App\Models\User;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Payment;
use App\Models\BonusTransaction;
use Carbon\Carbon;

class ReportServiceTest extends TestCase
{
    protected $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ReportService();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_financial_summary_calculates_profit()
    {
        // 1. Revenue
        Payment::factory()->create(['status' => 'paid', 'amount' => 5000]);
        Payment::factory()->create(['status' => 'paid', 'amount' => 5000]);
        
        // 2. Expense
        BonusTransaction::factory()->create(['amount' => 1000]);
        BonusTransaction::factory()->create(['amount' => 1000]);

        $summary = $this->service->getFinancialSummary(now()->subDay(), now()->addDay());

        $this->assertEquals(10000, $summary['revenue']);
        $this->assertEquals(2000, $summary['expenses']);
        $this->assertEquals(8000, $summary['profit']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_user_growth_report_calculates_correctly()
    {
        User::factory()->count(3)->create(['created_at' => now()->subDay(1)]);
        User::factory()->count(2)->create(['created_at' => now()]);

        $growth = $this->service->getUserGrowth(now()->subDays(5), now());
        
        $this->assertEquals(2, $growth->count()); // 2 groups (yesterday, today)
        $this->assertEquals(3, $growth[0]['count']);
        $this->assertEquals(2, $growth[1]['count']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_user_retention_report_calculates_churn()
    {
        // 10 users at start
        User::factory()->count(10)->create(['created_at' => now()->subMonths(2)]);

        // 2 users cancelled this month
        User::factory()->count(2)->create([
            'created_at' => now()->subMonths(2),
            'status' => 'cancelled',
            'updated_at' => now()
        ]);

        $metrics = $this->service->getRetentionMetrics(now()->subMonth(), now());

        $this->assertEquals(2, $metrics['users_lost']);
        $this->assertEquals(20, $metrics['churn_rate']); // 2 / 10
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_kyc_completion_report_calculates_percentage()
    {
        User::factory()->count(3)->create()->each(fn($u) => $u->kyc->update(['status' => 'verified']));
        User::factory()->count(1)->create()->each(fn($u) => $u->kyc->update(['status' => 'pending']));

        $percentage = $this->service->getKycCompletion();

        $this->assertEquals(75, $percentage); // 3 out of 4
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_tds_calculation_applies_10_percent()
    {
        // User A: Eligible for TDS
        $userA = User::factory()->create();
        BonusTransaction::factory()->create(['user_id' => $userA->id, 'amount' => 15000]); // > 10k

        $report = $this->service->getTdsReport(now()->subDay(), now()->addDay());

        $this->assertEquals(1, count($report));
        $this->assertEquals(15000, $report[0]['gross_amount']);
        $this->assertEquals(1500, $report[0]['tds_deducted']); // 10%
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_tds_exemption_for_amounts_below_10k()
    {
        // User A: Not eligible
        $userA = User::factory()->create();
        BonusTransaction::factory()->create(['user_id' => $userA->id, 'amount' => 9000]); // < 10k
        
        $report = $this->service->getTdsReport(now()->subDay(), now()->addDay());
        
        $this->assertEquals(0, count($report));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_aml_report_flags_suspicious_transactions()
    {
        // User A: New user, small payment (OK)
        $userA = User::factory()->create(['created_at' => now()->subDays(2)]);
        Payment::factory()->create(['user_id' => $userA->id, 'amount' => 1000, 'status' => 'paid']);
        
        // User B: Old user, large payment (OK)
        $userB = User::factory()->create(['created_at' => now()->subDays(10)]);
        Payment::factory()->create(['user_id' => $userB->id, 'amount' => 60000, 'status' => 'paid']);
        
        // User C: New user, large payment (FLAG)
        $userC = User::factory()->create(['created_at' => now()->subDays(3)]);
        Payment::factory()->create(['user_id' => $userC->id, 'amount' => 60000, 'status' => 'paid']);
        
        $report = $this->service->getAmlReport();

        $this->assertEquals(1, $report->count());
        $this->assertEquals($userC->id, $report->first()->user_id);
    }
}