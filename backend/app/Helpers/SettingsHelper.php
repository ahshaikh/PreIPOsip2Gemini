<?php
// V-PHASE2-1730-048 (Created) | V-FINAL-1730-400 (Caching Implemented)

// CRITICAL FIX: Namespace removed to ensure setting() is globally available.
// Do not add 'namespace App\Helpers;' here, as this file is loaded via require_once.

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

if (!function_exists('setting')) {
    /**
     * Get a setting value by key.
     *
     * This function is the core of the configurable system.
     * 1. It checks the cache for the key.
     * 2. If not found, it queries the DB *once*.
     * 3. It stores the result in the cache forever.
     * 4. It returns the value, cast to its proper type (bool, int, etc.).
     */
    function setting($key, $default = null)
    {
        $cacheKey = 'setting.' . $key;

        try {
            // Attempt to get from cache or database
            $setting = Cache::rememberForever($cacheKey, function () use ($key) {
                // Ensure the Setting model exists before querying
                if (!class_exists(Setting::class)) {
                    return null;
                }
                return Setting::where('key', $key)->first();
            });

            if (!$setting) {
                return $default; // Not found, return default
            }

            // Return the 'value' accessor, which handles casting
            return $setting->value;

        } catch (\Exception $e) {
            // Failsafe for migration/boot scenarios where DB isn't ready
            // We use a silent fail pattern here to prevent 500 errors on boot
            try {
                Log::error("Could not retrieve setting '{$key}': " . $e->getMessage());
            } catch (\Exception $logError) {
                // If logging fails (e.g. monolog not ready), simply ignore
            }
            return $default;
        }
    }
}