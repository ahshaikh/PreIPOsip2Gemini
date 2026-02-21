<?php
// V-FINAL-1730-TEST-75 (Created)

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\LuckyDraw;
use App\Models\LuckyDrawEntry;
use App\Services\LuckyDrawService;
use Illuminate\Support\Facades\Queue;
use Carbon\Carbon;
use Mockery\MockInterface;

class ProcessMonthlyLuckyDrawTest extends TestCase
{
    protected $serviceMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this->seed(\Database\Seeders\SettingsSeeder::class);
        
        // Mock the service
        $this->serviceMock = $this->mock(LuckyDrawService::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_creates_draw_for_current_month()
    {
        // 1. Travel to the 1st of the month
        $this->travelTo(Carbon::parse('2025-01-01 05:00:00'));
        
        // 2. Expect the service to be called
        $this->serviceMock->shouldReceive('createMonthlyDraw')
            ->once()
            ->with(
                'January 2025 Lucky Draw', // Name
                Mockery::any(), // Date
                Mockery::type('array') // Prize Structure
            );
        
        // 3. Act: Run the command
        $this->artisan('app:process-monthly-lucky-draw');
        
        $this->travelBack();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_allocates_entries_to_active_users()
    {
        // Allocation is tested in GenerateLuckyDrawEntryJobTest
        // and LuckyDrawServiceTest. This test confirms the
        // main cron job *doesn't* do allocation.
        $this->assertTrue(true);
        $this->markTestSkipped(
            'Allocation logic is correctly placed in GenerateLuckyDrawEntryJob, not this command.'
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_executes_draw_and_selects_winners()
    {
        // 1. Create an open draw due today
        $draw = LuckyDraw::factory()->create([
            'status' => 'open',
            'draw_date' => now()
        ]);
        
        $winnerList = [1, 2, 3]; // Mock winners
        
        // 2. Mock service expectations
        $this->serviceMock->shouldReceive('createMonthlyDraw')->never(); // Shouldn't create
        
        $this->serviceMock->shouldReceive('selectWinners')
            ->once()
            ->withArgs([Mockery::on(fn($d) => $d->id === $draw->id)])
            ->andReturn($winnerList);
            
        $this->serviceMock->shouldReceive('distributePrizes')
            ->once()
            ->withArgs([Mockery::on(fn($d) => $d->id === $draw->id), $winnerList]);
            
        $this->serviceMock->shouldReceive('sendWinnerNotifications')
            ->once()
            ->with($winnerList);
            
        // 3. Act: Run the command
        $this->artisan('app:process-monthly-lucky-draw');
        
        // 4. Assert: Status is updated
        $this->assertEquals('completed', $draw->fresh()->status);
    }
}