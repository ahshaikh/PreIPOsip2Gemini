<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ContentReportController extends Controller
{
    public function index(Request $request)
    {
        $query = Report::query();

        if ($request->filled('type')) {
            $query->byType($request->type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('access_level')) {
            $query->where('access_level', $request->access_level);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('author', 'like', "%{$search}%");
            });
        }

        $reports = $query->orderBy('published_date', 'desc')
                         ->paginate($request->get('per_page', 15));

        return response()->json($reports);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:market_analysis,research,white_paper,case_study,guide',
            'file_path' => 'required|string',
            'cover_image' => 'nullable|string',
            'file_size' => 'nullable|integer|min:0',
            'pages' => 'nullable|integer|min:0',
            'access_level' => 'required|in:public,registered,premium,admin',
            'requires_subscription' => 'boolean',
            'author' => 'nullable|string|max:255',
            'published_date' => 'nullable|date',
            'tags' => 'nullable|array',
            'status' => 'required|in:draft,published,archived',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $data['slug'] = Str::slug($data['title']) . '-' . Str::random(6);

        $report = Report::create($data);

        return response()->json([
            'message' => 'Report created successfully',
            'report' => $report
        ], 201);
    }

    public function show($id)
    {
        $report = Report::findOrFail($id);
        return response()->json($report);
    }

    public function update(Request $request, $id)
    {
        $report = Report::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'sometimes|required|in:market_analysis,research,white_paper,case_study,guide',
            'file_path' => 'sometimes|required|string',
            'cover_image' => 'nullable|string',
            'file_size' => 'nullable|integer|min:0',
            'pages' => 'nullable|integer|min:0',
            'access_level' => 'sometimes|required|in:public,registered,premium,admin',
            'requires_subscription' => 'boolean',
            'author' => 'nullable|string|max:255',
            'published_date' => 'nullable|date',
            'tags' => 'nullable|array',
            'status' => 'sometimes|required|in:draft,published,archived',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        if (isset($data['title']) && $data['title'] !== $report->title) {
            $data['slug'] = Str::slug($data['title']) . '-' . Str::random(6);
        }

        $report->update($data);

        return response()->json([
            'message' => 'Report updated successfully',
            'report' => $report
        ]);
    }

    public function destroy($id)
    {
        $report = Report::findOrFail($id);
        $report->delete();

        return response()->json(['message' => 'Report deleted successfully']);
    }
}
