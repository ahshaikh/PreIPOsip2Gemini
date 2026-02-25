<?php
// V-FINAL-1730-TEST-11

namespace Tests\Unit;

use Tests\UnitTestCase;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserKyc;
use App\Models\Wallet;
use App\Models\Subscription;
use App\Models\Referral;
use App\Models\Plan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;

class UserTest extends UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_user_can_be_created_with_valid_data()
    {
        $userData = [
            'username' => 'johndoe',
            'email' => 'john@example.com',
            'mobile' => '9876543210',
            'password' => 'Secret123!',
            'status' => 'active',
        ];

        $user = User::create($userData);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('johndoe', $user->username);
        $this->assertEquals('john@example.com', $user->email);
        $this->assertDatabaseHas('users', ['email' => 'john@example.com']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_user_password_is_hashed_on_creation()
    {
        $user = User::create([
            'username' => 'hashcheck',
            'email' => 'hash@example.com',
            'mobile' => '1111111111',
            'password' => 'PlainPassword',
        ]);

        $this->assertNotEquals('PlainPassword', $user->password);
        $this->assertTrue(Hash::check('PlainPassword', $user->password));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_user_email_must_be_unique()
    {
        User::factory()->create(['email' => 'duplicate@example.com']);

        $this->expectException(QueryException::class);

        User::factory()->create(['email' => 'duplicate@example.com']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_user_mobile_must_be_unique()
    {
        User::factory()->create(['mobile' => '9999999999']);

        $this->expectException(QueryException::class);

        User::factory()->create(['mobile' => '9999999999']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_user_referral_code_is_generated_automatically()
    {
        // We create a user WITHOUT providing a referral_code
        $user = User::create([
            'username' => 'refgen',
            'email' => 'ref@example.com',
            'mobile' => '8888888888',
            'password' => 'password',
        ]);

        $this->assertNotNull($user->referral_code);
        $this->assertEquals(10, strlen($user->referral_code)); // Assuming 10 chars
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_user_referral_code_is_unique()
    {
        $user1 = User::factory()->create(['referral_code' => 'UNIQUE01']);

        $this->expectException(QueryException::class);

        // Try to force a duplicate code
        User::factory()->create(['referral_code' => 'UNIQUE01']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_user_has_profile_relationship()
    {
        $user = User::factory()->create();
        $profile = UserProfile::create(['user_id' => $user->id, 'first_name' => 'Test']);

        $this->assertTrue($user->profile()->exists());
        $this->assertEquals('Test', $user->profile->first_name);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_user_has_wallet_relationship()
    {
        $user = User::factory()->create();
        $wallet = Wallet::create([
            'user_id' => $user->id,
            'balance_paise' => 10000, // ₹100 in paise
            'locked_balance_paise' => 0
        ]);

        $this->assertTrue($user->wallet()->exists());
        $this->assertEquals(100, $user->wallet->balance); // Virtual accessor returns rupees
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_user_has_kyc_relationship()
    {
        $user = User::factory()->create();
        UserKyc::create(['user_id' => $user->id, 'status' => 'pending']);

        $this->assertTrue($user->kyc()->exists());
        $this->assertEquals('pending', $user->kyc->status);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_user_has_subscriptions_relationship()
    {
        $user = User::factory()->create();
        $plan = Plan::factory()->create();
        
        Subscription::factory()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id
        ]);

        $this->assertTrue($user->subscriptions()->exists());
        $this->assertInstanceOf(Subscription::class, $user->subscriptions->first());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_user_has_referrals_relationship()
    {
        $referrer = User::factory()->create();
        $referee = User::factory()->create();

        Referral::create([
            'referrer_id' => $referrer->id,
            'referred_id' => $referee->id,
            'status' => 'pending'
        ]);

        // Test 'referrals' (users I referred)
        $this->assertTrue($referrer->referrals()->exists());
        $this->assertEquals($referee->id, $referrer->referrals->first()->referred_id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_user_soft_deletes_correctly()
    {
        $user = User::factory()->create();
        $userId = $user->id;

        $user->delete();

        // Should not be in default query
        $this->assertNull(User::find($userId));

        // Should be in trashed query
        $this->assertNotNull(User::withTrashed()->find($userId));
        $this->assertNotNull($user->deleted_at);
    }
}
