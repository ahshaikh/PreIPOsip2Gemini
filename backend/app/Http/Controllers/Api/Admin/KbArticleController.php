<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\KbArticle;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class KbArticleController extends Controller
{
    public function index()
    {
        return KbArticle::with('category:id,name', 'author:id,username')
                        ->latest()
                        ->paginate(25);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'summary' => 'nullable|string|max:500', // <--- Added validation
            'content' => 'required|string',
            'kb_category_id' => 'required|exists:kb_categories,id',
            'status' => 'required|in:draft,published',
            'last_updated' => 'nullable|date',       // <--- Added validation
            'seo_meta' => 'nullable|array',
        ]);
        
        $article = KbArticle::create($validated + [
            'author_id' => $request->user()->id,
            'slug' => Str::slug($validated['title']),
            'published_at' => $validated['status'] === 'published' ? now() : null, // <--- COMMA WAS MISSING HERE
            'last_updated' => $validated['last_updated'] ?? now(), // Default to today
        ]);

        return response()->json($article, 201);
    }

    public function show(KbArticle $kbArticle)
    {
        return $kbArticle;
    }

    public function update(Request $request, KbArticle $kbArticle)
    {
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'summary' => 'nullable|string|max:500',  // <--- Added
            'content' => 'sometimes|required|string',
            'kb_category_id' => 'sometimes|required|exists:kb_categories,id',
            'status' => 'sometimes|required|in:draft,published',
            'last_updated' => 'nullable|date',       // <--- Added
            'seo_meta' => 'nullable|array',
        ]);
        
        if (isset($validated['title'])) {
            $validated['slug'] = Str::slug($validated['title']);
        }
        
        if (isset($validated['status']) && $validated['status'] === 'published' && !$kbArticle->published_at) {
            $validated['published_at'] = now();
        }

        $kbArticle->update($validated);
        
        return response()->json($kbArticle);
    }

    public function destroy(KbArticle $kbArticle)
    {
        $kbArticle->delete();
        return response()->noContent();
    }
}