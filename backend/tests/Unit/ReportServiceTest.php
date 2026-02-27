<?php
// V-FINAL-1730-TEST-60 (Created)

namespace Tests\Unit;

use Tests\UnitTestCase;
use App\Services\ReportService;
use App\Models\User;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Payment;
use App\Models\BonusTransaction;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ReportServiceTest extends UnitTestCase
{
    use DatabaseTransactions;

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
        // Get initial values to account for seeded data
        $initial = $this->service->getFinancialSummary(now()->subDay(), now()->addDay());

        // 1. Revenue
        Payment::factory()->create(['status' => 'paid', 'amount_paise' => 500000, 'amount' => 5000.00, 'paid_at' => now()]); // ₹5000 in paise
        Payment::factory()->create(['status' => 'paid', 'amount_paise' => 500000, 'amount' => 5000.00, 'paid_at' => now()]); // ₹5000 in paise
        
        // 2. Expense
        BonusTransaction::factory()->create(['amount' => 1000]);
        BonusTransaction::factory()->create(['amount' => 1000]);

        $summary = $this->service->getFinancialSummary(now()->subDay(), now()->addDay());

        $this->assertEquals($initial['revenue'] + 10000, $summary['revenue']);
        $this->assertEquals($initial['expenses'] + 2000, $summary['expenses']);
        $this->assertEquals($initial['profit'] + 8000, $summary['profit']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_user_growth_report_calculates_correctly()
    {
        // Use a date range far in the future or past where no seeded data exists, 
        // or just calculate the delta.
        $start = now()->addYear();
        $end = now()->addYear()->addDays(5);

        User::factory()->count(3)->create(['created_at' => (clone $start)->addDay(1)]);
        User::factory()->count(2)->create(['created_at' => (clone $start)->addDay(2)]);

        $growth = $this->service->getUserGrowth($start, $end);
        
        $this->assertEquals(2, $growth->count()); 
        $this->assertEquals(3, $growth[0]['count']);
        $this->assertEquals(2, $growth[1]['count']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_user_retention_report_calculates_churn()
    {
        // Account for existing data
        $initial = $this->service->getRetentionMetrics(now()->subMonth(), now());
        $initialUsers = User::where('created_at', '<', now()->subMonth())->count();

        // 10 NEW users at start (created 2 months ago)
        User::factory()->count(10)->create(['created_at' => now()->subMonths(2)]);

        // 2 NEW users cancelled this month
        Subscription::factory()->count(2)->create([
            'status' => 'cancelled',
            'cancelled_at' => now()
        ]);

        $metrics = $this->service->getRetentionMetrics(now()->subMonth(), now());

        $expectedLost = $initial['users_lost'] + 2;
        $totalUsersAtStart = $initialUsers + 10;
        $expectedChurn = $totalUsersAtStart > 0 ? ($expectedLost / $totalUsersAtStart) * 100 : 0;

        $this->assertEquals($expectedLost, $metrics['users_lost']);
        $this->assertEquals($expectedChurn, $metrics['churn_rate']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_kyc_completion_report_calculates_percentage()
    {
        // Account for existing data
        $totalInitial = \App\Models\UserKyc::count();
        $verifiedInitial = \App\Models\UserKyc::where('status', 'verified')->count();

        User::factory()->count(3)->create()->each(fn($u) => $u->kyc->update(['status' => 'verified']));
        User::factory()->count(1)->create()->each(fn($u) => $u->kyc->update(['status' => 'pending']));

        $percentage = $this->service->getKycCompletion();

        $expectedPercentage = (($verifiedInitial + 3) / ($totalInitial + 4)) * 100;
        $this->assertEquals(round($expectedPercentage, 2), round($percentage, 2));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_tds_calculation_applies_10_percent()
    {
        $start = now()->subMinutes(5);
        $end = now()->addMinutes(5);

        // User A: Eligible for TDS
        $userA = User::factory()->create();
        BonusTransaction::factory()->create([
            'user_id' => $userA->id, 
            'amount' => 15000,
            'tds_deducted' => 1500,
            'created_at' => now()
        ]); 

        $report = $this->service->getTdsReport($start, $end);

        // Check if our specific record is in the report
        $record = $report->firstWhere('user', $userA->username);
        $this->assertNotNull($record, 'TDS record for User A should be found in the report');
        $this->assertEquals(15000, $record['gross_amount']);
        $this->assertEquals(1500, $record['tds_deducted']); 
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_tds_exemption_for_amounts_below_10k()
    {
        $userA = User::factory()->create();
        BonusTransaction::factory()->create([
            'user_id' => $userA->id, 
            'amount' => 9000,
            'created_at' => now()
        ]); 
        
        $report = $this->service->getTdsReport(now()->subMinutes(5), now()->addMinutes(5));
        
        $record = $report->firstWhere('user', $userA->username);
        $this->assertNull($record);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_aml_report_flags_suspicious_transactions()
    {
        // Use a high threshold or specific IDs
        $userC = User::factory()->create(['created_at' => now()->subDays(3)]);
        Payment::factory()->create(['user_id' => $userC->id, 'amount_paise' => 6000000, 'amount' => 60000.00, 'status' => 'paid', 'paid_at' => now()]); // ₹60000 in paise
        
        $report = $this->service->getAmlReport();

        $this->assertTrue($report->pluck('user_id')->contains($userC->id));
    }
}
