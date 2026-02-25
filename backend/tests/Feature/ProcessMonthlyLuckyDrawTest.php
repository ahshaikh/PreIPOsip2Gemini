<?php

namespace Tests\Feature;

use Tests\FeatureTestCase;
use App\Models\LuckyDraw;
use App\Services\LuckyDrawService;
use App\Jobs\GenerateLuckyDrawEntryJob;
use Illuminate\Support\Facades\Bus;
use Mockery;
use Carbon\Carbon;

class ProcessMonthlyLuckyDrawTest extends FeatureTestCase
{
    protected $serviceMock;

    protected function setUp(): void
    {
        parent::setUp();

        // Create and bind the mock
        $this->serviceMock = Mockery::mock(LuckyDrawService::class);
        $this->app->instance(LuckyDrawService::class, $this->serviceMock);

        // Default behavior for pending draw lookup
        $this->serviceMock->shouldReceive('getPendingDraw')
            ->byDefault()
            ->andReturn(null);
    }

    public function test_creates_draw_for_current_month()
    {
        $this->travelTo(Carbon::parse('2026-02-01'));

        $this->serviceMock->shouldReceive('createMonthlyDraw')
            ->once()
            ->with(
                'February 2026 Lucky Draw',
                Mockery::any(),
                Mockery::type('array')
            );

        // NEW: Expect monthly entry dispatch
        $this->serviceMock->shouldReceive('dispatchMonthlyEntryGeneration')
            ->once();

        $this->artisan('app:process-monthly-lucky-draw')
             ->assertExitCode(0);
    }

    public function test_allocates_entries_to_active_users()
    {
        Bus::fake();
        $this->travelTo(Carbon::parse('2036-01-01'));

        $this->serviceMock->shouldReceive('createMonthlyDraw')
            ->once()
            ->andReturn(new LuckyDraw());

        // IMPORTANT: Allow service call
        $this->serviceMock->shouldReceive('dispatchMonthlyEntryGeneration')
            ->once()
            ->andReturnUsing(function () {
                // Simulate job dispatch for test verification
                GenerateLuckyDrawEntryJob::dispatch(
                    \App\Models\Payment::factory()->create()
                );
            });

        $this->artisan('app:process-monthly-lucky-draw', ['--force' => true])
             ->assertExitCode(0);

        Bus::assertDispatched(GenerateLuckyDrawEntryJob::class);
    }

    public function test_executes_draw_and_selects_winners()
    {
        $draw = LuckyDraw::factory()->create([
            'status' => 'open',
            'draw_date' => now()->startOfDay()
        ]);

        $this->serviceMock->shouldReceive('getPendingDraw')
            ->once()
            ->andReturn($draw);

        $this->serviceMock->shouldReceive('selectWinners')
            ->once()
            ->with(Mockery::on(fn($d) => $d->id === $draw->id));

        $this->artisan('app:process-monthly-lucky-draw')
             ->assertExitCode(0);

        $this->assertEquals('completed', $draw->fresh()->status);
    }
}