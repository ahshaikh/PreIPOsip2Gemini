<?php
// V-CONTRACT-HARDENING-004: Subscription config snapshot service
// V-CONTRACT-HARDENING-FINAL: Strengthened canonical hash computation
// This service resolves plan bonus configuration and snapshots it into the subscription
// at creation time, creating an immutable contractual record.

namespace App\Services;

use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Support\Facades\Log;

/**
 * SubscriptionConfigSnapshotService
 *
 * Handles the creation and verification of immutable bonus contract snapshots.
 *
 * HASH DEFINITION (V-CONTRACT-HARDENING-FINAL):
 * The snapshot hash includes ALL of the following in canonical order:
 * - config_snapshot_at (ISO8601 timestamp)
 * - plan_id
 * - progressive_config (sorted JSON)
 * - milestone_config (sorted JSON)
 * - consistency_config (sorted JSON)
 * - welcome_bonus_config (sorted JSON)
 * - referral_tiers (sorted JSON)
 * - celebration_bonus_config (sorted JSON)
 * - lucky_draw_entries (sorted JSON)
 *
 * Hash algorithm: SHA256, truncated to 32 characters
 * JSON encoding: No whitespace, no unicode escaping, sorted keys recursively
 */
class SubscriptionConfigSnapshotService
{
    /**
     * Default configuration values when plan config is missing
     * These ensure every subscription has valid bonus calculation parameters
     */
    private const DEFAULT_PROGRESSIVE_CONFIG = [
        'rate' => 0.5,
        'start_month' => 4,
        'max_percentage' => 20,
        'overrides' => [],
    ];

    private const DEFAULT_MILESTONE_CONFIG = [];

    private const DEFAULT_CONSISTENCY_CONFIG = [
        'amount_per_payment' => 0,
        'streaks' => [],
    ];

    private const DEFAULT_WELCOME_BONUS_CONFIG = [
        'amount' => 500,
    ];

    private const DEFAULT_REFERRAL_TIERS = [
        'tiers' => [],
    ];

    private const DEFAULT_CELEBRATION_CONFIG = [
        'enabled' => false,
        'events' => [],
    ];

    private const DEFAULT_LUCKY_DRAW_CONFIG = [
        'entries_per_payment' => 0,
    ];

    /**
     * Resolve and snapshot all bonus configuration from plan to subscription
     *
     * @param Subscription $subscription The subscription to populate
     * @param Plan $plan The plan to snapshot configuration from
     * @return Subscription The subscription with snapshotted config
     */
    public function snapshotConfigToSubscription(Subscription $subscription, Plan $plan): Subscription
    {
        // Ensure plan configs are loaded
        if (!$plan->relationLoaded('configs')) {
            $plan->load('configs');
        }

        // Resolve each config type with validation
        $progressiveConfig = $this->resolveAndValidateProgressiveConfig($plan);
        $milestoneConfig = $this->resolveAndValidateMilestoneConfig($plan);
        $consistencyConfig = $this->resolveAndValidateConsistencyConfig($plan);
        $welcomeBonusConfig = $this->resolveWelcomeBonusConfig($plan);
        $referralTiers = $this->resolveReferralTiers($plan);
        $celebrationConfig = $this->resolveCelebrationConfig($plan);
        $luckyDrawConfig = $this->resolveLuckyDrawConfig($plan);

        // Set snapshot timestamp FIRST (needed for hash)
        $snapshotAt = now();

        // Update subscription with snapshotted config
        $subscription->progressive_config = $progressiveConfig;
        $subscription->milestone_config = $milestoneConfig;
        $subscription->consistency_config = $consistencyConfig;
        $subscription->welcome_bonus_config = $welcomeBonusConfig;
        $subscription->referral_tiers = $referralTiers;
        $subscription->celebration_bonus_config = $celebrationConfig;
        $subscription->lucky_draw_entries = $luckyDrawConfig;
        $subscription->config_snapshot_at = $snapshotAt;

        // V-CONTRACT-HARDENING-FINAL: Generate canonical hash including plan_id and timestamp
        $versionHash = $this->computeCanonicalHash(
            $subscription->plan_id,
            $snapshotAt,
            $progressiveConfig,
            $milestoneConfig,
            $consistencyConfig,
            $welcomeBonusConfig,
            $referralTiers,
            $celebrationConfig,
            $luckyDrawConfig
        );
        $subscription->config_snapshot_version = $versionHash;

        Log::channel('financial_contract')->info('Bonus contract snapshot created', [
            'subscription_id' => $subscription->id,
            'plan_id' => $plan->id,
            'version_hash' => $versionHash,
            'snapshot_at' => $snapshotAt->toIso8601String(),
            'hash_inputs' => 'plan_id + timestamp + 7 config fields (canonical JSON)',
        ]);

        return $subscription;
    }

    /**
     * V-CONTRACT-HARDENING-FINAL: Compute canonical hash for contract integrity
     *
     * HASH INCLUDES (in this exact order):
     * 1. plan_id (integer)
     * 2. config_snapshot_at (ISO8601 string)
     * 3. progressive_config (canonical JSON)
     * 4. milestone_config (canonical JSON)
     * 5. consistency_config (canonical JSON)
     * 6. welcome_bonus_config (canonical JSON)
     * 7. referral_tiers (canonical JSON)
     * 8. celebration_bonus_config (canonical JSON)
     * 9. lucky_draw_entries (canonical JSON)
     *
     * @return string 32-character SHA256 hash
     */
    private function computeCanonicalHash(
        int $planId,
        \DateTimeInterface $snapshotAt,
        ?array $progressiveConfig,
        ?array $milestoneConfig,
        ?array $consistencyConfig,
        ?array $welcomeBonusConfig,
        ?array $referralTiers,
        ?array $celebrationConfig,
        ?array $luckyDrawConfig
    ): string {
        // Build canonical data structure (fixed key order)
        // V-WAVE2-FIX: Normalize timestamp to second precision (MySQL timestamp doesn't store microseconds)
        // This ensures hash computed before save matches hash computed after database round-trip
        $normalizedTimestamp = \Carbon\Carbon::parse($snapshotAt)->setMicroseconds(0);

        $canonicalData = [
            'celebration_bonus_config' => $this->sortRecursively($celebrationConfig),
            'config_snapshot_at' => $normalizedTimestamp->format('Y-m-d\TH:i:s.000000P'), // ISO8601 with zero microseconds
            'consistency_config' => $this->sortRecursively($consistencyConfig),
            'lucky_draw_entries' => $this->sortRecursively($luckyDrawConfig),
            'milestone_config' => $this->sortRecursively($milestoneConfig),
            'plan_id' => $planId,
            'progressive_config' => $this->sortRecursively($progressiveConfig),
            'referral_tiers' => $this->sortRecursively($referralTiers),
            'welcome_bonus_config' => $this->sortRecursively($welcomeBonusConfig),
        ];

        // Sort top-level keys (already sorted above, but explicit for safety)
        ksort($canonicalData);

        // Canonical JSON: no whitespace, no unicode escaping, no slashes escaped
        $json = json_encode(
            $canonicalData,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION
        );

        return substr(hash('sha256', $json), 0, 32);
    }

    /**
     * V-CONTRACT-HARDENING-FINAL: Sort array recursively for canonical representation
     */
    private function sortRecursively(?array $data): ?array
    {
        if ($data === null) {
            return null;
        }

        // Check if this is an indexed array (list) or associative array
        if (array_keys($data) === range(0, count($data) - 1)) {
            // Indexed array - don't sort keys, but sort values if they're arrays
            return array_map(function ($item) {
                return is_array($item) ? $this->sortRecursively($item) : $item;
            }, $data);
        }

        // Associative array - sort by keys
        ksort($data);
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->sortRecursively($value);
            }
        }

        return $data;
    }

    /**
     * V-CONTRACT-HARDENING-FINAL: Verify subscription snapshot integrity
     *
     * @param Subscription $subscription The subscription to verify
     * @return bool True if config integrity is valid
     */
    public function verifyConfigIntegrity(Subscription $subscription): bool
    {
        $computedHash = $this->computeCurrentHash($subscription);
        $storedHash = $subscription->config_snapshot_version;

        $isValid = $computedHash === $storedHash;

        if (!$isValid) {
            Log::channel('financial_contract')->error('Contract integrity verification FAILED', [
                'subscription_id' => $subscription->id,
                'plan_id' => $subscription->plan_id,
                'stored_hash' => $storedHash,
                'computed_hash' => $computedHash,
                'alert' => 'POTENTIAL TAMPERING DETECTED',
            ]);
        }

        return $isValid;
    }

    /**
     * V-CONTRACT-HARDENING-FINAL: Compute current hash of subscription snapshot
     *
     * @param Subscription $subscription The subscription to hash
     * @return string The computed hash
     */
    public function computeCurrentHash(Subscription $subscription): string
    {
        return $this->computeCanonicalHash(
            $subscription->plan_id,
            $subscription->config_snapshot_at,
            $subscription->progressive_config,
            $subscription->milestone_config,
            $subscription->consistency_config,
            $subscription->welcome_bonus_config,
            $subscription->referral_tiers,
            $subscription->celebration_bonus_config,
            $subscription->lucky_draw_entries
        );
    }

    /**
     * V-CONTRACT-HARDENING-CORRECTIVE: Generate a preview snapshot from plan ID
     *
     * This method is used for bonus calculation previews WITHOUT creating a subscription.
     * It returns the same structure that would be snapshotted to a real subscription.
     *
     * IMPORTANT: This method loads plan internally - callers do NOT need to load plan.config.
     *
     * @param int $planId The plan ID to generate snapshot from
     * @return array The snapshot data as an associative array
     */
    public function generatePreviewSnapshot(int $planId): array
    {
        $plan = Plan::with('configs')->findOrFail($planId);

        return [
            'progressive_config' => $this->resolveAndValidateProgressiveConfig($plan),
            'milestone_config' => $this->resolveAndValidateMilestoneConfig($plan),
            'consistency_config' => $this->resolveAndValidateConsistencyConfig($plan),
            'welcome_bonus_config' => $this->resolveWelcomeBonusConfig($plan),
            'referral_tiers' => $this->resolveReferralTiers($plan),
            'celebration_bonus_config' => $this->resolveCelebrationConfig($plan),
            'lucky_draw_entries' => $this->resolveLuckyDrawConfig($plan),
        ];
    }

    /**
     * Resolve progressive bonus config with structure validation
     */
    private function resolveAndValidateProgressiveConfig(Plan $plan): array
    {
        $config = $plan->getConfig('progressive_config', self::DEFAULT_PROGRESSIVE_CONFIG);

        // Ensure required keys exist with proper types
        return [
            'rate' => (float) ($config['rate'] ?? self::DEFAULT_PROGRESSIVE_CONFIG['rate']),
            'start_month' => (int) ($config['start_month'] ?? self::DEFAULT_PROGRESSIVE_CONFIG['start_month']),
            'max_percentage' => (float) ($config['max_percentage'] ?? self::DEFAULT_PROGRESSIVE_CONFIG['max_percentage']),
            'overrides' => (array) ($config['overrides'] ?? []),
        ];
    }

    /**
     * Resolve milestone bonus config with structure validation
     */
    private function resolveAndValidateMilestoneConfig(Plan $plan): array
    {
        $config = $plan->getConfig('milestone_config', self::DEFAULT_MILESTONE_CONFIG);

        if (!is_array($config)) {
            return [];
        }

        // Validate each milestone entry
        $validated = [];
        foreach ($config as $milestone) {
            if (isset($milestone['month']) && isset($milestone['amount'])) {
                $validated[] = [
                    'month' => (int) $milestone['month'],
                    'amount' => (float) $milestone['amount'],
                ];
            }
        }

        return $validated;
    }

    /**
     * Resolve consistency bonus config with structure validation
     */
    private function resolveAndValidateConsistencyConfig(Plan $plan): array
    {
        $config = $plan->getConfig('consistency_config', self::DEFAULT_CONSISTENCY_CONFIG);

        $streaks = [];
        if (isset($config['streaks']) && is_array($config['streaks'])) {
            foreach ($config['streaks'] as $streak) {
                if (isset($streak['months']) && isset($streak['multiplier'])) {
                    $streaks[] = [
                        'months' => (int) $streak['months'],
                        'multiplier' => (float) $streak['multiplier'],
                    ];
                }
            }
        }

        return [
            'amount_per_payment' => (float) ($config['amount_per_payment'] ?? 0),
            'streaks' => $streaks,
        ];
    }

    /**
     * Resolve welcome bonus config
     */
    private function resolveWelcomeBonusConfig(Plan $plan): ?array
    {
        $config = $plan->getConfig('welcome_bonus_config');

        if ($config === null) {
            return self::DEFAULT_WELCOME_BONUS_CONFIG;
        }

        return [
            'amount' => (float) ($config['amount'] ?? self::DEFAULT_WELCOME_BONUS_CONFIG['amount']),
        ];
    }

    /**
     * Resolve referral tier config
     */
    private function resolveReferralTiers(Plan $plan): ?array
    {
        $config = $plan->getConfig('referral_config');

        if ($config === null || !isset($config['tiers'])) {
            return null;
        }

        $tiers = [];
        foreach ($config['tiers'] as $tier) {
            if (isset($tier['name']) && isset($tier['min_referrals']) && isset($tier['multiplier'])) {
                $tiers[] = [
                    'name' => (string) $tier['name'],
                    'min_referrals' => (int) $tier['min_referrals'],
                    'multiplier' => (float) $tier['multiplier'],
                ];
            }
        }

        return ['tiers' => $tiers];
    }

    /**
     * Resolve celebration bonus config
     */
    private function resolveCelebrationConfig(Plan $plan): ?array
    {
        $config = $plan->getConfig('celebration_bonus_config');

        if ($config === null) {
            return null;
        }

        return [
            'enabled' => (bool) ($config['enabled'] ?? false),
            'events' => (array) ($config['events'] ?? []),
        ];
    }

    /**
     * Resolve lucky draw config
     */
    private function resolveLuckyDrawConfig(Plan $plan): ?array
    {
        $config = $plan->getConfig('lucky_draw_config');

        if ($config === null) {
            return null;
        }

        return [
            'entries_per_payment' => (int) ($config['entries_per_payment'] ?? 0),
        ];
    }
}
