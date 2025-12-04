<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ArticleFeedback;
use Illuminate\Http\Request;

class HelpCenterController extends Controller
{
    public function storeFeedback(Request $request)
    {
        $validated = $request->validate([
            'article_id' => 'required|string',
            'is_helpful' => 'required|boolean',
            'comment'    => 'nullable|string|max:500',
        ]);

        ArticleFeedback::create([
            'article_id' => $validated['article_id'],
            'is_helpful' => $validated['is_helpful'],
            'comment'    => $validated['comment'] ?? null,
            'ip_address' => $request->ip(),
            'user_id'    => $request->user('sanctum')?->id, // Optional: if using Sanctum
        ]);

        return response()->json(['message' => 'Feedback recorded']);
    }
}