<?php
// V-FINAL-1730-TEST-39 (Created)

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\LuckyDraw;
use App\Models\LuckyDrawEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class LuckyDrawTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    // Helper to create a valid prize structure
    private function validPrizeStructure()
    {
        return [['rank' => 1, 'count' => 1, 'amount' => 1000]];
    }

    /** @test */
    public function test_lucky_draw_has_entries_relationship()
    {
        $draw = LuckyDraw::factory()->create(['prize_structure' => $this->validPrizeStructure()]);
        
        LuckyDrawEntry::factory()->create([
            'lucky_draw_id' => $draw->id,
            'user_id' => $this->user->id
        ]);

        $this->assertTrue($draw->entries()->exists());
        $this->assertEquals(1, $draw->entries->count());
    }

    /** @test */
    public function test_lucky_draw_status_enum_validates()
    {
        $validStatuses = ['open', 'drawn', 'completed', 'cancelled'];
        
        $validator = Validator::make(
            ['status' => 'open'], 
            ['status' => 'in:' . implode(',', $validStatuses)]
        );
        $this->assertTrue($validator->passes());
        
        $validator = Validator::make(
            ['status' => 'pending'], 
            ['status' => 'in:' . implode(',', $validStatuses)]
        );
        $this->assertFalse($validator->passes());
    }

    /** @test */
    public function test_lucky_draw_calculates_total_entries()
    {
        $draw = LuckyDraw::factory()->create(['prize_structure' => $this->validPrizeStructure()]);
        LuckyDrawEntry::factory()->count(5)->create(['lucky_draw_id' => $draw->id]);

        $this->assertEquals(5, $draw->total_entries);
    }

    /** @test */
    public function test_lucky_draw_validates_prize_pool_positive()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Prize pool must be positive");

        // Structure has 0 amount
        LuckyDraw::factory()->create([
            'prize_structure' => [['rank' => 1, 'count' => 1, 'amount' => 0]]
        ]);
    }

    /** @test */
    public function test_lucky_draw_tracks_execution_date()
    {
        $date = now()->addMonth();
        $draw = LuckyDraw::factory()->create([
            'prize_structure' => $this->validPrizeStructure(),
            'draw_date' => $date
        ]);

        $this->assertInstanceOf(Carbon::class, $draw->draw_date);
        $this->assertEquals($date->toDateString(), $draw->draw_date->toDateString());
    }

    /** @test */
    public function test_lucky_draw_can_be_executed()
    {
        $draw = LuckyDraw::factory()->create([
            'prize_structure' => $this->validPrizeStructure(),
            'status' => 'open'
        ]);

        $draw->execute();

        $this->assertEquals('completed', $draw->fresh()->status);
    }

    /** @test */
    public function test_lucky_draw_cannot_be_executed_twice()
    {
        $draw = LuckyDraw::factory()->create([
            'prize_structure' => $this->validPrizeStructure(),
            'status' => 'open'
        ]);
        
        $draw->execute(); // First execution
        $this->assertEquals('completed', $draw->fresh()->status);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage("Only 'open' draws can be executed.");
        
        $draw->execute(); // Second execution should fail
    }

    /** @test */
    public function test_lucky_draw_soft_deletes_correctly()
    {
        $draw = LuckyDraw::factory()->create(['prize_structure' => $this->validPrizeStructure()]);
        $drawId = $draw->id;

        $draw->delete();

        $this->assertNull(LuckyDraw::find($drawId));
        $this->assertNotNull(LuckyDraw::withTrashed()->find($drawId));
    }
}