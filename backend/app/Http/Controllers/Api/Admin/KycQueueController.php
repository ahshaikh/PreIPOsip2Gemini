<?php
// V-PHASE2-1730-054 (Created) | V-FINAL-1730-319 | V-FINAL-1730-462 (N+1 Optimized)
// V-KYC-ENHANCEMENT-005 | Enhanced with statistics, notes, and resubmission

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\UserKyc;
use App\Models\KycDocument;
use App\Models\KycRejectionTemplate;
use App\Notifications\KycVerified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        // Calculate statistics
        $stats = [
            'total' => UserKyc::count(),
            'pending' => UserKyc::where('status', 'submitted')->count(),
            'verified' => UserKyc::where('status', 'verified')->count(),
            'rejected' => UserKyc::where('status', 'rejected')->count(),
        ];

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
     */
    public function approve(Request $request, $id)
    {
        $request->validate([
            'verification_checklist' => 'sometimes|array',
            'notes' => 'sometimes|string|max:1000',
        ]);

        $kyc = UserKyc::with('user')->findOrFail($id);
        $admin = $request->user();

        if ($kyc->status !== 'submitted') {
            return response()->json(['message' => 'This submission is not pending approval.'], 400);
        }

        DB::transaction(function () use ($kyc, $admin, $request) {
            $kyc->update([
                'status' => 'verified',
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

        return response()->json(['message' => 'KYC approved successfully.']);
    }

    /**
     * Reject KYC with reason and checklist
     */
    public function reject(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|min:10|max:500',
            'verification_checklist' => 'sometimes|array',
        ]);

        $kyc = UserKyc::findOrFail($id);
        $admin = $request->user();

        if ($kyc->status !== 'submitted') {
            return response()->json(['message' => 'This submission is not pending approval.'], 400);
        }

        DB::transaction(function () use ($kyc, $admin, $request) {
            $kyc->update([
                'status' => 'rejected',
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

        return response()->json(['message' => 'KYC rejected successfully.']);
    }

    /**
     * Request resubmission with instructions
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
                'status' => 'resubmission_required',
                'resubmission_instructions' => $request->instructions,
            ]);

            $kyc->verificationNotes()->create([
                'admin_id' => $admin->id,
                'note' => 'Resubmission requested: ' . $request->instructions,
            ]);
        });

        // TODO: Send resubmission notification
        // $kyc->user->notify(new KycResubmissionRequired($request->instructions));

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
     */
    public function statistics(Request $request)
    {
        $days = $request->query('days', 30);
        $startDate = now()->subDays($days);

        $stats = [
            'total_submissions' => UserKyc::where('created_at', '>=', $startDate)->count(),
            'pending' => UserKyc::where('status', 'submitted')->where('created_at', '>=', $startDate)->count(),
            'verified' => UserKyc::where('status', 'verified')->where('created_at', '>=', $startDate)->count(),
            'rejected' => UserKyc::where('status', 'rejected')->where('created_at', '>=', $startDate)->count(),
            'avg_processing_time_hours' => UserKyc::where('verified_at', '>=', $startDate)
                ->whereNotNull('verified_at')
                ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, submitted_at, verified_at)) as avg_hours')
                ->value('avg_hours') ?? 0,
            'sla_compliance_percentage' => $this->calculateSLACompliance($startDate),
            'auto_verified_count' => UserKyc::where('status', 'verified')
                ->where('created_at', '>=', $startDate)
                ->whereHas('documents', function ($q) {
                    $q->where('processing_status', 'verified');
                })
                ->count(),
            'manual_verified_count' => UserKyc::where('status', 'verified')
                ->where('created_at', '>=', $startDate)
                ->whereDoesntHave('documents', function ($q) {
                    $q->where('processing_status', 'verified');
                })
                ->count(),
        ];

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
}
