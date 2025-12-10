<?php
// V-CMS-ENHANCEMENT-012 | PageBlockController
// Created: 2025-12-10 | Purpose: CRUD operations for page blocks (block-based page builder)

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\PageBlock;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PageBlockController extends Controller
{
    /**
     * Get all blocks for a specific page
     * GET /api/v1/admin/pages/{page}/blocks
     */
    public function index(Page $page): JsonResponse
    {
        $blocks = $page->blocks()->ordered()->get();

        return response()->json([
            'page' => $page,
            'blocks' => $blocks,
        ]);
    }

    /**
     * Get available block types with configurations
     * GET /api/v1/admin/page-blocks/types
     */
    public function getBlockTypes(): JsonResponse
    {
        $types = PageBlock::getAvailableBlockTypes();
        $configurations = [];

        foreach ($types as $type) {
            $configurations[$type] = PageBlock::getBlockTypeConfig($type);
        }

        return response()->json([
            'types' => $types,
            'configurations' => $configurations,
        ]);
    }

    /**
     * Create a new block for a page
     * POST /api/v1/admin/pages/{page}/blocks
     */
    public function store(Request $request, Page $page): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'string', Rule::in(PageBlock::getAvailableBlockTypes())],
            'name' => 'nullable|string|max:255',
            'config' => 'required|array',
            'display_order' => 'nullable|integer|min:0',
            'container_width' => 'nullable|in:full,boxed,narrow',
            'background_type' => 'nullable|in:none,color,gradient,image',
            'background_config' => 'nullable|array',
            'spacing' => 'nullable|array',
            'is_active' => 'nullable|boolean',
            'visibility' => 'nullable|in:always,desktop_only,mobile_only',
        ]);

        // Set default display_order to end of list
        if (!isset($validated['display_order'])) {
            $validated['display_order'] = $page->blocks()->max('display_order') + 1;
        }

        $block = $page->blocks()->create($validated);

        return response()->json([
            'message' => 'Block created successfully',
            'data' => $block
        ], 201);
    }

    /**
     * Get a single block
     * GET /api/v1/admin/page-blocks/{block}
     */
    public function show(PageBlock $pageBlock): JsonResponse
    {
        $pageBlock->load('page:id,title,slug');

        return response()->json([
            'data' => $pageBlock
        ]);
    }

    /**
     * Update a block
     * PUT/PATCH /api/v1/admin/page-blocks/{block}
     */
    public function update(Request $request, PageBlock $pageBlock): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['sometimes', 'required', 'string', Rule::in(PageBlock::getAvailableBlockTypes())],
            'name' => 'nullable|string|max:255',
            'config' => 'sometimes|required|array',
            'display_order' => 'nullable|integer|min:0',
            'container_width' => 'nullable|in:full,boxed,narrow',
            'background_type' => 'nullable|in:none,color,gradient,image',
            'background_config' => 'nullable|array',
            'spacing' => 'nullable|array',
            'is_active' => 'nullable|boolean',
            'visibility' => 'nullable|in:always,desktop_only,mobile_only',
        ]);

        $pageBlock->update($validated);

        return response()->json([
            'message' => 'Block updated successfully',
            'data' => $pageBlock->fresh()
        ]);
    }

    /**
     * Delete a block
     * DELETE /api/v1/admin/page-blocks/{block}
     */
    public function destroy(PageBlock $pageBlock): JsonResponse
    {
        $pageBlock->delete();

        return response()->json([
            'message' => 'Block deleted successfully'
        ]);
    }

    /**
     * Reorder blocks for a page
     * POST /api/v1/admin/pages/{page}/blocks/reorder
     */
    public function reorder(Request $request, Page $page): JsonResponse
    {
        $validated = $request->validate([
            'blocks' => 'required|array',
            'blocks.*.id' => 'required|exists:page_blocks,id',
            'blocks.*.display_order' => 'required|integer|min:0',
        ]);

        DB::transaction(function () use ($validated, $page) {
            foreach ($validated['blocks'] as $blockData) {
                $page->blocks()
                    ->where('id', $blockData['id'])
                    ->update(['display_order' => $blockData['display_order']]);
            }
        });

        return response()->json([
            'message' => 'Blocks reordered successfully',
            'blocks' => $page->blocks()->ordered()->get()
        ]);
    }

    /**
     * Duplicate a block
     * POST /api/v1/admin/page-blocks/{block}/duplicate
     */
    public function duplicate(PageBlock $pageBlock): JsonResponse
    {
        $newBlock = $pageBlock->replicate();
        $newBlock->name = ($pageBlock->name ?? 'Block') . ' (Copy)';
        $newBlock->display_order = $pageBlock->page->blocks()->max('display_order') + 1;
        $newBlock->save();

        return response()->json([
            'message' => 'Block duplicated successfully',
            'data' => $newBlock
        ], 201);
    }

    /**
     * Toggle block active status
     * POST /api/v1/admin/page-blocks/{block}/toggle
     */
    public function toggle(PageBlock $pageBlock): JsonResponse
    {
        $pageBlock->update(['is_active' => !$pageBlock->is_active]);

        return response()->json([
            'message' => $pageBlock->is_active ? 'Block activated' : 'Block deactivated',
            'data' => $pageBlock
        ]);
    }

    /**
     * Get block analytics
     * GET /api/v1/admin/page-blocks/{block}/analytics
     */
    public function analytics(PageBlock $pageBlock): JsonResponse
    {
        return response()->json([
            'block_id' => $pageBlock->id,
            'views' => $pageBlock->views_count,
            'clicks' => $pageBlock->clicks_count,
            'ctr' => $pageBlock->views_count > 0
                ? round(($pageBlock->clicks_count / $pageBlock->views_count) * 100, 2)
                : 0,
        ]);
    }
}
