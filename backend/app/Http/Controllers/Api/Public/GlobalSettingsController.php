<?php
// V-FINAL-1730-244

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\Banner;
use App\Models\Setting;
use Illuminate\Http\Request;

class GlobalSettingsController extends Controller
{
    public function index()
    {
        return response()->json([
            'menus' => Menu::with('items')->get()->keyBy('slug'),
            'banners' => Banner::active()->orderBy('display_order')->get(),
            'theme' => [
                'logo' => setting('site_logo'),
                'primary_color' => setting('theme_primary_color'),
                'meta_title_suffix' => setting('seo_meta_title_suffix', '| PreIPO SIP'),
                'ga_id' => setting('seo_google_analytics_id'),
            ]
        ]);
    }
}