<?php
// V-PERFORMANCE-CACHE - Centralized Caching Service

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CacheService
{
    /**
     * Cache TTL presets (in seconds)
     */
    const TTL_SHORT = 60;           // 1 minute
    const TTL_MEDIUM = 300;         // 5 minutes
    const TTL_LONG = 3600;          // 1 hour
    const TTL_DAY = 86400;          // 24 hours
    const TTL_FOREVER = null;       // Forever (until invalidated)

    /**
     * Cache key prefixes by category
     */
    const PREFIX_SETTINGS = 'settings:';
    const PREFIX_USER = 'user:';
    const PREFIX_PLAN = 'plan:';
    const PREFIX_PRODUCT = 'product:';
    const PREFIX_STATS = 'stats:';
    const PREFIX_REPORT = 'report:';

    /**
     * Get all application settings (cached)
     */
    public static function getSettings(): array
    {
        return Cache::rememberForever(self::PREFIX_SETTINGS . 'all', function () {
            return \App\Models\Setting::pluck('value', 'key')->toArray();
        });
    }

    /**
     * Get a single setting
     */
    public static function getSetting(string $key, mixed $default = null): mixed
    {
        $settings = self::getSettings();
        return $settings[$key] ?? $default;
    }

    /**
     * Invalidate settings cache
     */
    public static function invalidateSettings(): void
    {
        Cache::forget(self::PREFIX_SETTINGS . 'all');
        Log::info('Settings cache invalidated');
    }

    /**
     * Get user profile data (cached)
     */
    public static function getUserProfile(int $userId): ?array
    {
        $cacheKey = self::PREFIX_USER . "profile:{$userId}";

        return Cache::remember($cacheKey, self::TTL_MEDIUM, function () use ($userId) {
            $user = \App\Models\User::with(['wallet', 'subscription.plan'])
                ->find($userId);

            if (!$user) {
                return null;
            }

            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'kyc_status' => $user->kyc_status,
                'wallet_balance' => $user->wallet?->balance ?? 0,
                'subscription' => $user->subscription ? [
                    'plan_name' => $user->subscription->plan?->name,
                    'status' => $user->subscription->status,
                ] : null,
            ];
        });
    }

    /**
     * Invalidate user cache
     */
    public static function invalidateUser(int $userId): void
    {
        Cache::forget(self::PREFIX_USER . "profile:{$userId}");
        Cache::forget(self::PREFIX_USER . "portfolio:{$userId}");
        Cache::forget(self::PREFIX_USER . "stats:{$userId}");
    }

    /**
     * Get all active plans (cached)
     */
    public static function getActivePlans(): array
    {
        return Cache::remember(self::PREFIX_PLAN . 'active', self::TTL_LONG, function () {
            return \App\Models\Plan::where('is_active', true)
                ->orderBy('min_amount')
                ->get()
                ->toArray();
        });
    }

    /**
     * Get single plan
     */
    public static function getPlan(int $planId): ?array
    {
        $cacheKey = self::PREFIX_PLAN . "item:{$planId}";

        return Cache::remember($cacheKey, self::TTL_LONG, function () use ($planId) {
            $plan = \App\Models\Plan::find($planId);
            return $plan ? $plan->toArray() : null;
        });
    }

    /**
     * Invalidate plans cache
     */
    public static function invalidatePlans(): void
    {
        Cache::forget(self::PREFIX_PLAN . 'active');
        // Also clear individual plan caches
        $planIds = \App\Models\Plan::pluck('id');
        foreach ($planIds as $planId) {
            Cache::forget(self::PREFIX_PLAN . "item:{$planId}");
        }
    }

    /**
     * Get active products (cached)
     */
    public static function getActiveProducts(): array
    {
        return Cache::remember(self::PREFIX_PRODUCT . 'active', self::TTL_LONG, function () {
            return \App\Models\Product::where('status', 'active')
                ->orderBy('name')
                ->get()
                ->toArray();
        });
    }

    /**
     * Get product details
     */
    public static function getProduct(int $productId): ?array
    {
        $cacheKey = self::PREFIX_PRODUCT . "item:{$productId}";

        return Cache::remember($cacheKey, self::TTL_LONG, function () use ($productId) {
            $product = \App\Models\Product::find($productId);
            return $product ? $product->toArray() : null;
        });
    }

    /**
     * Invalidate products cache
     */
    public static function invalidateProducts(): void
    {
        Cache::forget(self::PREFIX_PRODUCT . 'active');
        $productIds = \App\Models\Product::pluck('id');
        foreach ($productIds as $productId) {
            Cache::forget(self::PREFIX_PRODUCT . "item:{$productId}");
        }
    }

    /**
     * Get dashboard statistics (cached)
     */
    public static function getDashboardStats(): array
    {
        return Cache::remember(self::PREFIX_STATS . 'dashboard', self::TTL_SHORT, function () {
            return [
                'total_users' => \App\Models\User::count(),
                'active_subscriptions' => \App\Models\Subscription::where('status', 'active')->count(),
                'pending_kyc' => \App\Models\KycDocument::where('status', 'pending')->count(),
                'pending_withdrawals' => \App\Models\Withdrawal::where('status', 'pending')->count(),
                'today_payments' => \App\Models\Payment::whereDate('created_at', today())
                    ->where('status', 'completed')
                    ->sum('amount'),
                'month_payments' => \App\Models\Payment::whereMonth('created_at', now()->month)
                    ->where('status', 'completed')
                    ->sum('amount'),
            ];
        });
    }

    /**
     * Invalidate dashboard stats
     */
    public static function invalidateDashboardStats(): void
    {
        Cache::forget(self::PREFIX_STATS . 'dashboard');
    }

    /**
     * Get or set with tags (for grouped invalidation)
     */
    public static function rememberWithTags(array $tags, string $key, int $ttl, callable $callback): mixed
    {
        // Tags are supported by Redis/Memcached
        if (in_array(config('cache.default'), ['redis', 'memcached'])) {
            return Cache::tags($tags)->remember($key, $ttl, $callback);
        }

        // Fallback for file/database cache
        return Cache::remember($key, $ttl, $callback);
    }

    /**
     * Flush cache by tag
     */
    public static function flushByTag(string $tag): void
    {
        if (in_array(config('cache.default'), ['redis', 'memcached'])) {
            Cache::tags([$tag])->flush();
            Log::info("Cache flushed for tag: {$tag}");
        }
    }

    /**
     * Clear all application cache
     */
    public static function clearAll(): void
    {
        Cache::flush();
        Log::info('All cache cleared');
    }

    /**
     * Get cache statistics (for monitoring)
     */
    public static function getStats(): array
    {
        $driver = config('cache.default');

        $stats = [
            'driver' => $driver,
            'prefix' => config('cache.prefix'),
        ];

        if ($driver === 'redis') {
            try {
                $redis = Cache::getRedis();
                $info = $redis->info();
                $stats['memory_used'] = $info['used_memory_human'] ?? 'N/A';
                $stats['connected_clients'] = $info['connected_clients'] ?? 'N/A';
                $stats['total_keys'] = $redis->dbSize();
            } catch (\Exception $e) {
                $stats['error'] = $e->getMessage();
            }
        }

        return $stats;
    }
}
