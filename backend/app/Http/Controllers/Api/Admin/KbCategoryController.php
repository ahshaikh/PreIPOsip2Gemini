<?php
// V-FINAL-1730-654 (Created)

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\KbCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class KbCategoryController extends Controller
{
    /**
     * Display a listing of the KB Categories.
     */
    public function index()
    {
        // Return all categories, ordered by parent and display order
        return KbCategory::orderBy('parent_id')
                         ->orderBy('display_order')
                         ->get();
    }

    /**
     * Store a newly created KB Category.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|unique:kb_categories,slug',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:kb_categories,id',
            'icon' => 'nullable|string|max:50',
            'display_order' => 'integer|min:0',
        ]);

        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['name']);
        
        // Ensure slug is unique if auto-generated
        if (KbCategory::where('slug', $validated['slug'])->exists()) {
             $validated['slug'] = $validated['slug'] . '-' . Str::random(4);
        }

        $category = KbCategory::create($validated);
        
        return response()->json($category, 201);
    }

    /**
     * Display the specified KB Category.
     */
    public function show(KbCategory $kbCategory)
    {
        return response()->json($kbCategory->load('children'));
    }

    /**
     * Update the specified KB Category.
     */
    public function update(Request $request, KbCategory $kbCategory)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'slug' => 'nullable|string|unique:kb_categories,slug,' . $kbCategory->id,
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:kb_categories,id',
            'icon' => 'nullable|string|max:50',
            'display_order' => 'integer|min:0',
            'is_active' => 'boolean',
        ]);

        if (isset($validated['name']) && empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $kbCategory->update($validated);
        
        return response()->json($kbCategory);
    }

    /**
     * Remove the specified KB Category.
     */
    public function destroy(KbCategory $kbCategory)
    {
        if ($kbCategory->articles()->exists() || $kbCategory->children()->exists()) {
            return response()->json(['message' => 'Cannot delete category with articles or sub-categories.'], 409);
        }
        
        $kbCategory->delete();
        
        return response()->noContent();
    }
}