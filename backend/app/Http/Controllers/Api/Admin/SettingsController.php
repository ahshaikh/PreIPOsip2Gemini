<?php
// V-PHASE2-1730-058 (Created) | V-FINAL-1730-398 (Audit Trail)| V-FINAL-1730-401 (Cache Busting) | V-FINAL-1730-490 (Full Object)

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SettingsController extends Controller
{
    /**
     * Get all settings, grouped, with full details (key, value, type).
     */
    public function index()
    {
        // We use a new cache key to store the full objects
        $allSettings = Cache::rememberForever('settings.all_grouped_full', function () {
             return Setting::all()->groupBy('group');
        });
        
        return response()->json($allSettings);
    }

    /**
     * Update settings.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'settings' => 'required|array',
            'settings.*.key' => 'required|string',
            'settings.*.value' => 'nullable|string',
        ]);

        $adminId = $request->user()->id;

        foreach ($validated['settings'] as $settingData) {
            // Update or create setting if it doesn't exist
            Setting::updateOrCreate(
                ['key' => $settingData['key']],
                [
                    'value' => $settingData['value'],
                    'updated_by' => $adminId,
                    // Set default values for new settings
                    'type' => $settingData['type'] ?? 'string',
                    'group' => $settingData['group'] ?? 'system',
                ]
            );

            // Bust individual key cache
            Cache::forget('setting.' . $settingData['key']);
        }

        // Bust all grouped caches
        Cache::forget('settings.all_grouped');
        Cache::forget('settings.all_grouped_full');

        return response()->json(['message' => 'Settings updated successfully.']);
    }
}
