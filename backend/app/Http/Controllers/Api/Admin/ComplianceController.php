<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\LegalAgreement;
use App\Models\LegalAgreementVersion;
use App\Models\LegalAgreementAuditTrail;
use App\Models\UserLegalAcceptance;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage; // Added for PDF storage
use Barryvdh\DomPDF\Facade\Pdf; // Added for PDF generation

class ComplianceController extends Controller
{
    /**
     * Get all legal agreements
     */
    public function index(Request $request)
    {
        $query = LegalAgreement::query();

        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // FIX: Module 17 - Optimize Compliance Dashboard (High)
        // Replaced N+1 query loop with efficient subquery using withCount
        // We compare the accepted_version in the acceptance table with the current version in the agreements table
        $agreements = $query->with(['creator', 'updater'])
            ->withCount('versions')
            ->withCount(['userAcceptances as accepted_count' => function($q) {
                $q->whereColumn('accepted_version', 'legal_agreements.version');
            }])
            ->orderBy('created_at', 'desc')
            ->get();

        // Calculate acceptance rate in memory (O(N)) instead of N Queries
        $totalUsers = User::count();
        
        $agreements->each(function ($agreement) use ($totalUsers) {
            $agreement->acceptance_rate = $totalUsers > 0
                ? round(($agreement->accepted_count / $totalUsers) * 100, 2)
                : 0;
        });

        return response()->json($agreements);
    }

    /**
     * Get agreement statistics
     */
    public function stats()
    {
        $totalAgreements = LegalAgreement::count();
        $activeAgreements = LegalAgreement::where('status', 'active')->count();
        $reviewAgreements = LegalAgreement::where('status', 'review')->count();
        $totalVersions = LegalAgreementVersion::count();
        $thisMonthChanges = LegalAgreement::where('updated_at', '>=', now()->startOfMonth())->count();

        return response()->json([
            'total_agreements' => $totalAgreements,
            'active_agreements' => $activeAgreements,
            'review_agreements' => $reviewAgreements,
            'total_versions' => $totalVersions,
            'this_month_changes' => $thisMonthChanges,
        ]);
    }

    /**
     * Store a new legal agreement
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|string|max:255',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'content' => 'required|string',
            'version' => 'required|string|max:50',
            'status' => 'required|in:draft,review,active,archived,superseded',
            'effective_date' => 'nullable|date',
            'expiry_date' => 'nullable|date|after:effective_date',
            'require_signature' => 'boolean',
            'is_template' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $agreement = LegalAgreement::create(array_merge(
                $request->all(),
                [
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id(),
                ]
            ));

            // Create initial version
            $agreement->createVersion('Initial version', Auth::user());

            // Log creation
            $agreement->logAudit(
                'created',
                "Agreement created: {$agreement->title} (v{$agreement->version})",
                null,
                Auth::user()
            );

            DB::commit();

            return response()->json($agreement, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create agreement', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Show a specific legal agreement
     */
    public function show($id)
    {
        $agreement = LegalAgreement::with(['creator', 'updater', 'versions'])
            ->findOrFail($id);

        return response()->json($agreement);
    }

    /**
     * Update a legal agreement
     */
    public function update(Request $request, $id)
    {
        $agreement = LegalAgreement::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'type' => 'sometimes|string|max:255',
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'content' => 'sometimes|string',
            'version' => 'sometimes|string|max:50',
            'status' => 'sometimes|in:draft,review,active,archived,superseded',
            'effective_date' => 'nullable|date',
            'expiry_date' => 'nullable|date|after:effective_date',
            'require_signature' => 'boolean',
            'is_template' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $oldData = $agreement->only(['title', 'content', 'version', 'status']);

            $agreement->update(array_merge(
                $request->all(),
                ['updated_by' => Auth::id()]
            ));

            // Track changes
            $changes = [];
            foreach ($oldData as $key => $value) {
                if ($request->has($key) && $request->$key !== $value) {
                    $changes[$key] = [
                        'old' => $value,
                        'new' => $request->$key,
                    ];
                }
            }

            // Create new version if content or version changed
            if (isset($changes['content']) || isset($changes['version'])) {
                $agreement->createVersion(
                    $request->input('change_summary', 'Updated agreement'),
                    Auth::user()
                );
            }

            // Log the update
            $agreement->logAudit(
                'updated',
                "Agreement updated: {$agreement->title}",
                $changes,
                Auth::user()
            );

            DB::commit();

            return response()->json($agreement);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to update agreement', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete a legal agreement
     */
    public function destroy($id)
    {
        $agreement = LegalAgreement::findOrFail($id);

        // Log deletion before deleting
        $agreement->logAudit(
            'deleted',
            "Agreement deleted: {$agreement->title} (v{$agreement->version})",
            null,
            Auth::user()
        );

        $agreement->delete();

        return response()->json(['message' => 'Agreement deleted successfully']);
    }

    /**
     * Publish an agreement (set status to active)
     */
    public function publish($id)
    {
        $agreement = LegalAgreement::findOrFail($id);
        $agreement->update(['status' => 'active']);

        // FIX: Module 17 - Fix PDF Scalability (Critical)
        // Generate PDF and save to storage immediately upon publishing.
        // This prevents generating it on every user download request.
        try {
            $pdf = Pdf::loadView('pdf.legal-document', ['document' => $agreement]);
            $fileName = "legal/{$agreement->type}-v{$agreement->version}.pdf";
            
            // Ensure directory exists
            if (!Storage::disk('public')->exists('legal')) {
                Storage::disk('public')->makeDirectory('legal');
            }
            
            Storage::disk('public')->put($fileName, $pdf->output());
            
            $agreement->logAudit(
                'published',
                "Agreement published and PDF generated: {$agreement->title} (v{$agreement->version})",
                ['old' => $agreement->getOriginal('status'), 'new' => 'active', 'pdf_path' => $fileName],
                Auth::user()
            );
        } catch (\Exception $e) {
            // Log error but don't fail the publish action completely
            \Illuminate\Support\Facades\Log::error("Failed to generate PDF for agreement {$id}: " . $e->getMessage());
        }

        return response()->json($agreement);
    }

    /**
     * Archive an agreement
     */
    public function archive($id)
    {
        $agreement = LegalAgreement::findOrFail($id);
        $agreement->update(['status' => 'archived']);

        $agreement->logAudit(
            'archived',
            "Agreement archived: {$agreement->title} (v{$agreement->version})",
            ['old' => $agreement->getOriginal('status'), 'new' => 'archived'],
            Auth::user()
        );

        return response()->json($agreement);
    }

    /**
     * Get all versions of an agreement
     */
    public function versions($id)
    {
        $versions = LegalAgreementVersion::where('legal_agreement_id', $id)
            ->with('creator')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($versions);
    }

    /**
     * Get acceptance statistics for an agreement
     */
    public function acceptanceStats($id)
    {
        $agreement = LegalAgreement::findOrFail($id);
        $totalUsers = User::count();

        $acceptedCount = UserLegalAcceptance::where('legal_agreement_id', $id)
            ->where('accepted_version', $agreement->version)
            ->distinct('user_id')
            ->count('user_id');

        $pendingCount = $totalUsers - $acceptedCount;
        $declinedCount = 0; // Placeholder for future implementation

        $acceptanceRate = $totalUsers > 0 ? round(($acceptedCount / $totalUsers) * 100, 2) : 0;

        // Get recent acceptances
        $recentAcceptances = UserLegalAcceptance::where('legal_agreement_id', $id)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($acceptance) {
                return [
                    'id' => $acceptance->id,
                    'user_name' => $acceptance->user->name,
                    'user_email' => $acceptance->user->email,
                    'version' => $acceptance->accepted_version,
                    'accepted_at' => $acceptance->created_at,
                    'ip_address' => $acceptance->ip_address,
                ];
            });

        return response()->json([
            'total_users' => $totalUsers,
            'accepted_count' => $acceptedCount,
            'pending_count' => $pendingCount,
            'declined_count' => $declinedCount,
            'acceptance_rate' => $acceptanceRate,
            'recent_acceptances' => $recentAcceptances,
        ]);
    }

    /**
     * Get audit trail for an agreement
     */
    public function auditTrail(Request $request, $id)
    {
        $query = LegalAgreementAuditTrail::where('legal_agreement_id', $id);

        if ($request->has('event_type') && $request->event_type !== 'all') {
            $query->where('event_type', $request->event_type);
        }

        if ($request->has('date_range')) {
            switch ($request->date_range) {
                case 'today':
                    $query->whereDate('created_at', today());
                    break;
                case 'week':
                    $query->where('created_at', '>=', now()->startOfWeek());
                    break;
                case 'month':
                    $query->where('created_at', '>=', now()->startOfMonth());
                    break;
                case 'quarter':
                    $query->where('created_at', '>=', now()->startOfQuarter());
                    break;
                case 'year':
                    $query->where('created_at', '>=', now()->startOfYear());
                    break;
            }
        }

        $auditTrail = $query->with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($auditTrail);
    }

    /**
     * Get audit trail statistics
     */
    public function auditTrailStats($id)
    {
        $totalEvents = LegalAgreementAuditTrail::where('legal_agreement_id', $id)->count();
        $thisWeekEvents = LegalAgreementAuditTrail::where('legal_agreement_id', $id)
            ->where('created_at', '>=', now()->startOfWeek())
            ->count();
        $uniqueUsers = LegalAgreementAuditTrail::where('legal_agreement_id', $id)
            ->whereNotNull('user_id')
            ->distinct('user_id')
            ->count('user_id');
        $lastActivity = LegalAgreementAuditTrail::where('legal_agreement_id', $id)
            ->latest()
            ->first();

        // Event counts by type
        $eventCounts = LegalAgreementAuditTrail::where('legal_agreement_id', $id)
            ->select('event_type', DB::raw('count(*) as count'))
            ->groupBy('event_type')
            ->pluck('count', 'event_type')
            ->toArray();

        return response()->json([
            'total_events' => $totalEvents,
            'this_week_events' => $thisWeekEvents,
            'unique_users' => $uniqueUsers,
            'last_activity' => $lastActivity ? $lastActivity->created_at : null,
            'event_counts' => $eventCounts,
        ]);
    }

    /**
     * Export audit trail as CSV
     */
    public function exportAuditTrail($id)
    {
        $auditTrail = LegalAgreementAuditTrail::where('legal_agreement_id', $id)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        $csvData = "ID,Event Type,Description,Version,User,IP Address,Date\n";

        foreach ($auditTrail as $entry) {
            $csvData .= sprintf(
                "%d,%s,%s,%s,%s,%s,%s\n",
                $entry->id,
                $entry->event_type,
                str_replace(',', ';', $entry->description),
                $entry->version ?? 'N/A',
                $entry->user_name ?? 'System',
                $entry->ip_address ?? 'N/A',
                $entry->created_at->format('Y-m-d H:i:s')
            );
        }

        return response($csvData, 200)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="audit-trail-' . $id . '-' . now()->format('Y-m-d') . '.csv"');
    }
}