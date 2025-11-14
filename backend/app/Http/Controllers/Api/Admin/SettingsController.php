<?php
// V-PHASE2-1730-058 (Created) | V-FINAL-1730-398 (Audit Trail Added)| V-FINAL-1730-401 (Cache Busting Verified)

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
        // Use a cached "all settings" query for the admin panel
        $allSettings = Cache::rememberForever('settings.all_grouped', function () {
             return Setting::all()->groupBy('group')->map(function ($group) {
                return $group->pluck('value', 'key');
            });
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
            'settings.*.key' => 'required|string|exists:settings,key',
            'settings.*.value' => 'nullable|string',
        ]);

        $adminId = $request->user()->id;

        foreach ($validated['settings'] as $settingData) {
            // Using update (not create) forces the 'saved' event
            // which will bust the individual 'setting.key' cache
            Setting::where('key', $settingData['key'])->update([
                'value' => $settingData['value'],
                'updated_by' => $adminId
            ]);
        }

        // Bust the high-level 'all settings' cache for the admin panel
        Cache::forget('settings.all_grouped');

        return response()->json(['message' => 'Settings updated successfully.']);
    }
}