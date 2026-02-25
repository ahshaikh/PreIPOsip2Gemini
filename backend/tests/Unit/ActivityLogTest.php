<?php
// V-FINAL-1730-TEST-59 (Created)

namespace Tests\Unit;

use Tests\UnitTestCase;
use App\Models\User;
use App\Models\ActivityLog;
use Carbon\Carbon;

class ActivityLogTest extends UnitTestCase
{
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_log_belongs_to_user()
    {
        $log = ActivityLog::create([
            'user_id' => $this->user->id,
            'action' => 'test'
        ]);

        $this->assertInstanceOf(User::class, $log->user);
        $this->assertEquals($this->user->id, $log->user->id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_log_tracks_action_type()
    {
        $log = ActivityLog::create([
            'user_id' => $this->user->id,
            'action' => 'login_success'
        ]);

        $this->assertEquals('login_success', $log->action);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_log_tracks_ip_address()
    {
        $log = ActivityLog::create([
            'user_id' => $this->user->id,
            'action' => 'test',
            'ip_address' => '192.168.1.1'
        ]);

        $this->assertEquals('192.168.1.1', $log->ip_address);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_log_tracks_user_agent()
    {
        $agent = 'Mozilla/5.0...';
        $log = ActivityLog::create([
            'user_id' => $this->user->id,
            'action' => 'test',
            'user_agent' => $agent
        ]);

        $this->assertEquals($agent, $log->user_agent);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_log_stores_old_and_new_values()
    {
        $old = ['status' => 'pending'];
        $new = ['status' => 'approved', 'name' => 'Test'];

        $log = ActivityLog::create([
            'user_id' => $this->user->id,
            'action' => 'kyc_approved',
            'old_values' => $old,
            'new_values' => $new
        ]);

        $retrieved = $log->fresh();

        $this->assertIsArray($retrieved->old_values);
        $this->assertEquals('pending', $retrieved->old_values['status']);

        $this->assertIsArray($retrieved->new_values);
        $this->assertEquals('approved', $retrieved->new_values['status']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_log_filters_by_date_range()
    {
        // 1. Log from 5 days ago
        ActivityLog::factory()->create([
            'created_at' => now()->subDays(5)
        ]);

        // 2. Log from today
        ActivityLog::factory()->create([
            'created_at' => now()
        ]);

        // 3. Log from 10 days ago
        ActivityLog::factory()->create([
            'created_at' => now()->subDays(10)
        ]);

        // Search for logs in the last 7 days
        $logs = ActivityLog::dateRange(
            now()->subDays(7)->toDateTimeString(),
            now()->addDay()->toDateTimeString()
        )->get();

        $this->assertCount(2, $logs); // Should find the 5-day and today's log
    }
}
