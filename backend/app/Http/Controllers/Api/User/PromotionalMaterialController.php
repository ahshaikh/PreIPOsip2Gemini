<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\PromotionalMaterial;
use App\Models\PromotionalMaterialDownload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PromotionalMaterialController extends Controller
{
    /**
     * Get all active promotional materials
     */
    public function index(Request $request)
    {
        $materials = PromotionalMaterial::where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($materials);
    }

    /**
     * Get download stats for the authenticated user
     */
    public function stats(Request $request)
    {
        $user = $request->user();

        $totalDownloads = PromotionalMaterialDownload::where('user_id', $user->id)->count();

        $lastDownload = PromotionalMaterialDownload::where('user_id', $user->id)
            ->latest()
            ->first();

        return response()->json([
            'total_downloads' => $totalDownloads,
            'last_download' => $lastDownload ? $lastDownload->created_at : null,
        ]);
    }

    /**
     * Track a download
     */
    public function trackDownload(Request $request, $materialId)
    {
        $user = $request->user();
        $material = PromotionalMaterial::findOrFail($materialId);

        DB::transaction(function () use ($user, $material, $request) {
            // Track the download
            PromotionalMaterialDownload::create([
                'user_id' => $user->id,
                'promotional_material_id' => $material->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            // Increment download count
            $material->incrementDownloadCount();
        });

        return response()->json([
            'success' => true,
            'message' => 'Download tracked successfully',
        ]);
    }
}
