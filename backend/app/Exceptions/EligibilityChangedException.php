<?php
/**
 * V-AUDIT-FIX-2026: Eligibility Changed Exception
 *
 * Thrown when buy eligibility changes between display time and checkout commit.
 * This is a TOCTOU (Time-Of-Check-Time-Of-Use) protection.
 *
 * SCENARIO:
 * 1. Investor sees "eligible to buy" on company page
 * 2. Investor starts checkout
 * 3. Meanwhile: company is suspended, tier revoked, or buying disabled
 * 4. At checkout commit, re-check detects the change
 * 5. This exception is thrown to prevent invalid purchase
 *
 * RESPONSE PROTOCOL:
 * 1. REJECT the checkout
 * 2. Return clear message to investor
 * 3. Log for audit trail
 * 4. Investor must re-evaluate decision with new state
 *
 * HTTP RESPONSE: 409 Conflict
 */

namespace App\Exceptions;

use Exception;
use App\Models\Company;

class EligibilityChangedException extends Exception
{
    protected $code = 409;

    protected int $companyId;
    protected string $companyName;
    protected array $originalBlockers;
    protected array $currentBlockers;
    protected array $newBlockers;

    public function __construct(
        Company $company,
        array $originalBlockers,
        array $currentBlockers
    ) {
        $this->companyId = $company->id;
        $this->companyName = $company->name;
        $this->originalBlockers = $originalBlockers;
        $this->currentBlockers = $currentBlockers;

        // Calculate which blockers are new (weren't there at display time)
        $originalRules = array_column($originalBlockers, 'rule');
        $this->newBlockers = array_filter($currentBlockers, function ($blocker) use ($originalRules) {
            return !in_array($blocker['rule'], $originalRules);
        });

        $newBlockerRules = array_column($this->newBlockers, 'rule');
        $newBlockerMessage = empty($newBlockerRules)
            ? 'eligibility conditions changed'
            : implode(', ', $newBlockerRules);

        parent::__construct(
            "[ELIGIBILITY CHANGED] Company #{$company->id} ({$company->name}): " .
            "Buy eligibility changed since checkout started. New blockers: {$newBlockerMessage}"
        );
    }

    public function getCompanyId(): int
    {
        return $this->companyId;
    }

    public function getCompanyName(): string
    {
        return $this->companyName;
    }

    public function getOriginalBlockers(): array
    {
        return $this->originalBlockers;
    }

    public function getCurrentBlockers(): array
    {
        return $this->currentBlockers;
    }

    public function getNewBlockers(): array
    {
        return $this->newBlockers;
    }

    /**
     * Return structured context for audit logging.
     */
    public function reportContext(): array
    {
        return [
            'exception_type' => 'EligibilityChangedException',
            'alert_level' => 'MEDIUM',
            'company_id' => $this->companyId,
            'company_name' => $this->companyName,
            'original_blockers' => $this->originalBlockers,
            'current_blockers' => $this->currentBlockers,
            'new_blockers' => $this->newBlockers,
            'action_taken' => 'Checkout REJECTED - eligibility changed',
            'investor_impact' => 'Must re-evaluate and restart checkout',
        ];
    }

    /**
     * Get user-friendly error message for API response.
     */
    public function getUserMessage(): string
    {
        if (!empty($this->newBlockers)) {
            $reasons = array_column($this->newBlockers, 'message');
            return 'This investment is no longer available: ' . implode('. ', $reasons);
        }

        return 'The investment conditions have changed. Please review the company details and try again.';
    }

    /**
     * Static factory for common scenarios.
     */
    public static function companySuspended(Company $company): self
    {
        return new self($company, [], [
            ['rule' => 'company_suspended', 'message' => 'Company has been suspended'],
        ]);
    }

    public static function buyingDisabled(Company $company): self
    {
        return new self($company, [], [
            ['rule' => 'buying_disabled', 'message' => 'Buying has been disabled for this company'],
        ]);
    }

    public static function tierRevoked(Company $company): self
    {
        return new self($company, [], [
            ['rule' => 'tier_2_required', 'message' => 'Company no longer meets disclosure requirements'],
        ]);
    }
}
