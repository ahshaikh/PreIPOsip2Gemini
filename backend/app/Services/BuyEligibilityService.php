<?php
/**
 * V-AUDIT-FIX-2026: Buy Eligibility Service
 *
 * PURPOSE:
 * Centralized service for checking and verifying buy eligibility.
 * Provides TOCTOU protection by re-verifying at checkout commit.
 *
 * ELIGIBILITY TOKEN:
 * Short-lived token that captures eligibility state at display time.
 * Must be presented at checkout and re-verified against current state.
 *
 * RULES CHECKED:
 * 1. Company has Tier 2 approval
 * 2. Buying is enabled for company
 * 3. Company is not suspended
 * 4. Company is not frozen
 * 5. Sufficient inventory exists (optional check)
 */

namespace App\Services;

use App\Exceptions\EligibilityChangedException;
use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BuyEligibilityService
{
    /**
     * Default token TTL in minutes
     */
    protected const TOKEN_TTL_MINUTES = 15;

    /**
     * Check current buy eligibility for a company.
     *
     * @param Company $company
     * @return array{eligible: bool, blockers: array, checked_at: string}
     */
    public function checkEligibility(Company $company): array
    {
        $blockers = [];

        // CHECK 1: Tier 2 must be approved
        if (!$company->tier_2_approved_at) {
            $blockers[] = [
                'rule' => 'tier_2_required',
                'severity' => 'critical',
                'message' => 'Tier 2 disclosures must be approved before buying is enabled',
            ];
        }

        // CHECK 2: Buying must be enabled
        if (!($company->buying_enabled ?? true)) {
            $blockers[] = [
                'rule' => 'buying_disabled',
                'severity' => 'critical',
                'message' => 'Buying is currently disabled for this company',
                'reason' => $company->buying_pause_reason ?? 'Platform restriction',
            ];
        }

        // CHECK 3: Company must not be suspended
        if ($company->lifecycle_state === 'suspended' || ($company->is_suspended ?? false)) {
            $blockers[] = [
                'rule' => 'company_suspended',
                'severity' => 'critical',
                'message' => 'Company is suspended - buying not allowed',
            ];
        }

        // CHECK 4: Company must not be frozen
        if ($company->disclosure_freeze ?? false) {
            $blockers[] = [
                'rule' => 'company_frozen',
                'severity' => 'critical',
                'message' => 'Company disclosures are frozen - buying not allowed',
            ];
        }

        return [
            'eligible' => empty($blockers),
            'blockers' => $blockers,
            'checked_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Generate an eligibility token for checkout.
     *
     * Token captures current eligibility state and has short TTL.
     * Must be presented at checkout for re-verification.
     *
     * @param Company $company
     * @param User $user
     * @param int $ttlMinutes Token validity in minutes
     * @return array{token: string, expires_at: string, eligibility: array}
     */
    public function generateEligibilityToken(
        Company $company,
        User $user,
        int $ttlMinutes = self::TOKEN_TTL_MINUTES
    ): array {
        $eligibility = $this->checkEligibility($company);

        if (!$eligibility['eligible']) {
            // Don't generate token if not currently eligible
            return [
                'token' => null,
                'expires_at' => null,
                'eligibility' => $eligibility,
                'error' => 'Cannot generate token - company not eligible for buying',
            ];
        }

        $token = Str::uuid()->toString();
        $expiresAt = now()->addMinutes($ttlMinutes);

        $tokenData = [
            'company_id' => $company->id,
            'user_id' => $user->id,
            'blockers_at_issue' => $eligibility['blockers'],
            'issued_at' => now()->toIso8601String(),
            'expires_at' => $expiresAt->toIso8601String(),
            'company_state_hash' => $this->computeStateHash($company),
        ];

        // Store in cache
        Cache::put(
            $this->getTokenCacheKey($token),
            $tokenData,
            $expiresAt
        );

        Log::info('[ELIGIBILITY TOKEN] Generated', [
            'token' => substr($token, 0, 8) . '...',
            'company_id' => $company->id,
            'user_id' => $user->id,
            'expires_at' => $expiresAt->toIso8601String(),
        ]);

        return [
            'token' => $token,
            'expires_at' => $expiresAt->toIso8601String(),
            'eligibility' => $eligibility,
        ];
    }

    /**
     * Verify eligibility token at checkout commit.
     *
     * Re-checks current eligibility and compares with token state.
     * Throws EligibilityChangedException if state changed.
     *
     * @param string $token
     * @param Company $company
     * @param User $user
     * @return array Current eligibility (if valid)
     * @throws EligibilityChangedException If eligibility changed
     * @throws \InvalidArgumentException If token invalid/expired
     */
    public function verifyTokenAtCheckout(string $token, Company $company, User $user): array
    {
        // Retrieve token data
        $tokenData = Cache::get($this->getTokenCacheKey($token));

        if (!$tokenData) {
            throw new \InvalidArgumentException('Eligibility token is invalid or expired. Please restart checkout.');
        }

        // Verify token matches company and user
        if ($tokenData['company_id'] !== $company->id) {
            throw new \InvalidArgumentException('Eligibility token is for a different company.');
        }

        if ($tokenData['user_id'] !== $user->id) {
            throw new \InvalidArgumentException('Eligibility token is for a different user.');
        }

        // Re-check current eligibility
        $currentEligibility = $this->checkEligibility($company);
        $originalBlockers = $tokenData['blockers_at_issue'];

        // If was eligible at token issue but not now, throw exception
        if (empty($originalBlockers) && !$currentEligibility['eligible']) {
            Log::warning('[ELIGIBILITY CHANGED] Detected at checkout', [
                'token' => substr($token, 0, 8) . '...',
                'company_id' => $company->id,
                'user_id' => $user->id,
                'original_blockers' => $originalBlockers,
                'current_blockers' => $currentEligibility['blockers'],
            ]);

            throw new EligibilityChangedException(
                $company,
                $originalBlockers,
                $currentEligibility['blockers']
            );
        }

        // Optional: Check if state hash changed (catches subtle changes)
        $currentStateHash = $this->computeStateHash($company);
        if ($currentStateHash !== $tokenData['company_state_hash']) {
            // State changed but still eligible - log for audit
            Log::info('[ELIGIBILITY STATE CHANGED] But still eligible', [
                'company_id' => $company->id,
                'user_id' => $user->id,
                'old_hash' => $tokenData['company_state_hash'],
                'new_hash' => $currentStateHash,
            ]);
        }

        // Invalidate token after use (single use)
        Cache::forget($this->getTokenCacheKey($token));

        return $currentEligibility;
    }

    /**
     * Re-verify eligibility at checkout (without token).
     *
     * Used when token system is not in use.
     * Compares current state against provided original blockers.
     *
     * @param Company $company
     * @param array $originalBlockers Blockers shown at display time
     * @throws EligibilityChangedException If eligibility degraded
     */
    public function assertEligibilityUnchanged(Company $company, array $originalBlockers = []): void
    {
        $currentEligibility = $this->checkEligibility($company);

        // If was eligible (no blockers) but now has blockers
        if (empty($originalBlockers) && !$currentEligibility['eligible']) {
            throw new EligibilityChangedException(
                $company,
                $originalBlockers,
                $currentEligibility['blockers']
            );
        }

        // If has NEW blockers that weren't there before
        $originalRules = array_column($originalBlockers, 'rule');
        $newBlockers = array_filter($currentEligibility['blockers'], function ($blocker) use ($originalRules) {
            return !in_array($blocker['rule'], $originalRules);
        });

        if (!empty($newBlockers)) {
            throw new EligibilityChangedException(
                $company,
                $originalBlockers,
                $currentEligibility['blockers']
            );
        }
    }

    /**
     * Compute a hash of company state for change detection.
     */
    protected function computeStateHash(Company $company): string
    {
        $stateData = [
            'tier_2_approved' => $company->tier_2_approved_at !== null,
            'buying_enabled' => $company->buying_enabled ?? true,
            'lifecycle_state' => $company->lifecycle_state,
            'is_suspended' => $company->is_suspended ?? false,
            'disclosure_freeze' => $company->disclosure_freeze ?? false,
        ];

        return hash('sha256', json_encode($stateData));
    }

    /**
     * Get cache key for token.
     */
    protected function getTokenCacheKey(string $token): string
    {
        return "eligibility_token:{$token}";
    }
}
