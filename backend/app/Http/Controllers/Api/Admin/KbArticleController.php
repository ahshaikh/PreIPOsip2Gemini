<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\KbArticle;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class KbArticleController extends Controller
{
    public function index(Request $request)
    {
        $query = KbArticle::with('category:id,name', 'author:id,username');

        // Search functionality
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            });
        }

        // Filter by category
        if ($request->has('category_id')) {
            $query->where('kb_category_id', $request->input('category_id'));
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        // Sort by
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($request->input('per_page', 25));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'summary' => 'nullable|string|max:500',
            'content' => 'required|string',
            'kb_category_id' => 'required|exists:kb_categories,id',
            'status' => 'required|in:draft,published',
            'last_updated' => 'nullable|date',
            'seo_meta' => 'nullable|array',
        ]);

        $slug = Str::slug($validated['title']);
        if (KbArticle::where('slug', $slug)->exists()) {
            $slug .= '-' . time();
        }

        $article = KbArticle::create($validated + [
            'author_id' => $request->user()->id,
            'slug' => $slug,
            'published_at' => $validated['status'] === 'published' ? now() : null,
            'last_updated' => $validated['last_updated'] ?? now(),
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
            'summary' => 'nullable|string|max:500',
            'content' => 'sometimes|required|string',
            'kb_category_id' => 'sometimes|required|exists:kb_categories,id',
            'status' => 'sometimes|required|in:draft,published',
            'last_updated' => 'nullable|date',
            'seo_meta' => 'nullable|array',
        ]);

        if (isset($validated['title']) && $validated['title'] !== $kbArticle->title) {
            $slug = Str::slug($validated['title']);
            if (KbArticle::where('slug', $slug)->where('id', '!=', $kbArticle->id)->exists()) {
                $slug .= '-' . time();
            }
            $validated['slug'] = $slug;
        }

        $kbArticle->update($validated);

        return response()->json($kbArticle);
    }

    public function destroy(KbArticle $kbArticle)
    {
        // Wrap in transaction: Either everything deletes, or nothing does.
        // This prevents partial deletes and silent failures.
        DB::transaction(function () use ($kbArticle) {
            // Force delete related records
            $kbArticle->views()->delete();
            $kbArticle->feedback()->delete();

            // Delete the article
            $kbArticle->delete();
        });

        return response()->noContent();
    }
}
