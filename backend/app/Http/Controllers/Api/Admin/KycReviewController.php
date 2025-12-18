<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\UserKyc;
use App\Services\Kyc\KycStatusService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\JsonResponse;

/**
 * KycReviewController
 * * [AUDIT FIX]: Handles the administrative side of KYC with strict permission gating.
 */
class KycReviewController extends Controller
{
    public function __construct(protected KycStatusService $statusService) {}

    /**
     * Approve or Reject a KYC submission.
     */
    public function update(Request $request, UserKyc $kyc): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:approved,rejected',
            'remarks' => 'nullable|string|max:500'
        ]);

        $this->statusService->transitionStatus($kyc, $request->status, $request->remarks);

        return response()->json([
            'message' => "KYC has been successfully {$request->status}."
        ]);
    }

    /**
     * Admin view of document via Signed URL.
     */
    public function viewDocument(UserKyc $kyc, string $side): JsonResponse
    {
        // Ensure only authorized admins can call this (middleware handled)
        $path = ($side === 'front') ? $kyc->front_image_path : $kyc->back_image_path;

        if (!$path || !Storage::disk('private')->exists($path)) {
            return response()->json(['message' => 'File not found.'], 404);
        }

        // [AUDIT FIX]: Admins get a short-lived link to sensitive investor data.
        $url = Storage::disk('private')->temporaryUrl($path, now()->addMinutes(10));

        return response()->json(['url' => $url]);
    }
}