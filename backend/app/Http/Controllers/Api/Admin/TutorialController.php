<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tutorial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class TutorialController extends Controller
{
    public function index(Request $request)
    {
        $query = Tutorial::query();

        if ($request->filled('difficulty')) {
            $query->byDifficulty($request->difficulty);
        }

        if ($request->filled('category')) {
            $query->byCategory($request->category);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $tutorials = $query->orderBy('sort_order')->orderBy('created_at', 'desc')
                           ->paginate($request->get('per_page', 15));

        return response()->json($tutorials);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'content' => 'nullable|string',
            'video_url' => 'nullable|url',
            'thumbnail' => 'nullable|string',
            'difficulty' => 'required|in:beginner,intermediate,advanced',
            'duration_minutes' => 'nullable|integer|min:1',
            'steps' => 'nullable|array',
            'resources' => 'nullable|array',
            'category' => 'nullable|string|max:255',
            'tags' => 'nullable|array',
            'sort_order' => 'nullable|integer',
            'status' => 'required|in:draft,published,archived',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $data['slug'] = Str::slug($data['title']) . '-' . Str::random(6);

        $tutorial = Tutorial::create($data);

        return response()->json([
            'message' => 'Tutorial created successfully',
            'tutorial' => $tutorial
        ], 201);
    }

    public function show($id)
    {
        $tutorial = Tutorial::findOrFail($id);
        return response()->json($tutorial);
    }

    public function update(Request $request, $id)
    {
        $tutorial = Tutorial::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'content' => 'nullable|string',
            'video_url' => 'nullable|url',
            'thumbnail' => 'nullable|string',
            'difficulty' => 'sometimes|required|in:beginner,intermediate,advanced',
            'duration_minutes' => 'nullable|integer|min:1',
            'steps' => 'nullable|array',
            'resources' => 'nullable|array',
            'category' => 'nullable|string|max:255',
            'tags' => 'nullable|array',
            'sort_order' => 'nullable|integer',
            'status' => 'sometimes|required|in:draft,published,archived',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        if (isset($data['title']) && $data['title'] !== $tutorial->title) {
            $data['slug'] = Str::slug($data['title']) . '-' . Str::random(6);
        }

        $tutorial->update($data);

        return response()->json([
            'message' => 'Tutorial updated successfully',
            'tutorial' => $tutorial
        ]);
    }

    public function destroy($id)
    {
        $tutorial = Tutorial::findOrFail($id);
        $tutorial->delete();

        return response()->json(['message' => 'Tutorial deleted successfully']);
    }
}
