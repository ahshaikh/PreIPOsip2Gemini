<?php
// V-PHASE2-1730-059 (Created) | V-FINAL-1730-369 (Block Editor Logic)

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PageController extends Controller
{
    public function index()
    {
        return Page::latest()->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'required|string|unique:pages,slug',
            'content' => 'nullable|array', // Expecting an array of blocks
            'status' => 'required|in:draft,published',
        ]);
        
        $page = Page::create($validated);
        return response()->json($page, 201);
    }

    public function show(Page $page)
    {
        return $page;
    }

    public function update(Request $request, Page $page)
    {
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'content' => 'nullable|array', // Expecting an array of blocks
            'status' => 'sometimes|required|in:draft,published',
        ]);
        
        $page->update($validated);
        return response()->json($page);
    }

    public function destroy(Page $page)
    {
        $page->delete();
        return response()->noContent();
    }
}