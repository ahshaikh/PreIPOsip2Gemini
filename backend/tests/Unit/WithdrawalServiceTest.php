<?php
// V-FINAL-1730-TEST-38

namespace Tests\Unit;

use Tests\UnitTestCase;
use App\Services\WithdrawalService;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Withdrawal;
use App\Models\Setting;
use App\Events\WithdrawalApproved;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\DB;

class WithdrawalServiceTest extends UnitTestCase
{
    protected $service;
    protected $user;
    protected $wallet;
    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(WithdrawalService::class);
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->user = User::factory()->create();
        $this->user->kyc->update(['status' => 'verified']);
        
        // Update existing wallet instead of creating new one
        $this->user->wallet->update([
            'balance_paise' => 500000, // ₹5000 in paise
            'locked_balance_paise' => 0
        ]);
        $this->wallet = $this->user->wallet->fresh();

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        // Set min withdrawal
        \App\Models\Setting::updateOrCreate(['key' => 'min_withdrawal_amount'], ['value' => '1000', 'type' => 'number']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_request_withdrawal_validates_kyc_approved()
    {
        $this->user->kyc->update(['status' => 'pending']);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("KYC must be verified");

        $this->service->requestWithdrawal($this->user, 1000, []);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_request_withdrawal_validates_minimum_amount()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Minimum withdrawal amount is ₹1000");

        $this->service->requestWithdrawal($this->user, 500, []); // 500 < 1000
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_approve_withdrawal_initiates_bank_transfer_event()
    {
        Event::fake(); // Tell Laravel to intercept events

        $withdrawal = $this->service->requestWithdrawal($this->user, 1000, []);
        $this->assertEquals('pending', $withdrawal->fresh()->status); // Not auto-approved

        $this->service->approveWithdrawal($withdrawal, $this->admin);
        
        // Assert that our custom event was fired
        Event::assertDispatched(WithdrawalApproved::class, function ($event) use ($withdrawal) {
            return $event->withdrawal->id === $withdrawal->id;
        });
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_complete_withdrawal_updates_wallet_balance()
    {
        $withdrawal = $this->service->requestWithdrawal($this->user, 1000, []);

        // At this point, total balance is still 5000, but available is 4000, locked is 1000
        $this->assertEquals(5000, $this->wallet->fresh()->balance);
        $this->assertEquals(4000, $this->wallet->fresh()->available_balance);
        $this->assertEquals(1000, $this->wallet->fresh()->locked_balance);

        // Manually approve (to skip event)
        $withdrawal->update(['status' => 'approved']);

        $this->service->completeWithdrawal($withdrawal, $this->admin, 'UTR123');

        // Balances after completion: total balance is now 4000, locked is 0
        $this->assertEquals(4000, $this->wallet->fresh()->balance);
        $this->assertEquals(0, $this->wallet->fresh()->locked_balance);
    }
}
