<?php
// V-FINAL-1730-TEST-10

namespace Tests\Feature;

use Tests\FeatureTestCase;
use App\Models\User;
use App\Models\LuckyDraw;
use App\Models\LuckyDrawEntry;
use App\Models\Wallet;
use App\Models\Payment;
use App\Models\Subscription;

class LuckyDrawTest extends FeatureTestCase
{
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

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_execute_draw()
    {
        // Create 5 entrants with proper FK relationships
        $users = User::factory()->count(5)->create();
        foreach ($users as $u) {
            Wallet::create(['user_id' => $u->id, 'balance_paise' => 0, 'locked_balance_paise' => 0]);

            // V-WAVE1-FIX: Create proper subscription and payment FKs
            $subscription = Subscription::factory()->create(['user_id' => $u->id]);
            $payment = Payment::factory()->create([
                'user_id' => $u->id,
                'subscription_id' => $subscription->id,
            ]);

            LuckyDrawEntry::create([
                'user_id' => $u->id,
                'lucky_draw_id' => $this->draw->id,
                'payment_id' => $payment->id,
                'base_entries' => 1,
                'bonus_entries' => 0,
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
