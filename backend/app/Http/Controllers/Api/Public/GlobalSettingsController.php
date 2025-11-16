<?php
// V-FINAL-1730-244 (Created) | V-FINAL-1730-517 

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\Banner;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class GlobalSettingsController extends Controller
{
    /**
     * Get all public settings (Menus, Banners, Theme) in one call.
     */
    public function index()
    {
        // This response should be cached for 1-5 minutes in production
        return Cache::remember('global_settings', 60, function () {
            
            // 1. Get Menus
            $menus = Menu::with('items')->get()->keyBy('slug');
            
            // 2. Get Active Banners
            $banners = Banner::active()->orderBy('display_order')->get();
            
            // 3. Get Theme
            $logo = setting('site_logo');
            
            return response()->json([
                'menus' => $menus,
                'banners' => $banners,
                'theme' => [
                    'logo_url' => $logo ? Storage::url($logo) : null,
                    'primary_color' => setting('theme_primary_color'),
                    'ga_id' => setting('seo_google_analytics_id'),
                ]
            ]);
        });
    }
}