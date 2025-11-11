// V-PHASE2-1730-048
<?php

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

if (!function_exists('setting')) {
    /**
     * Get a setting value from the database.
     * Caches the setting for performance.
     */
    function setting(string $key, $default = null)
    {
        return Cache::rememberForever('setting.' . $key, function () use ($key, $default) {
            $setting = Setting::where('key', $key)->first();
            if (!$setting) {
                return $default;
            }

            // Cast to correct type
            return match ($setting->type) {
                'boolean' => (bool) $setting->value,
                'number' => (int) $setting->value,
                'json' => json_decode($setting->value, true),
                default => $setting->value,
            };
        });
    }
}