<?php
// V-FINAL-1730-TEST-40 (Created)

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\LuckyDraw;
use App\Models\LuckyDrawEntry;
use App\Models\User;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\QueryException;

class LuckyDrawEntryTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $draw;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->draw = LuckyDraw::factory()->create(['prize_structure' => [['rank'=>1, 'count'=>1, 'amount'=>1]]]);
    }

    /** @test */
    public function test_entry_belongs_to_user()
    {
        $entry = LuckyDrawEntry::factory()->create([
            'user_id' => $this->user->id,
            'lucky_draw_id' => $this->draw->id,
        ]);

        $this->assertInstanceOf(User::class, $entry->user);
        $this->assertEquals($this->user->id, $entry->user->id);
    }

    /** @test */
    public function test_entry_belongs_to_lucky_draw()
    {
        $entry = LuckyDrawEntry::factory()->create([
            'user_id' => $this->user->id,
            'lucky_draw_id' => $this->draw->id,
        ]);

        $this->assertInstanceOf(LuckyDraw::class, $entry->luckyDraw);
        $this->assertEquals($this->draw->id, $entry->luckyDraw->id);
    }

    /** @test */
    public function test_entry_tracks_entries_count()
    {
        $entry = LuckyDrawEntry::factory()->create([
            'user_id' => $this->user->id,
            'lucky_draw_id' => $this->draw->id,
            'base_entries' => 5
        ]);

        $this->assertEquals(5, $entry->base_entries);
    }

    /** @test */
    public function test_entry_tracks_bonus_entries()
    {
        $entry = LuckyDrawEntry::factory()->create([
            'user_id' => $this->user->id,
            'lucky_draw_id' => $this->draw->id,
            'bonus_entries' => 3
        ]);

        $this->assertEquals(3, $entry->bonus_entries);
    }

    /** @test */
    public function test_entry_calculates_total_entries()
    {
        $entry = LuckyDrawEntry::factory()->create([
            'user_id' => $this->user->id,
            'lucky_draw_id' => $this->draw->id,
            'base_entries' => 5,
            'bonus_entries' => 3
        ]);
        
        // Uses the 'totalEntries' accessor
        $this->assertEquals(8, $entry->total_entries);
    }

    /** @test */
    public function test_entry_validates_entries_positive()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Base entries cannot be negative");

        LuckyDrawEntry::factory()->create([
            'user_id' => $this->user->id,
            'lucky_draw_id' => $this->draw->id,
            'base_entries' => -1
        ]);
    }
}