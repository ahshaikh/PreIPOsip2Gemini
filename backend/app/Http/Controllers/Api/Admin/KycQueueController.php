<?php
// V-PHASE2-1730-054 (Created) | V-FINAL-1730-319 | V-FINAL-1730-462 (N+1 Optimized)
// V-KYC-ENHANCEMENT-005 | Enhanced with statistics, notes, and resubmission
// V-AUDIT-MODULE2-008 (Fixed) - Added caching, fixed N+1 queries, added KycStatus enum

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\UserKyc;
use App\Models\KycDocument;
use App\Models\KycRejectionTemplate;
use App\Notifications\KycVerified;
use App\Enums\KycStatus; // ADDED: Import KycStatus enum
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache; // ADDED: Import Cache facade for statistics caching

class KycQueueController extends Controller
{
    /**
     * Get KYC queue with enhanced filtering and search
     */
    public function index(Request $request)
    {
        $status = $request->query('status', 'submitted');
        $search = $request->query('search');
        $priority = $request->query('priority');

        $query = UserKyc::query()->with('user:id,username,email,phone');

        // Status filter
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        // Search filter (username, email, ID)
        if ($search) {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('username', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('id', $search);
            });
        }

        // Priority filter (based on waiting time)
        if ($priority === 'high') {
            $query->where('submitted_at', '<', now()->subHours(24));
        }

        $kycSubmissions = $query->latest('submitted_at')->paginate(25);

        // PERFORMANCE FIX: Cache statistics for 5 minutes to reduce DB load
        // These counts can be expensive as the table grows
        $stats = Cache::remember('kyc_queue_stats', 300, function () {
            return [
                'total' => UserKyc::count(),
                'pending' => UserKyc::where('status', KycStatus::SUBMITTED->value)->count(),
                'verified' => UserKyc::where('status', KycStatus::VERIFIED->value)->count(),
                'rejected' => UserKyc::where('status', KycStatus::REJECTED->value)->count(),
                'processing' => UserKyc::where('status', KycStatus::PROCESSING->value)->count(), // ADDED
            ];
        });

        return response()->json([
            'data' => $kycSubmissions->items(),
            'stats' => $stats,
            'pagination' => [
                'total' => $kycSubmissions->total(),
                'current_page' => $kycSubmissions->currentPage(),
                'last_page' => $kycSubmissions->lastPage(),
            ],
        ]);
    }

    /**
     * Get detailed KYC submission with documents and notes
     */
    public function show($id)
    {
        $kyc = UserKyc::with(['user.profile', 'documents', 'verificationNotes.admin'])
            ->findOrFail($id);
        return response()->json($kyc);
    }

    /**
     * Approve KYC with verification checklist
     *
     * UPDATED (V-AUDIT-MODULE2-008):
     * - Uses KycStatus enum
     * - Invalidates statistics cache on approval
     */
    public function approve(Request $request, $id)
    {
        $request->validate([
            'verification_checklist' => 'sometimes|array',
            'notes' => 'sometimes|string|max:1000',
        ]);

        $kyc = UserKyc::with('user')->findOrFail($id);
        $admin = $request->user();

        // UPDATED: Use KycStatus enum for comparison
        if ($kyc->status !== KycStatus::SUBMITTED->value) {
            return response()->json(['message' => 'This submission is not pending approval.'], 400);
        }

        DB::transaction(function () use ($kyc, $admin, $request) {
            $kyc->update([
                'status' => KycStatus::VERIFIED->value, // UPDATED: Use enum
                'verified_at' => now(),
                'verified_by' => $admin->id,
                'rejection_reason' => null,
                'verification_checklist' => $request->verification_checklist,
            ]);

            // Add verification note if provided
            if ($request->notes) {
                $kyc->verificationNotes()->create([
                    'admin_id' => $admin->id,
                    'note' => $request->notes,
                ]);
            }

            // Mark all documents as approved
            $kyc->documents()->update(['status' => 'approved']);
        });

        $kyc->user->notify(new KycVerified());

        // ADDED: Invalidate statistics cache after approval
        $this->invalidateStatisticsCache();

        return response()->json(['message' => 'KYC approved successfully.']);
    }

    /**
     * Reject KYC with reason and checklist
     *
     * UPDATED (V-AUDIT-MODULE2-008):
     * - Uses KycStatus enum
     * - Invalidates statistics cache on rejection
     */
    public function reject(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|min:10|max:500',
            'verification_checklist' => 'sometimes|array',
        ]);

        $kyc = UserKyc::findOrFail($id);
        $admin = $request->user();

        // UPDATED: Use KycStatus enum for comparison
        if ($kyc->status !== KycStatus::SUBMITTED->value) {
            return response()->json(['message' => 'This submission is not pending approval.'], 400);
        }

        DB::transaction(function () use ($kyc, $admin, $request) {
            $kyc->update([
                'status' => KycStatus::REJECTED->value, // UPDATED: Use enum
                'rejection_reason' => $request->reason,
                'verified_at' => null,
                'verified_by' => $admin->id,
                'verification_checklist' => $request->verification_checklist,
            ]);

            // Add rejection note
            $kyc->verificationNotes()->create([
                'admin_id' => $admin->id,
                'note' => 'REJECTED: ' . $request->reason,
            ]);
        });

        // TODO: Send rejection notification
        // $kyc->user->notify(new KycRejected($request->reason));

        // ADDED: Invalidate statistics cache after rejection
        $this->invalidateStatisticsCache();

        return response()->json(['message' => 'KYC rejected successfully.']);
    }

    /**
     * Request resubmission with instructions
     *
     * UPDATED (V-AUDIT-MODULE2-008):
     * - Uses KycStatus enum
     * - Invalidates statistics cache
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
                'status' => KycStatus::RESUBMISSION_REQUIRED->value, // UPDATED: Use enum
                'resubmission_instructions' => $request->instructions,
            ]);

            $kyc->verificationNotes()->create([
                'admin_id' => $admin->id,
                'note' => 'Resubmission requested: ' . $request->instructions,
            ]);
        });

        // TODO: Send resubmission notification
        // $kyc->user->notify(new KycResubmissionRequired($request->instructions));

        // ADDED: Invalidate statistics cache
        $this->invalidateStatisticsCache();

        return response()->json(['message' => 'Resubmission request sent successfully.']);
    }

    /**
     * Add verification note
     */
    public function addNote(Request $request, $id)
    {
        $request->validate([
            'note' => 'required|string|min:5|max:1000',
        ]);

        $kyc = UserKyc::findOrFail($id);
        $admin = $request->user();

        $note = $kyc->verificationNotes()->create([
            'admin_id' => $admin->id,
            'note' => $request->note,
        ]);

        return response()->json([
            'message' => 'Note added successfully',
            'note' => $note->load('admin'),
        ]);
    }

    /**
     * Get KYC statistics
     *
     * PERFORMANCE FIX (V-AUDIT-MODULE2-008):
     * - Added caching to reduce expensive aggregation queries
     * - Cache TTL: 10 minutes
     * - Updated to use KycStatus enum
     */
    public function statistics(Request $request)
    {
        $days = $request->query('days', 30);
        $startDate = now()->subDays($days);

        // PERFORMANCE FIX: Cache statistics for 10 minutes (600 seconds)
        // Use dynamic cache key based on the time range requested
        $cacheKey = "kyc_statistics_{$days}days";

        $stats = Cache::remember($cacheKey, 600, function () use ($startDate) {
            return [
                'total_submissions' => UserKyc::where('created_at', '>=', $startDate)->count(),
                'pending' => UserKyc::where('status', KycStatus::SUBMITTED->value)
                    ->where('created_at', '>=', $startDate)->count(),
                'verified' => UserKyc::where('status', KycStatus::VERIFIED->value)
                    ->where('created_at', '>=', $startDate)->count(),
                'rejected' => UserKyc::where('status', KycStatus::REJECTED->value)
                    ->where('created_at', '>=', $startDate)->count(),
                'processing' => UserKyc::where('status', KycStatus::PROCESSING->value)
                    ->where('created_at', '>=', $startDate)->count(), // ADDED
                'avg_processing_time_hours' => UserKyc::where('verified_at', '>=', $startDate)
                    ->whereNotNull('verified_at')
                    ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, submitted_at, verified_at)) as avg_hours')
                    ->value('avg_hours') ?? 0,
                'sla_compliance_percentage' => $this->calculateSLACompliance($startDate),
                'auto_verified_count' => UserKyc::where('status', KycStatus::VERIFIED->value)
                    ->where('created_at', '>=', $startDate)
                    ->whereHas('documents', function ($q) {
                        $q->where('processing_status', 'verified');
                    })
                    ->count(),
                'manual_verified_count' => UserKyc::where('status', KycStatus::VERIFIED->value)
                    ->where('created_at', '>=', $startDate)
                    ->whereDoesntHave('documents', function ($q) {
                        $q->where('processing_status', 'verified');
                    })
                    ->count(),
            ];
        });

        return response()->json($stats);
    }

    /**
     * Get time series data
     */
    public function timeSeries(Request $request)
    {
        $days = $request->query('days', 30);
        $startDate = now()->subDays($days);

        $data = UserKyc::selectRaw("
            DATE(created_at) as date,
            COUNT(*) as submissions,
            SUM(CASE WHEN status = 'verified' THEN 1 ELSE 0 END) as verifications,
            SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejections
        ")
        ->where('created_at', '>=', $startDate)
        ->groupBy('date')
        ->orderBy('date')
        ->get();

        return response()->json(['data' => $data]);
    }

    /**
     * Get document type statistics
     */
    public function documentTypeStats(Request $request)
    {
        $days = $request->query('days', 30);
        $startDate = now()->subDays($days);

        $stats = KycDocument::selectRaw("
            doc_type,
            COUNT(*) as total,
            SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as verified,
            SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending
        ")
        ->where('created_at', '>=', $startDate)
        ->groupBy('doc_type')
        ->get();

        return response()->json(['data' => $stats]);
    }

    /**
     * Get rejection templates
     */
    public function getRejectionTemplates()
    {
        $templates = KycRejectionTemplate::where('is_active', true)->get();
        return response()->json(['data' => $templates]);
    }

    /**
     * Calculate SLA compliance percentage
     */
    private function calculateSLACompliance($startDate)
    {
        $total = UserKyc::where('verified_at', '>=', $startDate)
            ->whereNotNull('verified_at')
            ->count();

        if ($total === 0) {
            return 100;
        }

        $withinSLA = UserKyc::where('verified_at', '>=', $startDate)
            ->whereNotNull('verified_at')
            ->whereRaw('TIMESTAMPDIFF(HOUR, submitted_at, verified_at) <= 24')
            ->count();

        return ($withinSLA / $total) * 100;
    }

    /**
     * ADDED (V-AUDIT-MODULE2-008): Invalidate all statistics caches
     *
     * This method should be called whenever KYC status changes (approve/reject/resubmit)
     * to ensure fresh statistics on next request
     *
     * @return void
     */
    private function invalidateStatisticsCache(): void
    {
        // Clear queue stats cache
        Cache::forget('kyc_queue_stats');

        // Clear statistics cache for common time ranges
        $commonRanges = [7, 30, 60, 90, 365];
        foreach ($commonRanges as $days) {
            Cache::forget("kyc_statistics_{$days}days");
        }
    }
}
