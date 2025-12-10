<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Redirect;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RedirectController extends Controller
{
    /**
     * Get all redirects with pagination
     */
    public function index(Request $request)
    {
        $query = Redirect::query();

        // Filter by status_code
        if ($request->has('status_code')) {
            $query->where('status_code', $request->input('status_code'));
        }

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Search
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('from_url', 'like', "%{$search}%")
                  ->orWhere('to_url', 'like', "%{$search}%");
            });
        }

        // Sort
        $sortBy = $request->input('sort_by', 'hit_count');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $perPage = $request->input('per_page', 20);

        if ($request->boolean('paginated', true)) {
            return response()->json($query->paginate($perPage));
        }

        return response()->json(['data' => $query->get()]);
    }

    /**
     * Create a new redirect
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'from_url' => [
                'required',
                'string',
                'max:500',
                'unique:redirects,from_url',
                'regex:/^\/[a-zA-Z0-9\/_-]*$/', // Must start with / and contain valid URL characters
            ],
            'to_url' => [
                'required',
                'string',
                'max:500',
            ],
            'status_code' => [
                'required',
                'integer',
                Rule::in([301, 302, 307, 308]), // Only allow valid redirect status codes
            ],
            'is_active' => 'boolean',
        ]);

        // Ensure from_url starts with /
        if (!str_starts_with($validated['from_url'], '/')) {
            $validated['from_url'] = '/' . $validated['from_url'];
        }

        // Trim trailing slashes for consistency
        $validated['from_url'] = rtrim($validated['from_url'], '/');

        // Validate to_url format (can be absolute or relative)
        if (!filter_var($validated['to_url'], FILTER_VALIDATE_URL) && !str_starts_with($validated['to_url'], '/')) {
            return response()->json([
                'message' => 'The to_url must be a valid URL or start with /',
            ], 422);
        }

        $redirect = Redirect::create($validated);

        return response()->json([
            'message' => 'Redirect created successfully',
            'data' => $redirect,
        ], 201);
    }

    /**
     * Get a single redirect
     */
    public function show(Redirect $redirect)
    {
        return response()->json(['data' => $redirect]);
    }

    /**
     * Update a redirect
     */
    public function update(Request $request, Redirect $redirect)
    {
        $validated = $request->validate([
            'from_url' => [
                'sometimes',
                'required',
                'string',
                'max:500',
                Rule::unique('redirects', 'from_url')->ignore($redirect->id),
                'regex:/^\/[a-zA-Z0-9\/_-]*$/',
            ],
            'to_url' => [
                'sometimes',
                'required',
                'string',
                'max:500',
            ],
            'status_code' => [
                'sometimes',
                'required',
                'integer',
                Rule::in([301, 302, 307, 308]),
            ],
            'is_active' => 'sometimes|boolean',
        ]);

        // Normalize from_url if provided
        if (isset($validated['from_url'])) {
            if (!str_starts_with($validated['from_url'], '/')) {
                $validated['from_url'] = '/' . $validated['from_url'];
            }
            $validated['from_url'] = rtrim($validated['from_url'], '/');
        }

        // Validate to_url if provided
        if (isset($validated['to_url'])) {
            if (!filter_var($validated['to_url'], FILTER_VALIDATE_URL) && !str_starts_with($validated['to_url'], '/')) {
                return response()->json([
                    'message' => 'The to_url must be a valid URL or start with /',
                ], 422);
            }
        }

        $redirect->update($validated);

        return response()->json([
            'message' => 'Redirect updated successfully',
            'data' => $redirect,
        ]);
    }

    /**
     * Delete a redirect
     */
    public function destroy(Redirect $redirect)
    {
        $redirect->delete();

        return response()->json([
            'message' => 'Redirect deleted successfully',
        ]);
    }

    /**
     * Bulk delete redirects
     */
    public function bulkDestroy(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:redirects,id',
        ]);

        $deleted = Redirect::whereIn('id', $validated['ids'])->delete();

        return response()->json([
            'message' => "{$deleted} redirect(s) deleted successfully",
            'deleted_count' => $deleted,
        ]);
    }

    /**
     * Toggle active status
     */
    public function toggleActive(Redirect $redirect)
    {
        $redirect->update(['is_active' => !$redirect->is_active]);

        return response()->json([
            'message' => 'Redirect status toggled successfully',
            'data' => $redirect,
        ]);
    }

    /**
     * Get redirect statistics
     */
    public function statistics()
    {
        $stats = [
            'total_redirects' => Redirect::count(),
            'active_redirects' => Redirect::where('is_active', true)->count(),
            'inactive_redirects' => Redirect::where('is_active', false)->count(),
            'total_hits' => Redirect::sum('hit_count'),
            'by_status_code' => [
                '301' => Redirect::where('status_code', 301)->count(),
                '302' => Redirect::where('status_code', 302)->count(),
                '307' => Redirect::where('status_code', 307)->count(),
                '308' => Redirect::where('status_code', 308)->count(),
            ],
            'top_redirects' => Redirect::orderBy('hit_count', 'desc')
                ->where('is_active', true)
                ->limit(10)
                ->get(['id', 'from_url', 'to_url', 'hit_count']),
            'unused_redirects' => Redirect::where('hit_count', 0)
                ->where('is_active', true)
                ->count(),
        ];

        return response()->json($stats);
    }

    /**
     * Test a redirect (without incrementing hit count)
     */
    public function test(Request $request)
    {
        $validated = $request->validate([
            'url' => 'required|string',
        ]);

        $url = $validated['url'];

        // Normalize URL
        if (!str_starts_with($url, '/')) {
            $url = '/' . $url;
        }
        $url = rtrim($url, '/');

        $redirect = Redirect::where('from_url', $url)
            ->where('is_active', true)
            ->first();

        if ($redirect) {
            return response()->json([
                'found' => true,
                'redirect' => $redirect,
                'message' => "Redirect found: {$redirect->from_url} → {$redirect->to_url} ({$redirect->status_code})",
            ]);
        }

        return response()->json([
            'found' => false,
            'message' => 'No redirect found for this URL',
        ]);
    }

    /**
     * Import redirects from CSV
     */
    public function import(Request $request)
    {
        $validated = $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:2048',
            'overwrite' => 'boolean',
        ]);

        $file = $request->file('file');
        $overwrite = $request->boolean('overwrite', false);

        $imported = 0;
        $skipped = 0;
        $errors = [];

        if (($handle = fopen($file->getRealPath(), 'r')) !== false) {
            // Skip header row
            $header = fgetcsv($handle);

            while (($data = fgetcsv($handle)) !== false) {
                if (count($data) < 3) {
                    $errors[] = "Invalid row: " . implode(',', $data);
                    continue;
                }

                $fromUrl = trim($data[0]);
                $toUrl = trim($data[1]);
                $statusCode = isset($data[2]) ? (int)$data[2] : 301;

                // Normalize from_url
                if (!str_starts_with($fromUrl, '/')) {
                    $fromUrl = '/' . $fromUrl;
                }
                $fromUrl = rtrim($fromUrl, '/');

                // Check if redirect already exists
                $existing = Redirect::where('from_url', $fromUrl)->first();

                if ($existing && !$overwrite) {
                    $skipped++;
                    continue;
                }

                try {
                    if ($existing && $overwrite) {
                        $existing->update([
                            'to_url' => $toUrl,
                            'status_code' => $statusCode,
                        ]);
                    } else {
                        Redirect::create([
                            'from_url' => $fromUrl,
                            'to_url' => $toUrl,
                            'status_code' => $statusCode,
                            'is_active' => true,
                        ]);
                    }
                    $imported++;
                } catch (\Exception $e) {
                    $errors[] = "Failed to import: {$fromUrl} - {$e->getMessage()}";
                }
            }

            fclose($handle);
        }

        return response()->json([
            'message' => 'Import completed',
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors,
        ]);
    }

    /**
     * Export redirects to CSV
     */
    public function export()
    {
        $redirects = Redirect::orderBy('from_url')->get();

        $csv = "From URL,To URL,Status Code,Hit Count,Is Active,Created At\n";

        foreach ($redirects as $redirect) {
            $csv .= sprintf(
                "%s,%s,%d,%d,%s,%s\n",
                $redirect->from_url,
                $redirect->to_url,
                $redirect->status_code,
                $redirect->hit_count,
                $redirect->is_active ? 'Yes' : 'No',
                $redirect->created_at->format('Y-m-d H:i:s')
            );
        }

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="redirects-' . date('Y-m-d') . '.csv"',
        ]);
    }

    /**
     * Reset hit counts
     */
    public function resetHitCounts(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'nullable|array',
            'ids.*' => 'exists:redirects,id',
        ]);

        if (isset($validated['ids'])) {
            Redirect::whereIn('id', $validated['ids'])->update(['hit_count' => 0]);
            $count = count($validated['ids']);
        } else {
            $count = Redirect::count();
            Redirect::query()->update(['hit_count' => 0]);
        }

        return response()->json([
            'message' => "Hit counts reset for {$count} redirect(s)",
        ]);
    }
}
