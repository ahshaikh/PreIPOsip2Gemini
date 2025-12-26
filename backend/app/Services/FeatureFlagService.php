<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class FeatureFlagService
{
    /**
     * Check if campaigns feature is globally enabled
     */
    public function isCampaignsEnabled(): bool
    {
        return Cache::remember('feature_flag:campaigns_enabled', 3600, function () {
            return setting('campaigns_enabled', true);
        });
    }

    /**
     * Check if specific campaign type is enabled
     */
    public function isCampaignTypeEnabled(string $discountType): bool
    {
        $key = "feature_flag:campaign_type:{$discountType}";

        return Cache::remember($key, 3600, function () use ($discountType) {
            return setting("campaign_type_{$discountType}_enabled", true);
        });
    }

    /**
     * Check if campaign application is enabled for specific context
     */
    public function isCampaignApplicationEnabled(string $context): bool
    {
        // context: 'investment', 'subscription', 'withdrawal'
        $key = "feature_flag:campaign_application:{$context}";

        return Cache::remember($key, 3600, function () use ($context) {
            return setting("campaign_application_{$context}_enabled", true);
        });
    }

    /**
     * Enable campaigns globally
     */
    public function enableCampaigns(): void
    {
        setting(['campaigns_enabled' => true]);
        Cache::forget('feature_flag:campaigns_enabled');
        Log::info('Campaigns feature enabled globally');
    }

    /**
     * Disable campaigns globally (kill switch)
     */
    public function disableCampaigns(): void
    {
        setting(['campaigns_enabled' => false]);
        Cache::forget('feature_flag:campaigns_enabled');
        Log::warning('Campaigns feature disabled globally - KILL SWITCH ACTIVATED');
    }

    /**
     * Enable specific campaign type
     */
    public function enableCampaignType(string $discountType): void
    {
        setting(["campaign_type_{$discountType}_enabled" => true]);
        Cache::forget("feature_flag:campaign_type:{$discountType}");
        Log::info("Campaign type enabled: {$discountType}");
    }

    /**
     * Disable specific campaign type
     */
    public function disableCampaignType(string $discountType): void
    {
        setting(["campaign_type_{$discountType}_enabled" => false]);
        Cache::forget("feature_flag:campaign_type:{$discountType}");
        Log::warning("Campaign type disabled: {$discountType}");
    }

    /**
     * Clear all feature flag cache
     */
    public function clearCache(): void
    {
        Cache::flush();
        Log::info('Feature flag cache cleared');
    }
}
