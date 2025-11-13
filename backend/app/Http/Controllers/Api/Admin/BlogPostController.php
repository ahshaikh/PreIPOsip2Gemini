<?php
// V-FINAL-1730-189

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BlogPostController extends Controller
{
    public function index()
    {
        return BlogPost::latest()->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string',
            'content' => 'required|string',
            'status' => 'required|in:draft,published'
        ]);
        
        $post = BlogPost::create($validated + [
            'slug' => Str::slug($validated['title']),
            'author_id' => $request->user()->id
        ]);
        return response()->json($post, 201);
    }
    
    // Simple public getter for the frontend
    public function publicIndex() {
        return BlogPost::where('status', 'published')->latest()->get();
    }

    public function publicShow($slug) {
        return BlogPost::where('slug', $slug)->where('status', 'published')->firstOrFail();
    }
}