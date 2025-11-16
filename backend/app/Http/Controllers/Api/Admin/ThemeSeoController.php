<?php
// V-FINAL-1730-243

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Artisan;

class ThemeSeoController extends Controller
{
    public function updateTheme(Request $request)
    {
        $validated = $request->validate([
            'primary_color' => 'nullable|string',
            'secondary_color' => 'nullable|string',
            'font_family' => 'nullable|string',
            'logo' => 'nullable|file|image|max:2048',
            'favicon' => 'nullable|file|image|max:512',
        ]);

        // Handle File Uploads
        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('branding', 'public');
            Setting::updateOrCreate(['key' => 'site_logo'], ['value' => $path, 'group' => 'theme']);
        }
        
        if ($request->hasFile('favicon')) {
            $path = $request->file('favicon')->store('branding', 'public');
            Setting::updateOrCreate(['key' => 'site_favicon'], ['value' => $path, 'group' => 'theme']);
        }

        // Handle Colors
        if ($request->primary_color) {
            Setting::updateOrCreate(['key' => 'theme_primary_color'], ['value' => $request->primary_color, 'group' => 'theme']);
        }
        
        // Clear Cache
        \Illuminate\Support\Facades\Cache::forget('settings');

        return response()->json(['message' => 'Theme updated successfully']);
    }

    public function updateSeo(Request $request)
    {
        $validated = $request->validate([
            'robots_txt' => 'nullable|string',
            'meta_title_suffix' => 'nullable|string',
            'google_analytics_id' => 'nullable|string',
        ]);

        foreach ($validated as $key => $value) {
            if ($value) {
                Setting::updateOrCreate(['key' => 'seo_' . $key], ['value' => $value, 'group' => 'seo']);
            }
        }

        return response()->json(['message' => 'SEO settings updated']);
    }
}