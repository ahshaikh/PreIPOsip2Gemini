<?php
// V-FINAL-1730-243 | V-SECURITY-FIX (FileUploadService, GA validation)

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\FileUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;

class ThemeSeoController extends Controller
{
    protected FileUploadService $fileUploadService;

    public function __construct(FileUploadService $fileUploadService)
    {
        $this->fileUploadService = $fileUploadService;
    }

    public function updateTheme(Request $request)
    {
        $validated = $request->validate([
            'primary_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'], // Hex color validation
            'secondary_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'font_family' => 'nullable|string|max:100',
            'logo' => 'nullable|file|image|mimes:png,jpg,jpeg,svg|max:2048',
            'favicon' => 'nullable|file|image|mimes:png,ico|max:512',
        ]);

        // Handle File Uploads using FileUploadService for security
        if ($request->hasFile('logo')) {
            // Delete old logo if exists
            $oldLogo = setting('site_logo');
            if ($oldLogo && Storage::disk('public')->exists($oldLogo)) {
                Storage::disk('public')->delete($oldLogo);
            }

            $path = $this->fileUploadService->upload(
                $request->file('logo'),
                'branding',
                ['encrypt' => false] // Logos don't need encryption
            );
            Setting::updateOrCreate(['key' => 'site_logo'], ['value' => $path, 'group' => 'theme']);
        }

        if ($request->hasFile('favicon')) {
            // Delete old favicon if exists
            $oldFavicon = setting('site_favicon');
            if ($oldFavicon && Storage::disk('public')->exists($oldFavicon)) {
                Storage::disk('public')->delete($oldFavicon);
            }

            $path = $this->fileUploadService->upload(
                $request->file('favicon'),
                'branding',
                ['encrypt' => false]
            );
            Setting::updateOrCreate(['key' => 'site_favicon'], ['value' => $path, 'group' => 'theme']);
        }

        // Handle Colors
        if ($request->primary_color) {
            Setting::updateOrCreate(['key' => 'theme_primary_color'], ['value' => $request->primary_color, 'group' => 'theme']);
        }

        if ($request->secondary_color) {
            Setting::updateOrCreate(['key' => 'theme_secondary_color'], ['value' => $request->secondary_color, 'group' => 'theme']);
        }

        if ($request->font_family) {
            Setting::updateOrCreate(['key' => 'theme_font_family'], ['value' => $request->font_family, 'group' => 'theme']);
        }

        // Clear Cache
        Cache::forget('settings');

        return response()->json(['message' => 'Theme updated successfully']);
    }

    public function updateSeo(Request $request)
    {
        $validated = $request->validate([
            'robots_txt' => 'nullable|string|max:5000',
            'meta_title_suffix' => 'nullable|string|max:100',
            // Google Analytics ID format: G-XXXXXXXXXX or UA-XXXXXXXX-X
            'google_analytics_id' => ['nullable', 'string', 'regex:/^(G-[A-Z0-9]{10}|UA-\d{4,10}-\d{1,4})$/'],
        ]);

        foreach ($validated as $key => $value) {
            if ($value !== null) {
                Setting::updateOrCreate(['key' => 'seo_' . $key], ['value' => $value, 'group' => 'seo']);
            }
        }

        // Clear Cache
        Cache::forget('settings');

        return response()->json(['message' => 'SEO settings updated']);
    }
}