<?php
// V-FINAL-1730-TEST-90 (Created)

namespace Tests\Feature;

use Tests\FeatureTestCase;
use App\Models\User;
use App\Models\LuckyDraw;
use App\Models\LuckyDrawEntry;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\Plan;

class UserLuckyDrawEndpointsTest extends FeatureTestCase
{
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

    #[\PHPUnit\Framework\Attributes\Test]
    public function testUserCanViewCurrentDraw()
    {
        // Act
        $response = $this->actingAs($this->user)->getJson('/api/v1/user/lucky-draws');

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('active_draw.name', 'Current Monthly Draw');
    }

    #[\PHPUnit\Framework\Attributes\Test]
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

        // Check that the active_draw contains user's entries
        // The API returns my_entries as an array and total_tickets as a count
        $response->assertJsonPath('active_draw.total_tickets', 8); // 5 base + 3 bonus
        $this->assertCount(8, $response->json('active_draw.my_entries'));

        // Verify entries contain expected structure
        $myEntries = $response->json('active_draw.my_entries');
        $baseEntries = collect($myEntries)->where('type', 'base')->count();
        $bonusEntries = collect($myEntries)->where('type', 'bonus')->count();
        $this->assertEquals(5, $baseEntries);
        $this->assertEquals(3, $bonusEntries);
    }
}
