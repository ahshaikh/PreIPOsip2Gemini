<?php
// V-PHASE2-1730-058 (Created) | V-FINAL-1730-398 (Audit Trail)| V-FINAL-1730-401 (Cache Busting) | V-FINAL-1730-490 (Full Object) | V-AUDIT-FIX-ENCRYPTION

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

class SettingsController extends Controller
{
    /**
     * Get all settings, grouped, with full details (key, value, type).
     */
    public function index()
    {
        // [AUDIT FIX] We do NOT cache this view with secrets exposed.
        // We fetch fresh, masking secrets, to ensure admin UI doesn't leak them.
        
        $allSettings = Setting::all()->map(function ($setting) {
            // Mask encrypted values for display
            if ($setting->type === 'encrypted' && !empty($setting->value)) {
                // Note: accessing ->value triggers the accessor which decrypts it.
                // We want to verify it exists but send back a mask.
                $setting->value = '********'; 
            }
            return $setting;
        })->groupBy('group');
        
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
            'settings.*.type' => 'nullable|string' // Allow type overrides
        ]);

        $adminId = $request->user() ? $request->user()->id : null;

        foreach ($validated['settings'] as $settingData) {
            
            // FIX: Fetch existing type to validate input
            $existing = Setting::where('key', $settingData['key'])->first();
            $type = $settingData['type'] 
                ?? ($existing && $existing->type ? $existing->type : 'string');

            $value = $settingData['value'];

            // Skip update if value is the mask
            if ($type === 'encrypted' && $value === '********') {
                continue;
            }

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
                // [AUDIT FIX] Encrypt value if type is encrypted
                if ($type === 'encrypted') {
                    $value = Crypt::encryptString($value);
                }
            }

            // Update or create setting
            // Note: Cache busting is handled automatically by the Setting model's booted() method
            Setting::updateOrCreate(
                ['key' => $settingData['key']],
                [
                    'value' => (string) $value,
                    'updated_by' => $adminId,
                    'type' => $type,
                    'group' => $existing ? $existing->group : ($settingData['group'] ?? 'system'),
                ]
            );
        }

        // Bust all grouped caches
        Cache::forget('settings.all_grouped');
        Cache::forget('settings.all_grouped_full');

        return response()->json(['message' => 'Settings updated successfully.']);
    }

    /**
     * Update theme settings.
     */
    public function updateTheme(Request $request)
    {
        $validated = $request->validate([
            'primary_color' => ['nullable', 'string', 'regex:/^#([A-Fa-f0-9]{6})$/'],
            'secondary_color' => ['nullable', 'string', 'regex:/^#([A-Fa-f0-9]{6})$/'],
            'font_family' => ['nullable', 'string', 'max:100'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,svg', 'max:2048'],
            'favicon' => ['nullable', 'image', 'mimes:png', 'max:512'],
        ]);

        $adminId = $request->user() ? $request->user()->id : null;

        // Handle text settings
        $textSettings = [
            'primary_color' => 'theme_primary_color',
            'secondary_color' => 'theme_secondary_color',
            'font_family' => 'theme_font_family',
        ];

        foreach ($textSettings as $inputKey => $settingKey) {
            if ($request->has($inputKey)) {
                Setting::updateOrCreate(
                    ['key' => $settingKey],
                    [
                        'value' => $validated[$inputKey],
                        'updated_by' => $adminId,
                        'group' => 'theme',
                        'type' => 'string'
                    ]
                );
            }
        }

        // Handle file uploads
        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('theme', 'public');
            Setting::updateOrCreate(
                ['key' => 'site_logo'],
                [
                    'value' => $path,
                    'updated_by' => $adminId,
                    'group' => 'theme',
                    'type' => 'string'
                ]
            );
        }

        if ($request->hasFile('favicon')) {
            $path = $request->file('favicon')->store('theme', 'public');
            Setting::updateOrCreate(
                ['key' => 'site_favicon'],
                [
                    'value' => $path,
                    'updated_by' => $adminId,
                    'group' => 'theme',
                    'type' => 'string'
                ]
            );
        }

        Cache::forget('settings');
        Cache::forget('settings.all_grouped');

        return response()->json(['message' => 'Theme updated successfully']);
    }

    /**
     * Update SEO settings.
     */
    public function updateSeo(Request $request)
    {
        $validated = $request->validate([
            'robots_txt' => ['nullable', 'string', 'max:5000'],
            'meta_title_suffix' => ['nullable', 'string', 'max:100'],
            'google_analytics_id' => ['nullable', 'string', 'regex:/^(G-[A-Z0-9]{10}|UA-[0-9]{4,10}-[0-9]{1,2})$/'],
        ]);

        $adminId = $request->user() ? $request->user()->id : null;

        $seoSettings = [
            'robots_txt' => 'seo_robots_txt',
            'meta_title_suffix' => 'seo_meta_title_suffix',
            'google_analytics_id' => 'seo_google_analytics_id',
        ];

        $rawInput = json_decode($request->getContent(), true);

        foreach ($seoSettings as $inputKey => $settingKey) {
            if ($request->has($inputKey)) {
                $value = (isset($rawInput[$inputKey])) ? $rawInput[$inputKey] : $validated[$inputKey];

                Setting::updateOrCreate(
                    ['key' => $settingKey],
                    [
                        'value' => $value,
                        'updated_by' => $adminId,
                        'group' => 'seo',
                        'type' => 'string'
                    ]
                );
            }
        }

        Cache::forget('settings');
        Cache::forget('settings.all_grouped');

        return response()->json(['message' => 'SEO settings updated']);
    }
}