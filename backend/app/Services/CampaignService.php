<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\CampaignUsage;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CampaignService
{
    protected FeatureFlagService $featureFlagService;

    public function __construct(FeatureFlagService $featureFlagService)
    {
        $this->featureFlagService = $featureFlagService;
    }

    /**
     * Validate if a campaign code exists and return the campaign
     *
     * @param string $code
     * @return Campaign|null
     */
    public function validateCampaignCode(string $code): ?Campaign
    {
        return Campaign::where('code', $code)->first();
    }

    /**
     * Check if a campaign is applicable for a user and investment amount
     *
     * @param Campaign $campaign
     * @param User $user
     * @param float $amount
     * @param string $context Optional context for feature flag check (investment, subscription, etc.)
     * @return array ['applicable' => bool, 'reason' => string|null]
     */
    public function isApplicable(Campaign $campaign, User $user, float $amount, string $context = 'investment'): array
    {
        // Check if campaigns feature is globally enabled
        if (!$this->featureFlagService->isCampaignsEnabled()) {
            return [
                'applicable' => false,
                'reason' => 'Campaigns are temporarily disabled'
            ];
        }

        // Check if campaign type is enabled
        if (!$this->featureFlagService->isCampaignTypeEnabled($campaign->discount_type)) {
            return [
                'applicable' => false,
                'reason' => 'This campaign type is temporarily unavailable'
            ];
        }

        // Check if campaign application is enabled for this context
        if (!$this->featureFlagService->isCampaignApplicationEnabled($context)) {
            return [
                'applicable' => false,
                'reason' => 'Campaign application is temporarily disabled for this type of transaction'
            ];
        }

        // Check if campaign is approved
        if (!$campaign->is_approved) {
            return [
                'applicable' => false,
                'reason' => 'Campaign is not yet approved'
            ];
        }

        // Check if campaign is active
        if (!$campaign->is_active) {
            return [
                'applicable' => false,
                'reason' => 'Campaign is currently paused'
            ];
        }

        // Check if campaign has started
        if ($campaign->start_at && $campaign->start_at->isFuture()) {
            return [
                'applicable' => false,
                'reason' => 'Campaign has not started yet. Starts on ' . $campaign->start_at->format('M d, Y')
            ];
        }

        // Check if campaign has expired
        if ($campaign->end_at && $campaign->end_at->isPast()) {
            return [
                'applicable' => false,
                'reason' => 'Campaign has expired'
            ];
        }

        // Check global usage limit
        if ($campaign->usage_limit && $campaign->usage_count >= $campaign->usage_limit) {
            return [
                'applicable' => false,
                'reason' => 'Campaign usage limit has been reached'
            ];
        }

        // Check per-user usage limit
        if ($campaign->user_usage_limit) {
            $userUsageCount = $this->getUserUsageCount($campaign, $user);
            if ($userUsageCount >= $campaign->user_usage_limit) {
                return [
                    'applicable' => false,
                    'reason' => 'You have already used this campaign the maximum number of times'
                ];
            }
        }

        // Check minimum investment requirement
        if ($campaign->min_investment && $amount < $campaign->min_investment) {
            return [
                'applicable' => false,
                'reason' => 'Minimum investment of ₹' . number_format($campaign->min_investment, 2) . ' required'
            ];
        }

        return [
            'applicable' => true,
            'reason' => null
        ];
    }

    /**
     * Calculate discount for a campaign and amount
     *
     * @param Campaign $campaign
     * @param float $amount
     * @return float
     */
    public function calculateDiscount(Campaign $campaign, float $amount): float
    {
        $discount = 0;

        if ($campaign->discount_type === 'fixed_amount') {
            $discount = $campaign->discount_amount ?? 0;
        } elseif ($campaign->discount_type === 'percentage') {
            $discount = ($amount * ($campaign->discount_percent / 100));
        }

        // Apply maximum discount cap if set
        if ($campaign->max_discount && $discount > $campaign->max_discount) {
            $discount = $campaign->max_discount;
        }

        // Ensure discount doesn't exceed the amount
        if ($discount > $amount) {
            $discount = $amount;
        }

        return round($discount, 2);
    }

    /**
     * Apply a campaign to an investment/subscription and record usage
     *
     * @param Campaign $campaign
     * @param User $user
     * @param Model $applicable The entity to which campaign is being applied (Investment, Subscription, etc.)
     * @param float $originalAmount
     * @param bool $termsAccepted Whether user accepted campaign terms
     * @param bool $disclaimerAcknowledged Whether user acknowledged regulatory disclaimers
     * @return array ['success' => bool, 'usage' => CampaignUsage|null, 'discount' => float, 'message' => string]
     */
    public function applyCampaign(
        Campaign $campaign,
        User $user,
        Model $applicable,
        float $originalAmount,
        bool $termsAccepted = true,
        bool $disclaimerAcknowledged = true
    ): array {
        try {
            return DB::transaction(function () use ($campaign, $user, $applicable, $originalAmount, $termsAccepted, $disclaimerAcknowledged) {
                // Lock campaign for update to prevent race conditions
                $campaign = Campaign::where('id', $campaign->id)
                    ->lockForUpdate()
                    ->first();

                if (!$campaign) {
                    return [
                        'success' => false,
                        'usage' => null,
                        'discount' => 0,
                        'message' => 'Campaign not found'
                    ];
                }

                // Re-validate applicability with locked campaign
                $applicabilityCheck = $this->isApplicable($campaign, $user, $originalAmount);
                if (!$applicabilityCheck['applicable']) {
                    return [
                        'success' => false,
                        'usage' => null,
                        'discount' => 0,
                        'message' => $applicabilityCheck['reason']
                    ];
                }

                // Check for duplicate application to same entity
                $existingUsage = CampaignUsage::where('campaign_id', $campaign->id)
                    ->where('applicable_type', get_class($applicable))
                    ->where('applicable_id', $applicable->id)
                    ->first();

                if ($existingUsage) {
                    return [
                        'success' => false,
                        'usage' => $existingUsage,
                        'discount' => $existingUsage->discount_applied,
                        'message' => 'Campaign has already been applied to this transaction'
                    ];
                }

                // Calculate discount
                $discount = $this->calculateDiscount($campaign, $originalAmount);
                $finalAmount = $originalAmount - $discount;

                // Create campaign usage record
                $usage = CampaignUsage::create([
                    'campaign_id' => $campaign->id,
                    'user_id' => $user->id,
                    'applicable_type' => get_class($applicable),
                    'applicable_id' => $applicable->id,
                    'original_amount' => $originalAmount,
                    'discount_applied' => $discount,
                    'final_amount' => $finalAmount,
                    'campaign_code' => $campaign->code,
                    'campaign_snapshot' => $campaign->toArray(),
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'used_at' => now(),
                    'terms_accepted' => $termsAccepted,
                    'terms_accepted_at' => $termsAccepted ? now() : null,
                    'terms_acceptance_ip' => $termsAccepted ? request()->ip() : null,
                    'disclaimer_acknowledged' => $disclaimerAcknowledged,
                    'disclaimer_acknowledged_at' => $disclaimerAcknowledged ? now() : null,
                ]);

                // Increment campaign usage count
                $campaign->increment('usage_count');

                Log::info('Campaign applied successfully', [
                    'campaign_id' => $campaign->id,
                    'campaign_code' => $campaign->code,
                    'user_id' => $user->id,
                    'applicable_type' => get_class($applicable),
                    'applicable_id' => $applicable->id,
                    'discount' => $discount,
                ]);

                return [
                    'success' => true,
                    'usage' => $usage,
                    'discount' => $discount,
                    'message' => 'Campaign applied successfully'
                ];
            });
        } catch (\Exception $e) {
            Log::error('Failed to apply campaign', [
                'campaign_id' => $campaign->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'usage' => null,
                'discount' => 0,
                'message' => 'Failed to apply campaign: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get the number of times a user has used a campaign
     *
     * @param Campaign $campaign
     * @param User $user
     * @return int
     */
    public function getUserUsageCount(Campaign $campaign, User $user): int
    {
        return CampaignUsage::where('campaign_id', $campaign->id)
            ->where('user_id', $user->id)
            ->count();
    }

    /**
     * Get usage statistics for a campaign
     *
     * @param Campaign $campaign
     * @return array
     */
    public function getCampaignStats(Campaign $campaign): array
    {
        $usages = CampaignUsage::where('campaign_id', $campaign->id);

        return [
            'total_usage_count' => $usages->count(),
            'unique_users_count' => $usages->distinct('user_id')->count('user_id'),
            'total_discount_given' => $usages->sum('discount_applied'),
            'total_original_amount' => $usages->sum('original_amount'),
            'total_final_amount' => $usages->sum('final_amount'),
            'average_discount' => $usages->avg('discount_applied'),
            'remaining_usage' => $campaign->remaining_usage,
            'usage_percentage' => $campaign->usage_percentage,
        ];
    }

    /**
     * Get all active campaigns applicable to a user
     *
     * @param User $user
     * @param float|null $amount Optional amount to check applicability
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getApplicableCampaigns(User $user, ?float $amount = null)
    {
        $campaigns = Campaign::active()->get();

        if ($amount !== null) {
            $campaigns = $campaigns->filter(function ($campaign) use ($user, $amount) {
                $check = $this->isApplicable($campaign, $user, $amount);
                return $check['applicable'];
            });
        }

        return $campaigns;
    }

    /**
     * Approve a campaign
     *
     * @param Campaign $campaign
     * @param User $approver
     * @return bool
     */
    public function approveCampaign(Campaign $campaign, User $approver): bool
    {
        if (!$campaign->can_be_approved) {
            return false;
        }

        $campaign->update([
            'approved_by' => $approver->id,
            'approved_at' => now(),
        ]);

        Log::info('Campaign approved', [
            'campaign_id' => $campaign->id,
            'campaign_code' => $campaign->code,
            'approved_by' => $approver->id,
        ]);

        return true;
    }

    /**
     * Activate a campaign
     *
     * @param Campaign $campaign
     * @return bool
     */
    public function activateCampaign(Campaign $campaign): bool
    {
        if (!$campaign->can_be_activated) {
            return false;
        }

        $campaign->update(['is_active' => true]);

        Log::info('Campaign activated', [
            'campaign_id' => $campaign->id,
            'campaign_code' => $campaign->code,
        ]);

        return true;
    }

    /**
     * Pause a campaign
     *
     * @param Campaign $campaign
     * @return bool
     */
    public function pauseCampaign(Campaign $campaign): bool
    {
        if (!$campaign->can_be_paused) {
            return false;
        }

        $campaign->update(['is_active' => false]);

        Log::info('Campaign paused', [
            'campaign_id' => $campaign->id,
            'campaign_code' => $campaign->code,
        ]);

        return true;
    }

    /**
     * Archive a campaign
     * Archived campaigns are soft-disabled and moved out of active view
     *
     * @param Campaign $campaign
     * @param User $archiver
     * @param string|null $reason
     * @return bool
     */
    public function archiveCampaign(Campaign $campaign, User $archiver, ?string $reason = null): bool
    {
        // Archive conditions: expired or manually archived by admin
        $campaign->update([
            'is_active' => false,
            'is_archived' => true,
            'archived_by' => $archiver->id,
            'archived_at' => now(),
            'archive_reason' => $reason ?? 'Campaign archived by admin',
        ]);

        Log::info('Campaign archived', [
            'campaign_id' => $campaign->id,
            'campaign_code' => $campaign->code,
            'archived_by' => $archiver->id,
            'reason' => $reason,
        ]);

        return true;
    }

    /**
     * Unarchive a campaign
     *
     * @param Campaign $campaign
     * @return bool
     */
    public function unarchiveCampaign(Campaign $campaign): bool
    {
        if (!$campaign->is_archived) {
            return false;
        }

        $campaign->update([
            'is_archived' => false,
            'archived_by' => null,
            'archived_at' => null,
            'archive_reason' => null,
        ]);

        Log::info('Campaign unarchived', [
            'campaign_id' => $campaign->id,
            'campaign_code' => $campaign->code,
        ]);

        return true;
    }

    /**
     * Auto-archive expired campaigns
     * Should be run via scheduled task
     *
     * @param User $systemUser
     * @return int Number of campaigns archived
     */
    public function autoArchiveExpiredCampaigns(User $systemUser): int
    {
        $expiredCampaigns = Campaign::whereNotNull('end_at')
            ->where('end_at', '<', now())
            ->where('is_archived', false)
            ->get();

        $count = 0;
        foreach ($expiredCampaigns as $campaign) {
            $this->archiveCampaign($campaign, $systemUser, 'Auto-archived: Campaign expired');
            $count++;
        }

        if ($count > 0) {
            Log::info("Auto-archived {$count} expired campaigns");
        }

        return $count;
    }
}
