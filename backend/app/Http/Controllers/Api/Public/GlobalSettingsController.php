<?php
// V-FINAL-1730-244 (Created) | V-FINAL-1730-517 | FIX-1730-AUTLOAD-HELPER

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\Banner;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class GlobalSettingsController extends Controller
{
    /**
     * Safe setting fetcher that does NOT rely on the global setting() helper.
     */
    private function safeSetting(string $key, $default = null)
    {
        try {
            return Setting::where('key', $key)->value('value') ?? $default;
        } catch (\Exception $e) {
            return $default;
        }
    }

    /**
     * Get all public settings (Menus, Banners, Theme) in one call.
     */
    public function index()
    {
        return Cache::remember('global_settings', 60, function () {
            
            // 1. Get Menus
            $menus = Menu::with('items')->get()->keyBy('slug');
            
            // 2. Get Active Banners
            $banners = Banner::active()->orderBy('display_order')->get();
            
            // 3. Get Theme (using safe model access)
            $logo = $this->safeSetting('site_logo');

            return response()->json([
                'menus' => $menus,
                'banners' => $banners,
                'theme' => [
                    'logo_url' => $logo ? Storage::url($logo) : null,
                    'primary_color' => $this->safeSetting('theme_primary_color'),
                    'ga_id'        => $this->safeSetting('seo_google_analytics_id'),
                ]
            ]);
        });
    }
}
