<?php
// V-FINAL-1730-TEST-42 (Created)

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\ProfitShare;
use App\Models\UserProfitShare;
use App\Models\BonusTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserProfitShareTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $profitSharePeriod;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        
        $this->profitSharePeriod = ProfitShare::factory()->create([
            'admin_id' => User::factory()->create()->id, // Needs an admin
        ]);
    }

    /** @test */
    public function test_user_share_belongs_to_user()
    {
        $share = UserProfitShare::create([
            'user_id' => $this->user->id,
            'profit_share_id' => $this->profitSharePeriod->id,
            'amount' => 100
        ]);

        $this->assertInstanceOf(User::class, $share->user);
        $this->assertEquals($this->user->id, $share->user->id);
    }

    /** @test */
    public function test_user_share_belongs_to_profit_share()
    {
        $share = UserProfitShare::create([
            'user_id' => $this->user->id,
            'profit_share_id' => $this->profitSharePeriod->id,
            'amount' => 100
        ]);

        $this->assertInstanceOf(ProfitShare::class, $share->profitSharePeriod);
        $this->assertEquals($this->profitSharePeriod->id, $share->profitSharePeriod->id);
    }

    /** @test */
    public function test_user_share_validates_amount_positive()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Profit share amount must be positive");

        UserProfitShare::create([
            'user_id' => $this->user->id,
            'profit_share_id' => $this->profitSharePeriod->id,
            'amount' => -50 // Invalid
        ]);
    }

    /** @test */
    public function test_user_share_tracks_credited_to_wallet()
    {
        $bonusTxn = BonusTransaction::factory()->create([
            'user_id' => $this->user->id,
            'amount' => 100,
            'type' => 'profit_share'
        ]);

        $share = UserProfitShare::create([
            'user_id' => $this->user->id,
            'profit_share_id' => $this->profitSharePeriod->id,
            'amount' => 100,
            'bonus_transaction_id' => $bonusTxn->id
        ]);

        $this->assertInstanceOf(BonusTransaction::class, $share->bonusTransaction);
        $this->assertEquals($bonusTxn->id, $share->bonusTransaction->id);
    }
}