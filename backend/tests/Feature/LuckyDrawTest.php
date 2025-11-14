<?php
// V-FINAL-1730-TEST-10

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\LuckyDraw;
use App\Models\LuckyDrawEntry;
use App\Models\Wallet;

class LuckyDrawTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $draw;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->draw = LuckyDraw::create([
            'name' => 'Test Draw',
            'draw_date' => now(),
            'prize_structure' => [
                ['rank' => 1, 'count' => 1, 'amount' => 1000]
            ],
            'status' => 'open'
        ]);
    }

    /** @test */
    public function admin_can_execute_draw()
    {
        // Create 5 entrants
        $users = User::factory()->count(5)->create();
        foreach ($users as $u) {
            Wallet::create(['user_id' => $u->id, 'balance' => 0]);
            LuckyDrawEntry::create([
                'user_id' => $u->id,
                'lucky_draw_id' => $this->draw->id,
                'payment_id' => 1, // Stub
                'entry_code' => 'ABC' . $u->id
            ]);
        }

        $response = $this->actingAs($this->admin)
                         ->postJson("/api/v1/admin/lucky-draws/{$this->draw->id}/execute");

        $response->assertStatus(200);
        
        // Check 1 winner selected
        $this->assertDatabaseHas('lucky_draw_entries', ['is_winner' => true]);
        $this->assertEquals(1, LuckyDrawEntry::where('is_winner', true)->count());
        
        // Check status
        $this->assertEquals('completed', $this->draw->fresh()->status);
    }
}