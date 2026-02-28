<?php

namespace Tests\Feature;

use App\Enums\TransactionType;
use App\Models\LedgerAccount;
use App\Models\User;
use App\Models\Wallet;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FinancialIntegrityInvariantTest extends TestCase
{
    use RefreshDatabase;

    protected WalletService $walletService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\LedgerAccountSeeder::class);
        $this->walletService = app(WalletService::class);
    }

    /**
     * @test
     * [INVARIANT]: SUM(Wallet.balance_paise) == LedgerAccount(USER_WALLET_LIABILITY).balance_paise
     */
    public function test_balance_sheet_invariant_holds_after_complex_operations()
    {
        // 1. Setup Users
        $userA = User::factory()->create(['username' => 'user_a']);
        $userB = User::factory()->create(['username' => 'user_b']);

        // 2. Initial State: Both 0
        $this->assertInvariant();

        // 3. Simple Deposits
        $this->walletService->deposit($userA, 1000.00, TransactionType::DEPOSIT, 'Initial deposit A');
        $this->walletService->deposit($userB, 500.00, TransactionType::DEPOSIT, 'Initial deposit B');
        $this->assertInvariant();

        // 4. Bonus Grants (Net 100 to wallet)
        // Bonus grant in this system is two steps: 
        // a) recordBonusWithTds (Marketing -> Bonus Liability)
        // b) walletService->deposit(..., TransactionType::BONUS_CREDIT, ...) (Bonus Liability -> Wallet Liability)
        $this->walletService->deposit($userA, 100.00, TransactionType::BONUS_CREDIT, 'Referral Bonus');
        $this->assertInvariant();

        // 5. Withdrawals (Completed)
        $this->walletService->withdraw($userA, 200.00, TransactionType::WITHDRAWAL, 'Withdrawal A');
        $this->assertInvariant();

        // 6. Investments (Cash + Bonus)
        // WalletService::withdraw handles the ledger routing for INVESTMENT
        $this->walletService->withdraw($userA, 300.00, TransactionType::INVESTMENT, 'Investment A', null, false, false, 50.00);
        $this->assertInvariant();

        // 7. Refunds
        $this->walletService->deposit($userA, 50.00, TransactionType::REFUND, 'Refund for investment A');
        $this->assertInvariant();

        // 8. Chargebacks
        // recordChargeback: DEBIT USER_WALLET_LIABILITY, CREDIT BANK
        $this->walletService->withdraw($userB, 100.00, TransactionType::CHARGEBACK, 'Chargeback B');
        $this->assertInvariant();

        // 9. TDS Deduction
        $this->walletService->withdraw($userA, 10.00, TransactionType::TDS_DEDUCTION, 'TDS for profit');
        $this->assertInvariant();

        // 10. Subscription Payment
        // V-AUDIT-REVENUE-2026: Subscription is now treated as a deposit (not revenue)
        $this->walletService->deposit($userB, 199.00, TransactionType::SUBSCRIPTION_PAYMENT, 'Annual Sub');
        $this->assertInvariant();
        
        // Final sanity check of total balances
        $totalWallets = Wallet::sum('balance_paise');
        $this->assertGreaterThan(0, $totalWallets, "Total wallet balance should be positive for this test scenario");
    }

    /**
     * Asserts that the sum of all wallet balances matches the USER_WALLET_LIABILITY ledger account.
     */
    protected function assertInvariant()
    {
        $sumWalletsPaise = (int) Wallet::sum('balance_paise');
        
        $ledgerAccount = LedgerAccount::where('code', LedgerAccount::CODE_USER_WALLET_LIABILITY)->first();
        
        // Ledger balance is in Rupees (float), but we want to compare in Paise for precision
        // We sum the ledger lines directly in Paise to avoid float conversion issues in the test itself
        $sumLedgerPaise = (int) $ledgerAccount->ledgerLines()
            ->selectRaw('SUM(CASE WHEN direction = "CREDIT" THEN amount_paise ELSE -amount_paise END) as total_paise')
            ->value('total_paise') ?? 0;

        $this->assertEquals(
            $sumLedgerPaise,
            $sumWalletsPaise,
            "INVARIANT FAILED: Wallet Sum ({$sumWalletsPaise} paise) != Ledger Liability ({$sumLedgerPaise} paise)"
        );
    }
}
