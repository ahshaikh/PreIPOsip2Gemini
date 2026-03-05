# TODO: Financial Mutation Consolidation Plan

## Step 1: Extend FinancialOrchestrator
- [ ] Add `awardBulkBonus(User $user, float $amount, string $reason, string $type)`
- [ ] Add `awardReferralBonusFromJob(User $referredUser)`
- [ ] Add `requestWithdrawal(User $user, float $amount, array $bankDetails, ?string $idempotencyKey = null)`
- [ ] Add `rejectWithdrawal(Withdrawal $withdrawal, User $admin, string $reason)`
- [ ] Add `completeWithdrawal(Withdrawal $withdrawal, User $admin, string $utr)`
- [ ] Add `cancelWithdrawal(User $user, Withdrawal $withdrawal)`

## Step 2: Refactor Bonus Lifecycle
- [ ] Modify `AwardBulkBonusJob`: Call `FinancialOrchestrator::awardBulkBonus()`, remove `DB::transaction` and `walletService` calls.
- [ ] Modify `CelebrationBonusService`: Ensure `awardMilestoneBonus` remains mutation-free, verify how it's called.
- [ ] Modify `BonusCalculatorService`: Already has `calculateAndAwardBonuses` with `lockedWallet` support.

## Step 3: Refactor Referral Lifecycle
- [ ] Modify `ProcessReferralJob`: Call `FinancialOrchestrator::awardReferralBonusFromJob()`, remove `DB::transaction` and `walletService` calls.

## Step 4: Refactor Withdrawal Lifecycle
- [ ] Modify `WithdrawalService`:
    - [ ] Extract `createWithdrawalRecord` logic (validation vs mutation).
    - [ ] Remove `DB::transaction` and `walletService` calls from `createWithdrawalRecord`, `rejectWithdrawal`, `completeWithdrawal`, `cancelUserWithdrawal`.
- [ ] Update `FinancialOrchestrator` methods to call the cleaned-up `WithdrawalService` methods.

## Step 5: Verification
- [ ] Scan for direct `walletService->deposit` / `withdraw` in the target lifecycles.
- [ ] Verify `DB::transaction` and `lockForUpdate` are only in `FinancialOrchestrator`.
