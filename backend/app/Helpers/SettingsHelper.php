<?php
// V-PHASE2-1730-048 (Created) | V-FINAL-1730-400 (Caching Implemented)

// debug: write marker when helper is included (remove after)
@file_put_contents(__DIR__ . '/../../storage/logs/helper_loaded.log', date('c') . " helper included\n", FILE_APPEND);


use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

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
            $setting = Cache::rememberForever($cacheKey, function () use ($key) {
                return Setting::where('key', $key)->first();
            });

            if (!$setting) {
                return $default; // Not found, return default
            }

            // Return the 'value' accessor, which handles casting
            return $setting->value;

        } catch (\Exception $e) {
            // DB might not be ready (e.g., during migration)
            \Illuminate\Support\Facades\Log::error("Could not retrieve setting '{$key}': " . $e->getMessage());
            return $default;
        }
    }
}