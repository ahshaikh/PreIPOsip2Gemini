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
            'settings.*.value' => 'nullable', // Value type checked manually below
        ]);

        $adminId = $request->user()->id;

        foreach ($validated['settings'] as $settingData) {
            
            // FIX: Fetch existing type to validate input
            $existing = Setting::where('key', $settingData['key'])->first();
            $type = $existing ? $existing->type : ($settingData['type'] ?? 'string');

            $value = $settingData['value'];

            // Type Validation Logic
            if ($value !== null) {
                if ($type === 'number' && !is_numeric($value)) {
                    return response()->json(['error' => "Setting '{$settingData['key']}' must be a number."], 422);
                }
                if ($type === 'boolean' && !in_array($value, ['true', 'false', '1', '0', true, false], true)) {
                    return response()->json(['error' => "Setting '{$settingData['key']}' must be a boolean."], 422);
                }
                if ($type === 'json') {
                    json_decode($value);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        return response()->json(['error' => "Setting '{$settingData['key']}' must be valid JSON."], 422);
                    }
                }
            }

            // Update or create setting
            Setting::updateOrCreate(
                ['key' => $settingData['key']],
                [
                    'value' => (string) $value,
                    'updated_by' => $adminId,
                    'type' => $type,
                    'group' => $existing ? $existing->group : ($settingData['group'] ?? 'system'),
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