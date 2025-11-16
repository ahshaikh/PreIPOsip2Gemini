<?php
// V-FINAL-1730-242 (Created) | V-FINAL-1730-515 | V-FINAL-1730-518 (V2.0 Banners)

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Banner;
use App\Models\Redirect;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CmsController extends Controller
{
    // --- MENU MANAGEMENT (FSD-FRONT-003) ---
    public function getMenus()
    {
        return Menu::with('items')->get();
    }

    public function updateMenu(Request $request, Menu $menu)
    {
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.label' => 'required|string',
            'items.*.url' => 'required|string',
            'items.*.parent_id' => 'nullable|integer',
        ]);

        DB::transaction(function () use ($menu, $validated) {
            $menu->items()->delete();
            foreach ($validated['items'] as $index => $item) {
                $menu->items()->create([
                    'label' => $item['label'],
                    'url' => $item['url'],
                    'display_order' => $index,
                ]);
            }
        });

        return response()->json($menu->load('items'));
    }

    // --- BANNER MANAGEMENT (FSD-FRONT-021) ---
    public function getBanners()
    {
        return Banner::orderBy('display_order')->get();
    }

    public function storeBanner(Request $request)
    {
        // V2.0 Validation
        $validated = $request->validate([
            'title' => 'required|string',
            'type' => 'required|in:top_bar,popup',
            'content' => 'required|string',
            'link_url' => 'nullable|url',
            'is_active' => 'boolean',
            'trigger_type' => 'required|in:load,time_delay,scroll,exit_intent',
            'trigger_value' => 'required|integer|min:0',
            'frequency' => 'required|in:always,once_per_session,once_daily,once',
            'targeting_rules' => 'nullable|array',
            'style_config' => 'nullable|array',
        ]);
        
        $banner = Banner::create($validated);
        return response()->json($banner, 201);
    }

    public function updateBanner(Request $request, Banner $banner)
    {
        // V2.0 Update
        $validated = $request->validate([
            'title' => 'required|string',
            'type' => 'required|in:top_bar,popup',
            'content' => 'required|string',
            'link_url' => 'nullable|url',
            'is_active' => 'boolean',
            'trigger_type' => 'required|in:load,time_delay,scroll,exit_intent',
            'trigger_value' => 'required|integer|min:0',
            'frequency' => 'required|in:always,once_per_session,once_daily,once',
            'targeting_rules' => 'nullable|array',
            'style_config' => 'nullable|array',
        ]);
        
        $banner->update($validated);
        return response()->json($banner);
    }

    public function destroyBanner(Banner $banner)
    {
        $banner->delete();
        return response()->noContent();
    }

    // --- REDIRECT MANAGEMENT (FSD-SEO-004) ---
    public function getRedirects()
    {
        return Redirect::latest()->get();
    }

    public function storeRedirect(Request $request)
    {
        $validated = $request->validate([
            'from_url' => 'required|string|unique:redirects,from_url',
            'to_url' => 'required|string',
            'status_code' => 'required|in:301,302'
        ]);
        
        $redirect = Redirect::create($validated);
        return response()->json($redirect, 201);
    }

    public function destroyRedirect(Redirect $redirect)
    {
        $redirect->delete();
        return response()->noContent();
    }
}