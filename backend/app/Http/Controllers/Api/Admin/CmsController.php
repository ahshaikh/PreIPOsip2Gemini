<?php
// V-FINAL-1730-242 (Created) | V-FINAL-1730-515 | V-FINAL-1730-518 (V2.0 Banners) | V-SECURITY-FIX (XSS Prevention) | V-AUDIT-MODULE12-002 (SafeUrl Rule) | V-PATCH-BANNER-FIX (Image & Scheduling)

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Banner;
use App\Models\Redirect;
use App\Rules\SafeUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage; // ADDED: Required for banner image handling
use Illuminate\Validation\Rule;

class CmsController extends Controller
{
    /**
     * Custom validation for safe URLs (blocks javascript: and data: protocols).
     *
     * V-AUDIT-MODULE12-002 (MEDIUM): Deprecated in favor of App\Rules\SafeUrl
     * @deprecated This method is kept for backward compatibility but should be replaced with SafeUrl rule class
     * @see App\Rules\SafeUrl
     */
    private function safeUrlRule(): array
    {
        // V-AUDIT-MODULE12-002: Use the new SafeUrl rule class instead
        return ['nullable', 'string', 'max:2048', new SafeUrl()];
    }

    // --- MENU MANAGEMENT (FSD-FRONT-003) | V-CMS-ENHANCEMENT-009 (Multi-level support)
    public function getMenus()
    {
        // Load menus with nested items (parent-child relationships)
        return Menu::with(['items.children'])->get();
    }

    /**
     * V-AUDIT-MODULE12-003 (HIGH): Non-destructive menu sync to preserve item IDs
     */
    public function updateMenu(Request $request, Menu $menu)
    {
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'nullable|integer', // For existing items
            'items.*.label' => 'required|string',
            'items.*.url' => ['required', ...$this->safeUrlRule()],
            'items.*.parent_id' => 'nullable|integer',
            'items.*.display_order' => 'nullable|integer',
        ]);

        DB::transaction(function () use ($menu, $validated) {
            // V-AUDIT-MODULE12-003: Non-destructive sync (Upsert strategy)
            // Previous Issue: menu->allItems()->delete() caused ID churn on every save,
            // breaking frontend references and fragmenting database indexes.
            //
            // Fix: Update existing items, create new ones, delete only missing ones.

            $submittedIds = collect($validated['items'])
                ->pluck('id')
                ->filter()
                ->toArray();

            // 1. Update or Create items
            foreach ($validated['items'] as $index => $item) {
                $data = [
                    'label' => $item['label'],
                    'url' => $item['url'],
                    'parent_id' => $item['parent_id'] ?? null,
                    'display_order' => $item['display_order'] ?? $index,
                ];

                if (!empty($item['id'])) {
                    // V-AUDIT-MODULE12-003: Update existing item (preserves ID)
                    MenuItem::where('id', $item['id'])
                        ->where('menu_id', $menu->id)
                        ->update($data);
                } else {
                    // V-AUDIT-MODULE12-003: Create new item
                    $menu->items()->create($data);
                }
            }

            // 2. Delete items that were removed from the request
            // V-AUDIT-MODULE12-003: Only delete items not present in submitted IDs
            if (!empty($submittedIds)) {
                $menu->allItems()
                    ->whereNotIn('id', $submittedIds)
                    ->delete();
            } else {
                // If no IDs submitted (all new items), delete all old items
                $menu->allItems()->delete();
            }
        });

        return response()->json($menu->load(['items.children']));
    }

    // --- BANNER MANAGEMENT (FSD-FRONT-021) ---
    public function getBanners()
    {
        // FIX: Ensure we sort by display_order for consistent frontend rendering
        return Banner::orderBy('display_order', 'asc')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function storeBanner(Request $request)
    {
        // V-PATCH-BANNER-FIX: Enhanced validation to support images and scheduling
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            // Expanded types to include standard image banners and sliders
            'type' => 'required|in:top_bar,popup,banner,slider', 
            // Content can be nullable if it's purely an image banner
            'content' => 'nullable|string', 
            'link_url' => $this->safeUrlRule(),
            'is_active' => 'boolean',
            'trigger_type' => 'nullable|in:load,time_delay,scroll,exit_intent',
            'trigger_value' => 'nullable|integer|min:0',
            'frequency' => 'nullable|in:always,once_per_session,once_daily,once',
            'targeting_rules' => 'nullable|array',
            'style_config' => 'nullable|array',
            // ADDED: Standard banner fields
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // Max 5MB
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'display_order' => 'nullable|integer',
        ]);
        
        $data = $validated;

        // V-PATCH-BANNER-FIX: Handle Image Upload
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('banners', 'public');
            $data['image_url'] = Storage::url($path);
        }

        // Set defaults for optional fields if missing
        $data['display_order'] = $request->display_order ?? 0;
        $data['is_active'] = $request->boolean('is_active', true);

        $banner = Banner::create($data);
        
        return response()->json($banner, 201);
    }

    public function updateBanner(Request $request, Banner $banner)
    {
        // V-PATCH-BANNER-FIX: Enhanced validation for updates
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'type' => 'sometimes|in:top_bar,popup,banner,slider',
            'content' => 'nullable|string',
            'link_url' => $this->safeUrlRule(),
            'is_active' => 'boolean',
            'trigger_type' => 'nullable|in:load,time_delay,scroll,exit_intent',
            'trigger_value' => 'nullable|integer|min:0',
            'frequency' => 'nullable|in:always,once_per_session,once_daily,once',
            'targeting_rules' => 'nullable|array',
            'style_config' => 'nullable|array',
            // ADDED: Standard banner fields
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'display_order' => 'nullable|integer',
        ]);
        
        $data = $validated;

        // V-PATCH-BANNER-FIX: Handle Image Replacement
        if ($request->hasFile('image')) {
            // 1. Delete old image if it exists to keep storage clean
            if ($banner->image_url) {
                // Extract relative path from URL (e.g., /storage/banners/img.jpg -> banners/img.jpg)
                $oldPath = str_replace('/storage/', '', $banner->image_url);
                if (Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
            }

            // 2. Store new image
            $path = $request->file('image')->store('banners', 'public');
            $data['image_url'] = Storage::url($path);
        }

        $banner->update($data);
        
        return response()->json($banner);
    }

    public function destroyBanner(Banner $banner)
    {
        // V-PATCH-BANNER-FIX: Cleanup image file before deletion
        if ($banner->image_url) {
            $oldPath = str_replace('/storage/', '', $banner->image_url);
            if (Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }
        }

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
            'from_url' => 'required|string|max:2048|unique:redirects,from_url',
            'to_url' => ['required', ...$this->safeUrlRule()],
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

    /**
     * PUBLIC ENDPOINT: Get active banners for the frontend
     * This must be excluded from admin auth middleware
     */
    public function getPublicBanners()
    {
        return Banner::where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('start_date')
                      ->orWhere('start_date', '<=', now());
            })
            ->where(function ($query) {
                $query->whereNull('end_date')
                      ->orWhere('end_date', '>=', now());
            })
            ->orderBy('display_order', 'asc')
            ->latest()
            ->get();
    }
}