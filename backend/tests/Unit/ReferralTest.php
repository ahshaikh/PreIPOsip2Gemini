<?php
// V-FINAL-1730-TEST-45 (Created)

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Referral;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class ReferralTest extends TestCase
{
    protected $referrer;
    protected $referee;

    protected function setUp(): void
    {
        parent::setUp();
        $this->referrer = User::factory()->create();
        $this->referee = User::factory()->create();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_referral_belongs_to_referrer()
    {
        $referral = Referral::create([
            'referrer_id' => $this->referrer->id,
            'referred_id' => $this->referee->id
        ]);

        $this->assertInstanceOf(User::class, $referral->referrer);
        $this->assertEquals($this->referrer->id, $referral->referrer->id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_referral_belongs_to_referred_user()
    {
        $referral = Referral::create([
            'referrer_id' => $this->referrer->id,
            'referred_id' => $this->referee->id
        ]);

        $this->assertInstanceOf(User::class, $referral->referred);
        $this->assertEquals($this->referee->id, $referral->referred->id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_referral_status_enum_validates()
    {
        $validStatuses = ['pending', 'completed', 'inactive'];
        
        $validator = Validator::make(
            ['status' => 'pending'], 
            ['status' => 'in:' . implode(',', $validStatuses)]
        );
        $this->assertTrue($validator->passes());
        
        $validator = Validator::make(
            ['status' => 'expired'], // Invalid status
            ['status' => 'in:' . implode(',', $validStatuses)]
        );
        $this->assertFalse($validator->passes());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_referral_tracks_activation_date()
    {
        $referral = Referral::create([
            'referrer_id' => $this->referrer->id,
            'referred_id' => $this->referee->id,
            'status' => 'pending'
        ]);

        $this->assertNull($referral->completed_at);
        
        $this->travelTo(Carbon::parse('2025-01-01'));
        
        $referral->complete(); // Use the helper method
        
        $this->assertEquals('completed', $referral->status);
        $this->assertEquals('2025-01-01', $referral->completed_at->toDateString());
        
        $this->travelBack();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_referral_validates_referrer_not_self()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("A user cannot refer themselves");

        Referral::create([
            'referrer_id' => $this->referrer->id,
            'referred_id' => $this->referrer->id // Self-referral
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_referral_validates_unique_referral()
    {
        // First referral is OK
        Referral::create([
            'referrer_id' => $this->referrer->id,
            'referred_id' => $this->referee->id
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("This user has already been referred");

        // Try to refer the SAME user again
        $anotherReferrer = User::factory()->create();
        Referral::create([
            'referrer_id' => $anotherReferrer->id,
            'referred_id' => $this->referee->id
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_referral_scope_active_filters_correctly()
    {
        Referral::create([
            'referrer_id' => $this->referrer->id,
            'referred_id' => $this->referee->id,
            'status' => 'pending'
        ]);
        
        $user3 = User::factory()->create();
        Referral::create([
            'referrer_id' => $this->referrer->id,
            'referred_id' => $user3->id,
            'status' => 'completed'
        ]);

        $this->assertEquals(1, Referral::completed()->count());
        $this->assertEquals($user3->id, Referral::completed()->first()->referred_id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_referral_calculates_bonus_eligible()
    {
        $referral = Referral::create([
            'referrer_id' => $this->referrer->id,
            'referred_id' => $this->referee->id,
            'status' => 'pending'
        ]);
        
        // Not eligible when pending
        $this->assertFalse($referral->isBonusEligible());

        $referral->complete();
        
        // Eligible when completed
        $this->assertTrue($referral->isBonusEligible());
    }
}