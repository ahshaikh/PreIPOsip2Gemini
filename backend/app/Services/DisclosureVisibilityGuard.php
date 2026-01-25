<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CompanyDisclosure;
use App\Models\CompanyInvestment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * P0 FIX (GAP 25-27): Disclosure Visibility Guard
 *
 * PURPOSE:
 * Enforce strict visibility rules for company disclosures based on:
 * - Disclosure status (only approved visible to investors)
 * - User role (public, subscriber, admin)
 * - Visibility toggles (public_visible, subscriber_only)
 *
 * GAPS ADDRESSED:
 * - GAP 25: Investor APIs scoped to approved disclosures only
 * - GAP 26: Drafts/rejected disclosures never exposed to investors
 * - GAP 27: Public vs subscriber visibility enforced
 *
 * VISIBILITY LEVELS:
 * - PUBLIC: Anyone can view (no auth required)
 * - SUBSCRIBER: Only users who have invested in the company
 * - ADMIN: Platform administrators only
 * - COMPANY: Company representatives only
 *
 * CRITICAL: This guard MUST be used for ALL disclosure queries in investor-facing APIs.
 */
class DisclosureVisibilityGuard
{
    /**
     * Disclosure statuses visible to investors
     * GAP 25 & 26 FIX: Only approved disclosures
     */
    const INVESTOR_VISIBLE_STATUSES = ['approved'];

    /**
     * Disclosure statuses visible to company representatives
     */
    const COMPANY_VISIBLE_STATUSES = ['draft', 'submitted', 'under_review', 'clarification_required', 'approved', 'rejected'];

    /**
     * Disclosure statuses visible to admins
     */
    const ADMIN_VISIBLE_STATUSES = ['draft', 'submitted', 'under_review', 'clarification_required', 'approved', 'rejected'];

    /**
     * Visibility levels
     */
    const VISIBILITY_PUBLIC = 'public';
    const VISIBILITY_SUBSCRIBER = 'subscriber';
    const VISIBILITY_ADMIN = 'admin';
    const VISIBILITY_COMPANY = 'company';

    /**
     * GAP 25 FIX: Apply investor visibility scope to query
     *
     * Ensures query ONLY returns disclosures that:
     * 1. Have status = 'approved'
     * 2. Are marked as visible (is_visible = true)
     * 3. Meet public/subscriber visibility requirements
     *
     * @param Builder $query
     * @param User|null $user Current user (null = public/unauthenticated)
     * @param int|null $companyId Optional company filter
     * @return Builder
     */
    public function scopeForInvestor(Builder $query, ?User $user = null, ?int $companyId = null): Builder
    {
        // GAP 25 & 26: ONLY approved disclosures
        $query->whereIn('status', self::INVESTOR_VISIBLE_STATUSES);

        // Must be marked as visible
        $query->where('is_visible', true);

        // GAP 27: Apply public/subscriber visibility
        if ($user === null) {
            // Unauthenticated: Only public disclosures
            $query->where('visibility', self::VISIBILITY_PUBLIC);
        } else {
            // Authenticated: Public OR subscriber (if they're a subscriber)
            $query->where(function ($q) use ($user, $companyId) {
                // Always allow public
                $q->where('visibility', self::VISIBILITY_PUBLIC);

                // Allow subscriber-only if user is a subscriber
                if ($companyId) {
                    $isSubscriber = $this->isSubscriber($user->id, $companyId);
                    if ($isSubscriber) {
                        $q->orWhere('visibility', self::VISIBILITY_SUBSCRIBER);
                    }
                } else {
                    // No specific company - allow subscriber content for any company they've invested in
                    $subscribedCompanyIds = $this->getSubscribedCompanyIds($user->id);
                    if (!empty($subscribedCompanyIds)) {
                        $q->orWhere(function ($subQ) use ($subscribedCompanyIds) {
                            $subQ->where('visibility', self::VISIBILITY_SUBSCRIBER)
                                ->whereIn('company_id', $subscribedCompanyIds);
                        });
                    }
                }
            });
        }

        // Apply company filter if provided
        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        Log::debug('VISIBILITY: Applied investor scope', [
            'user_id' => $user?->id,
            'company_id' => $companyId,
            'allowed_statuses' => self::INVESTOR_VISIBLE_STATUSES,
        ]);

        return $query;
    }

    /**
     * GAP 26 FIX: Verify a specific disclosure is visible to investor
     *
     * Use this for single-disclosure endpoints to prevent enumeration attacks.
     *
     * @param int $disclosureId
     * @param User|null $user
     * @return array{visible: bool, reason: string|null, disclosure: CompanyDisclosure|null}
     */
    public function canInvestorViewDisclosure(int $disclosureId, ?User $user = null): array
    {
        $disclosure = CompanyDisclosure::find($disclosureId);

        if (!$disclosure) {
            return [
                'visible' => false,
                'reason' => 'Disclosure not found',
                'disclosure' => null,
            ];
        }

        // GAP 26: Check status
        if (!in_array($disclosure->status, self::INVESTOR_VISIBLE_STATUSES)) {
            Log::warning('VISIBILITY: Investor attempted to access non-approved disclosure', [
                'disclosure_id' => $disclosureId,
                'status' => $disclosure->status,
                'user_id' => $user?->id,
            ]);

            return [
                'visible' => false,
                'reason' => 'Disclosure is not available',
                'disclosure' => null,
            ];
        }

        // Check is_visible flag
        if (!$disclosure->is_visible) {
            return [
                'visible' => false,
                'reason' => 'Disclosure is not available',
                'disclosure' => null,
            ];
        }

        // GAP 27: Check visibility level
        if ($disclosure->visibility === self::VISIBILITY_SUBSCRIBER) {
            if ($user === null) {
                return [
                    'visible' => false,
                    'reason' => 'Authentication required',
                    'disclosure' => null,
                ];
            }

            if (!$this->isSubscriber($user->id, $disclosure->company_id)) {
                return [
                    'visible' => false,
                    'reason' => 'This content is only available to investors in this company',
                    'disclosure' => null,
                ];
            }
        }

        if ($disclosure->visibility === self::VISIBILITY_ADMIN) {
            return [
                'visible' => false,
                'reason' => 'Disclosure is not available',
                'disclosure' => null,
            ];
        }

        if ($disclosure->visibility === self::VISIBILITY_COMPANY) {
            return [
                'visible' => false,
                'reason' => 'Disclosure is not available',
                'disclosure' => null,
            ];
        }

        return [
            'visible' => true,
            'reason' => null,
            'disclosure' => $disclosure,
        ];
    }

    /**
     * GAP 26 FIX: Sanitize disclosure data for investor response
     *
     * Removes any fields that should never be exposed to investors.
     *
     * @param CompanyDisclosure $disclosure
     * @param User|null $user
     * @return array
     */
    public function sanitizeForInvestor(CompanyDisclosure $disclosure, ?User $user = null): array
    {
        // Fields that investors should NEVER see
        $sensitiveFields = [
            'admin_notes',
            'internal_tags',
            'review_notes',
            'rejection_reason',
            'clarification_requests',
            'submitted_by_id',
            'submitted_by_type',
            'reviewed_by_id',
            'ip_address',
            'user_agent',
        ];

        $data = $disclosure->toArray();

        // Remove sensitive fields
        foreach ($sensitiveFields as $field) {
            unset($data[$field]);
        }

        // Add visibility context
        $data['_visibility'] = [
            'level' => $disclosure->visibility,
            'is_subscriber_content' => $disclosure->visibility === self::VISIBILITY_SUBSCRIBER,
        ];

        return $data;
    }

    /**
     * Apply company visibility scope
     *
     * Company representatives can see their own disclosures in any status.
     *
     * @param Builder $query
     * @param User $user Company representative
     * @param int $companyId
     * @return Builder
     */
    public function scopeForCompany(Builder $query, User $user, int $companyId): Builder
    {
        // Verify user belongs to this company
        if (!$this->isCompanyRepresentative($user, $companyId)) {
            // Return empty result set
            $query->whereRaw('1 = 0');
            return $query;
        }

        $query->where('company_id', $companyId);
        $query->whereIn('status', self::COMPANY_VISIBLE_STATUSES);

        return $query;
    }

    /**
     * Apply admin visibility scope
     *
     * Admins can see all disclosures.
     *
     * @param Builder $query
     * @param User $admin
     * @return Builder
     */
    public function scopeForAdmin(Builder $query, User $admin): Builder
    {
        if (!$this->isAdmin($admin)) {
            $query->whereRaw('1 = 0');
            return $query;
        }

        // Admins see all statuses
        $query->whereIn('status', self::ADMIN_VISIBLE_STATUSES);

        return $query;
    }

    /**
     * GAP 27 FIX: Get visibility requirements for a company
     *
     * Returns what visibility levels are available for each disclosure type.
     *
     * @param int $companyId
     * @return array
     */
    public function getVisibilityRequirements(int $companyId): array
    {
        return [
            'public_disclosures' => [
                'description' => 'Visible to anyone without authentication',
                'modules' => ['company_overview', 'basic_financials', 'team_overview'],
            ],
            'subscriber_disclosures' => [
                'description' => 'Visible only to users who have invested in this company',
                'modules' => ['detailed_financials', 'projections', 'cap_table', 'legal_documents'],
            ],
            'always_public' => [
                'description' => 'Must always be public (regulatory requirement)',
                'modules' => ['risk_disclosure', 'regulatory_filings', 'material_events'],
            ],
        ];
    }

    /**
     * Validate visibility setting for a disclosure
     *
     * Some disclosures MUST be public (regulatory requirement).
     *
     * @param string $moduleType
     * @param string $requestedVisibility
     * @return array{valid: bool, message: string|null}
     */
    public function validateVisibilitySetting(string $moduleType, string $requestedVisibility): array
    {
        $alwaysPublicModules = ['risk_disclosure', 'regulatory_filings', 'material_events'];

        if (in_array($moduleType, $alwaysPublicModules) && $requestedVisibility !== self::VISIBILITY_PUBLIC) {
            return [
                'valid' => false,
                'message' => "Module '{$moduleType}' must have public visibility (regulatory requirement)",
            ];
        }

        $validVisibilities = [self::VISIBILITY_PUBLIC, self::VISIBILITY_SUBSCRIBER];
        if (!in_array($requestedVisibility, $validVisibilities)) {
            return [
                'valid' => false,
                'message' => "Invalid visibility level. Must be 'public' or 'subscriber'",
            ];
        }

        return [
            'valid' => true,
            'message' => null,
        ];
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    /**
     * Check if user is a subscriber (investor) of a company
     */
    public function isSubscriber(int $userId, int $companyId): bool
    {
        return CompanyInvestment::where('user_id', $userId)
            ->where('company_id', $companyId)
            ->whereIn('status', ['completed', 'active'])
            ->exists();
    }

    /**
     * Get all company IDs user has invested in
     */
    public function getSubscribedCompanyIds(int $userId): array
    {
        return CompanyInvestment::where('user_id', $userId)
            ->whereIn('status', ['completed', 'active'])
            ->pluck('company_id')
            ->unique()
            ->toArray();
    }

    /**
     * Check if user is a company representative
     */
    protected function isCompanyRepresentative(User $user, int $companyId): bool
    {
        // Check company_users table or user's company_id field
        return DB::table('company_users')
            ->where('user_id', $user->id)
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Check if user is an admin
     */
    protected function isAdmin(User $user): bool
    {
        return $user->is_admin ||
               $user->hasRole('admin') ||
               $user->hasRole('super_admin');
    }

    /**
     * Log visibility violation attempt
     */
    public function logViolationAttempt(
        string $attemptType,
        ?int $userId,
        int $disclosureId,
        string $reason
    ): void {
        Log::warning('VISIBILITY VIOLATION ATTEMPT', [
            'type' => $attemptType,
            'user_id' => $userId,
            'disclosure_id' => $disclosureId,
            'reason' => $reason,
            'ip' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'timestamp' => now()->toIso8601String(),
        ]);

        // Store in audit log for security review
        DB::table('security_audit_log')->insert([
            'event_type' => 'visibility_violation_attempt',
            'user_id' => $userId,
            'resource_type' => 'disclosure',
            'resource_id' => $disclosureId,
            'details' => json_encode([
                'attempt_type' => $attemptType,
                'reason' => $reason,
            ]),
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'created_at' => now(),
        ]);
    }
}
