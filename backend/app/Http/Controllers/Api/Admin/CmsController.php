<?php
// V-FINAL-1730-242

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Banner;
use App\Models\Redirect;
use Illuminate\Http\Request;

class CmsController extends Controller
{
    // --- MENU MANAGEMENT ---
    public function getMenus()
    {
        return Menu::with('items')->get();
    }

    public function updateMenu(Request $request, Menu $menu)
    {
        // Expects a full replacement of items for simplicity in reordering
        $request->validate([
            'items' => 'array',
            'items.*.label' => 'required|string',
            'items.*.url' => 'required|string',
        ]);

        // Delete old items (simple strategy for full sync)
        $menu->items()->delete();

        // Re-create items
        foreach ($request->items as $index => $item) {
            $menu->items()->create([
                'label' => $item['label'],
                'url' => $item['url'],
                'display_order' => $index,
                // Parent/Child logic can be expanded here
            ]);
        }

        return response()->json($menu->load('items'));
    }

    // --- BANNER MANAGEMENT ---
    public function getBanners()
    {
        return Banner::orderBy('display_order')->get();
    }

    public function storeBanner(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string',
            'type' => 'required|in:top_bar,popup,slide',
            'content' => 'nullable|string',
            'link_url' => 'nullable|string',
            'is_active' => 'boolean'
        ]);
        
        $banner = Banner::create($validated);
        return response()->json($banner, 201);
    }

    public function updateBanner(Request $request, Banner $banner)
    {
        $banner->update($request->all());
        return response()->json($banner);
    }

    public function destroyBanner(Banner $banner)
    {
        $banner->delete();
        return response()->noContent();
    }

    // --- REDIRECT MANAGEMENT ---
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