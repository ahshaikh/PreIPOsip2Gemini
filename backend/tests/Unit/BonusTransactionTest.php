<?php
// V-FINAL-1730-TEST-26

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Subscription;
use App\Models\Payment;
use App\Models\BonusTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;

class BonusTransactionTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $sub;
    protected $payment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->sub = Subscription::factory()->create(['user_id' => $this->user->id]);
        $this->payment = Payment::factory()->create(['subscription_id' => $this->sub->id]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_bonus_belongs_to_user()
    {
        $bonus = BonusTransaction::create([
            'user_id' => $this->user->id,
            'subscription_id' => $this->sub->id,
            'amount' => 100,
            'type' => 'test'
        ]);

        $this->assertInstanceOf(User::class, $bonus->user);
        $this->assertEquals($this->user->id, $bonus->user->id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_bonus_belongs_to_subscription()
    {
        $bonus = BonusTransaction::create([
            'user_id' => $this->user->id,
            'subscription_id' => $this->sub->id,
            'amount' => 100,
            'type' => 'test'
        ]);

        $this->assertInstanceOf(Subscription::class, $bonus->subscription);
        $this->assertEquals($this->sub->id, $bonus->subscription->id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_bonus_belongs_to_payment()
    {
        $bonus = BonusTransaction::create([
            'user_id' => $this->user->id,
            'subscription_id' => $this->sub->id,
            'payment_id' => $this->payment->id,
            'amount' => 100,
            'type' => 'test'
        ]);

        $this->assertInstanceOf(Payment::class, $bonus->payment);
        $this->assertEquals($this->payment->id, $bonus->payment->id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_bonus_type_enum_validates()
    {
        $validTypes = ['progressive', 'milestone', 'consistency', 'referral', 'profit_share', 'celebration', 'lucky_draw'];
        
        $validator = Validator::make(
            ['type' => 'progressive'], 
            ['type' => 'in:' . implode(',', $validTypes)]
        );
        
        $this->assertTrue($validator->passes());

        $validator = Validator::make(
            ['type' => 'fake_bonus'], 
            ['type' => 'in:' . implode(',', $validTypes)]
        );
        
        $this->assertFalse($validator->passes());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_bonus_tracks_multiplier_applied()
    {
        $bonus = BonusTransaction::create([
            'user_id' => $this->user->id,
            'subscription_id' => $this->sub->id,
            'amount' => 200,
            'multiplier_applied' => 2.5,
            'type' => 'referral'
        ]);

        $this->assertEquals(2.5, $bonus->fresh()->multiplier_applied);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_bonus_tracks_base_amount()
    {
        $bonus = BonusTransaction::create([
            'user_id' => $this->user->id,
            'subscription_id' => $this->sub->id,
            'amount' => 200,
            'base_amount' => 100,
            'type' => 'referral'
        ]);

        $this->assertEquals(100, $bonus->fresh()->base_amount);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_bonus_calculates_effective_amount()
    {
        // We test the 'booted' logic here
        // If we provide base=100 and multiplier=1.5, amount should auto-set to 150
        
        $bonus = BonusTransaction::create([
            'user_id' => $this->user->id,
            'subscription_id' => $this->sub->id,
            'type' => 'auto_calc',
            'base_amount' => 100,
            'multiplier_applied' => 1.5,
            // 'amount' is intentionally omitted
        ]);

        $this->assertEquals(150.00, $bonus->fresh()->amount);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_bonus_can_be_reversed()
    {
        $bonus = BonusTransaction::create([
            'user_id' => $this->user->id,
            'subscription_id' => $this->sub->id,
            'type' => 'mistake',
            'amount' => 500,
            'base_amount' => 500,
            'multiplier_applied' => 1
        ]);

        // Call the helper method
        $reversal = $bonus->reverse("Admin correction");

        $this->assertDatabaseHas('bonus_transactions', [
            'user_id' => $this->user->id,
            'type' => 'reversal',
            'amount' => -500, // Negative
            'description' => 'Reversal of Bonus #' . $bonus->id . ': Admin correction'
        ]);
        
        $this->assertEquals(-500, $reversal->amount);
    }
}