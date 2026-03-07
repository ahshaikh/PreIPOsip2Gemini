<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SettingsService
{
    /**
     * Set a single setting value.
     */
    public function set(string $key, $value): Setting
    {
        return DB::transaction(function () use ($key, $value) {

            $setting = Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );

            $this->forgetCache($key);

            return $setting;
        });
    }

    /**
     * Bulk update multiple settings.
     *
     * Example:
     * SettingService->setMany([
     *     'campaign_enabled' => true,
     *     'bonus_percentage' => 5,
     * ]);
     */
    public function setMany(array $settings): void
    {
        DB::transaction(function () use ($settings) {

            foreach ($settings as $key => $value) {

                Setting::updateOrCreate(
                    ['key' => $key],
                    ['value' => $value]
                );

                $this->forgetCache($key);
            }

        });
    }

    /**
     * Delete a setting.
     */
    public function delete(string $key): void
    {
        DB::transaction(function () use ($key) {

            Setting::where('key', $key)->delete();

            $this->forgetCache($key);

        });
    }

    /**
     * Clear cached setting.
     */
    protected function forgetCache(string $key): void
    {
        Cache::forget("setting:{$key}");
    }
}