<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Carbon;

// Models (Dynamic check in logic)
use App\Models\Faq;

class FaqController extends Controller
{
    /**
     * List all FAQs
     * Endpoint: GET /api/v1/admin/faqs
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = DB::table('faqs');

            // Filters
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where('question', 'like', "%{$search}%")
                      ->orWhere('answer', 'like', "%{$search}%");
            }

            if ($request->filled('category') && $request->category !== 'all') {
                $query->where('category', $request->category);
            }

            // FIX: Use get() instead of paginate() to return a flat Array
            // The frontend expects [...] but paginate() returns { data: [...] } which causes .filter() to fail.
            $faqs = $query->orderBy('display_order', 'asc')
                          ->latest()
                          ->get();

            // Use map() for Collections
            $data = $faqs->map(function ($faq) {
                return [
                    'id' => $faq->id,
                    'question' => $faq->question,
                    'answer' => $faq->answer, // Assuming simple text or HTML
                    'category' => $faq->category ?? 'General',
                    'display_order' => $faq->display_order,
                    'is_published' => (bool) $faq->is_published,
                    'created_at' => $this->safeDate($faq->created_at),
                    'updated_at' => $this->safeDate($faq->updated_at),
                ];
            });

            return response()->json($data);

        } catch (\Throwable $e) {
            Log::error("FAQ Index Failed: " . $e->getMessage());
            // Return empty array to prevent frontend crash
            return response()->json([]);
        }
    }

    /**
     * Store new FAQ
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'question' => 'required|string|max:255',
            'answer' => 'required|string',
            'category' => 'required|string',
            'display_order' => 'integer',
            'is_published' => 'boolean'
        ]);

        try {
            $id = DB::table('faqs')->insertGetId(array_merge($validated, [
                'created_at' => now(),
                'updated_at' => now()
            ]));

            return response()->json(['message' => 'FAQ created', 'id' => $id], 201);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Failed to create FAQ: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Show single FAQ
     */
    public function show($id): JsonResponse
    {
        try {
            $faq = DB::table('faqs')->where('id', $id)->first();

            if (!$faq) {
                return response()->json(['message' => 'FAQ not found'], 404);
            }

            return response()->json(['data' => $faq]);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Update FAQ
     */
    public function update(Request $request, $id): JsonResponse
    {
        $validated = $request->validate([
            'question' => 'sometimes|required|string|max:255',
            'answer' => 'sometimes|required|string',
            'category' => 'sometimes|required|string',
            'display_order' => 'integer',
            'is_published' => 'boolean'
        ]);

        try {
            $updated = DB::table('faqs')->where('id', $id)->update(array_merge($validated, [
                'updated_at' => now()
            ]));

            if ($updated === 0) {
                return response()->json(['message' => 'FAQ not found or no changes made'], 404);
            }

            return response()->json(['message' => 'FAQ updated successfully']);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete FAQ
     */
    public function destroy($id): JsonResponse
    {
        try {
            $deleted = DB::table('faqs')->where('id', $id)->delete();
            
            if ($deleted) {
                return response()->json(['message' => 'FAQ deleted']);
            }
            return response()->json(['message' => 'FAQ not found'], 404);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    private function safeDate($date)
    {
        if (empty($date)) return '-';
        try {
            return Carbon::parse($date)->format('d M Y');
        } catch (\Throwable $e) {
            return '-';
        }
    }
}