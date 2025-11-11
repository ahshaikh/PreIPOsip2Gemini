// V-PHASE2-1730-058
<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SettingsController extends Controller
{
    /**
     * Get all settings, grouped.
     */
    public function index()
    {
        $settings = Setting::all()->groupBy('group')->map(function ($group) {
            return $group->pluck('value', 'key');
        });
        
        return response()->json($settings);
    }

    /**
     * Update settings.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'settings' => 'required|array',
            'settings.*.key' => 'required|string|exists:settings,key',
            'settings.*.value' => 'nullable|string', // Simple update
        ]);

        foreach ($validated['settings'] as $settingData) {
            $setting = Setting::where('key', $settingData['key'])->first();
            if ($setting) {
                $setting->update(['value' => $settingData['value']]);
            }
        }

        // Bust the cache
        Cache::forget('settings');
        foreach ($validated['settings'] as $settingData) {
            Cache::forget('setting.' . $settingData['key']);
        }

        return response()->json(['message' => 'Settings updated successfully.']);
    }
}