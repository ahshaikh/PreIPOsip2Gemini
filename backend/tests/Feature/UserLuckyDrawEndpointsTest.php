<?php
// V-FINAL-1730-TEST-90 (Created)

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\LuckyDraw;
use App\Models\LuckyDrawEntry;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\Plan;

class UserLuckyDrawEndpointsTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $draw;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        
        $this->user = User::factory()->create();
        $this->user->assignRole('user');

        // Create an "open" draw for the user to see
        $this->draw = LuckyDraw::factory()->create([
            'name' => 'Current Monthly Draw',
            'status' => 'open',
            'prize_structure' => [['rank' => 1, 'count' => 1, 'amount' => 1000]]
        ]);
    }

    /** @test */
    public function testUserCanViewCurrentDraw()
    {
        // Act
        $response = $this->actingAs($this->user)->getJson('/api/v1/user/lucky-draws');

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('active_draw.name', 'Current Monthly Draw');
    }

    /** @test */
    public function testUserCanViewOwnEntries()
    {
        // Arrange
        // Create an entry for the user
        $sub = Subscription::factory()->create(['user_id' => $this->user->id]);
        $payment = Payment::factory()->create(['subscription_id' => $sub->id]);
        
        LuckyDrawEntry::create([
            'user_id' => $this->user->id,
            'lucky_draw_id' => $this->draw->id,
            'payment_id' => $payment->id,
            'base_entries' => 5,
            'bonus_entries' => 3
        ]);

        // Create an entry for another user (which should not be seen)
        $otherUser = User::factory()->create();
        LuckyDrawEntry::factory()->create([
            'user_id' => $otherUser->id,
            'lucky_draw_id' => $this->draw->id
        ]);

        // Act
        $response = $this->actingAs($this->user)->getJson('/api/v1/user/lucky-draws');

        // Assert
        $response->assertStatus(200);
        
        // Check that the user_entry object is correct
        $response->assertJsonPath('user_entry.user_id', $this->user->id);
        $response->assertJsonPath('user_entry.base_entries', 5);
        $response->assertJsonPath('user_entry.bonus_entries', 3);
        
        // Check the 'total_entries' accessor
        $response->assertJsonPath('user_entry.total_entries', 8);
    }
}