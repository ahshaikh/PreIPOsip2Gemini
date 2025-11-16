<?php
// V-FINAL-1730-554 (Created)

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
            'content' => 'required|string',
            'kb_category_id' => 'required|exists:kb_categories,id',
            'status' => 'required|in:draft,published',
            'seo_meta' => 'nullable|array',
        ]);
        
        $article = KbArticle::create($validated + [
            'author_id' => $request->user()->id,
            'slug' => Str::slug($validated['title']),
            'published_at' => $validated['status'] === 'published' ? now() : null
        ]);

        return response()->json($article, 201);
    }

    public function show(KbArticle $kbArticle)
    {
        // 'kbArticle' is the route model binding, e.g., /kb-articles/5
        return $kbArticle;
    }

    public function update(Request $request, KbArticle $kbArticle)
    {
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'content' => 'sometimes|required|string',
            'kb_category_id' => 'sometimes|required|exists:kb_categories,id',
            'status' => 'sometimes|required|in:draft,published',
            'seo_meta' => 'nullable|array',
        ]);
        
        if (isset($validated['title'])) {
            $validated['slug'] = Str::slug($validated['title']);
        }
        
        if ($validated['status'] === 'published' && !$kbArticle->published_at) {
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