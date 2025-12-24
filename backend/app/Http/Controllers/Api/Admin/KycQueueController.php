<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\UserKyc;
use App\Models\KycDocument;
use App\Models\KycRejectionTemplate;
use App\Notifications\KycVerified;
use App\Enums\KycStatus; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
// [NOTE]: We do NOT import SettingsHelper because it is a global function file, not a class.

class KycQueueController extends Controller
{
    /**
     * Get KYC queue with enhanced filtering and search.
     * Endpoint: GET /api/admin/kyc-queue
     */
    public function index(Request $request)
    {
        // Default to 'all' to show both processing and submitted
        $status = $request->query('status', 'all');
        $search = $request->query('search');
        $priority = $request->query('priority');

        // [FIX] Eager load 'mobile' instead of 'phone' to prevent SQL Error 1054
        $query = UserKyc::query()->with('user:id,username,email,mobile');

        // [FIX] Status Filtering Logic
        // Default shows all pending KYCs (processing + submitted)
        // If specific status requested, filter by it
        if ($status && $status !== 'all') {
            // If 'pending' requested, show both processing and submitted
            if ($status === 'pending') {
                $query->whereIn('status', [KycStatus::PROCESSING->value, KycStatus::SUBMITTED->value]);
            } else {
                $query->where('status', $status);
            }
        } else {
            // Default: Show pending KYCs only (exclude verified/rejected)
            $query->whereIn('status', [KycStatus::PROCESSING->value, KycStatus::SUBMITTED->value]);
        }

        // Search filter (username, email, ID, and MOBILE)
        if ($search) {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('username', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('mobile', 'like', "%{$search}%") 
                  ->orWhere('id', $search);
            });
        }

        // Priority filter (High priority = submitted more than 24h ago)
        if ($priority === 'high') {
            $query->where('submitted_at', '<', now()->subHours(24))
                  ->where('status', KycStatus::SUBMITTED->value);
        }

        // [FIX]: Dynamic Pagination Limit
        // We use the global setting() helper function defined in app/Helpers/SettingsHelper.php
        $perPage = (int) setting('records_per_page', 15);

        // Standard ordering: Newest submissions first
        $kycSubmissions = $query->latest('submitted_at')->paginate($perPage);

        return response()->json($kycSubmissions);
    }

    /**
     * Get detailed KYC submission with documents and notes
     * Endpoint: GET /api/admin/kyc-queue/{id}
     */
    public function show($id)
    {
        $kyc = UserKyc::with(['user.profile', 'documents', 'verificationNotes.admin'])
            ->findOrFail($id);
        return response()->json($kyc);
    }

    /**
     * Approve KYC with verification checklist
     * Endpoint: POST /api/admin/kyc-queue/{id}/approve
     */
    public function approve(Request $request, $id)
    {
        $request->validate([
            'verification_checklist' => 'sometimes|array',
            'notes' => 'sometimes|string|max:1000',
        ]);

        $kyc = UserKyc::with('user')->findOrFail($id);
        $admin = $request->user();

        // Allow approval of both 'processing' and 'submitted' statuses
        if (!in_array($kyc->status, [KycStatus::PROCESSING->value, KycStatus::SUBMITTED->value])) {
            return response()->json(['message' => 'This submission is not pending approval.'], 400);
        }

        DB::transaction(function () use ($kyc, $admin, $request) {
            $kyc->update([
                'status' => KycStatus::VERIFIED->value,
                'verified_at' => now(),
                'verified_by' => $admin->id,
                'rejection_reason' => null,
                'verification_checklist' => $request->verification_checklist,
            ]);

            if ($request->notes) {
                $kyc->verificationNotes()->create([
                    'admin_id' => $admin->id,
                    'note' => $request->notes,
                ]);
            }

            $kyc->documents()->update(['status' => 'approved']);
        });

        try {
            $kyc->user->notify(new KycVerified());
        } catch (\Exception $e) {
            \Log::error("Failed to send notification: " . $e->getMessage());
        }

        $this->invalidateStatisticsCache();

        return response()->json(['message' => 'KYC approved successfully.']);
    }

    /**
     * Reject KYC with reason and checklist
     * Endpoint: POST /api/admin/kyc-queue/{id}/reject
     */
    public function reject(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|min:10|max:500',
            'verification_checklist' => 'sometimes|array',
        ]);

        $kyc = UserKyc::findOrFail($id);
        $admin = $request->user();

        DB::transaction(function () use ($kyc, $admin, $request) {
            $kyc->update([
                'status' => KycStatus::REJECTED->value,
                'rejection_reason' => $request->reason,
                'verified_at' => null,
                'verified_by' => $admin->id,
                'verification_checklist' => $request->verification_checklist,
            ]);

            $kyc->verificationNotes()->create([
                'admin_id' => $admin->id,
                'note' => 'REJECTED: ' . $request->reason,
            ]);
        });

        $this->invalidateStatisticsCache();

        return response()->json(['message' => 'KYC rejected successfully.']);
    }

    /**
     * Request resubmission with instructions
     * Endpoint: POST /api/admin/kyc-queue/{id}/request-resubmission
     */
    public function requestResubmission(Request $request, $id)
    {
        $request->validate([
            'instructions' => 'required|string|min:10|max:1000',
            'verification_checklist' => 'sometimes|array',
        ]);

        $kyc = UserKyc::findOrFail($id);
        $admin = $request->user();

        DB::transaction(function () use ($kyc, $admin, $request) {
            $kyc->update([
                'status' => KycStatus::RESUBMISSION_REQUIRED->value,
                'resubmission_instructions' => $request->instructions,
            ]);

            $kyc->verificationNotes()->create([
                'admin_id' => $admin->id,
                'note' => 'Resubmission requested: ' . $request->instructions,
            ]);
        });

        $this->invalidateStatisticsCache();

        return response()->json(['message' => 'Resubmission request sent successfully.']);
    }

    /**
     * Add verification note
     * Endpoint: POST /api/admin/kyc-queue/{id}/notes
     */
    public function addNote(Request $request, $id)
    {
        $request->validate([
            'note' => 'required|string|min:5|max:1000',
        ]);

        $kyc = UserKyc::findOrFail($id);
        $note = $kyc->verificationNotes()->create([
            'admin_id' => $request->user()->id,
            'note' => $request->note,
        ]);

        return response()->json([
            'message' => 'Note added successfully',
            'note' => $note->load('admin'),
        ]);
    }

    /**
     * Get KYC statistics
     * Endpoint: GET /api/admin/kyc-queue/stats
     */
    public function statistics(Request $request)
    {
        $days = $request->query('days', 30);
        $startDate = now()->subDays($days);
        $cacheKey = "kyc_statistics_{$days}days";

        return response()->json(Cache::remember($cacheKey, 600, function () use ($startDate) {
            $processingCount = UserKyc::where('status', KycStatus::PROCESSING->value)->count();
            $submittedCount = UserKyc::where('status', KycStatus::SUBMITTED->value)->count();

            return [
                'total_submissions' => UserKyc::where('created_at', '>=', $startDate)->count(),
                'pending_review' => $processingCount + $submittedCount, // Combined count for "Pending Review"
                'processing' => $processingCount,
                'submitted' => $submittedCount,
                'verified' => UserKyc::where('status', KycStatus::VERIFIED->value)->where('created_at', '>=', $startDate)->count(),
                'rejected' => UserKyc::where('status', KycStatus::REJECTED->value)->where('created_at', '>=', $startDate)->count(),
                'total' => UserKyc::count(),
            ];
        }));
    }
    
    // Alias to match route name if needed
    public function stats(Request $request) { return $this->statistics($request); }

    public function getRejectionTemplates()
    {
        $templates = KycRejectionTemplate::where('is_active', true)->get();
        return response()->json(['data' => $templates]);
    }

    private function invalidateStatisticsCache(): void
    {
        Cache::forget('kyc_queue_stats');
        $commonRanges = [7, 30, 60, 90, 365];
        foreach ($commonRanges as $days) {
            Cache::forget("kyc_statistics_{$days}days");
        }
    }
}