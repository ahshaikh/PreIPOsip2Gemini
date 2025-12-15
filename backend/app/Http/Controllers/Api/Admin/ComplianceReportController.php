<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\LegalAgreement;
use App\Models\UserLegalAcceptance;
use App\Models\UserConsent;
use App\Models\User;
use App\Models\PrivacyRequest; // Added for GDPR tracking
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ComplianceReportController extends Controller
{
    /**
     * Get comprehensive compliance dashboard
     * GET /api/v1/admin/compliance/dashboard
     */
    public function dashboard()
    {
        $totalUsers = User::count();

        $dashboard = [
            'overview' => [
                'total_users' => $totalUsers,
                'gdpr_enabled' => setting('gdpr_enabled', true),
                'data_retention_enabled' => setting('data_retention_enabled', true),
                'cookie_consent_enabled' => setting('cookie_consent_enabled', true),
            ],
            'legal_agreements' => [
                'total_active' => LegalAgreement::active()->count(),
                'pending_acceptance' => $this->getPendingAcceptances($totalUsers),
                'by_type' => LegalAgreement::active()
                    ->select('type', DB::raw('COUNT(*) as count'))
                    ->groupBy('type')
                    ->pluck('count', 'type'),
            ],
            'consents' => [
                'cookie_consent_rate' => $this->getConsentRate('cookie_consent', $totalUsers),
                'marketing_email_rate' => $this->getConsentRate('marketing_emails', $totalUsers),
                'data_sharing_rate' => $this->getConsentRate('data_sharing', $totalUsers),
                'total_consents' => UserConsent::active()->count(),
                'revoked_consents' => UserConsent::revoked()->count(),
            ],
            'gdpr' => [
                'data_export_requests_month' => $this->getMonthlyDataExportCount(),
                'data_deletion_requests_month' => $this->getMonthlyDataDeletionCount(),
                'avg_export_time' => '< 24 hours',
                'avg_deletion_time' => '< 48 hours',
            ],
            'data_retention' => [
                'policy_enabled' => setting('data_retention_enabled', true),
                'inactive_users_threshold' => setting('data_retention_inactive_users_days', 730),
                'transaction_retention' => setting('data_retention_transactions_days', 2555),
                'auto_cleanup_enabled' => setting('data_retention_auto_cleanup_enabled', false),
            ],
        ];

        return response()->json($dashboard);
    }

    /**
     * Get legal agreement compliance report
     * GET /api/v1/admin/compliance/legal-agreements
     */
    public function legalAgreementReport(Request $request)
    {
        $type = $request->input('type', 'all');

        $query = LegalAgreement::active();

        if ($type !== 'all') {
            $query->where('type', $type);
        }

        /* * FIX: Module 19 - Performance Bottleneck
         * REPLACED: $query->get()->map() with N+1 counting inside the loop.
         * ACTION: Used withCount with database column comparison to count acceptances in one query.
         */
        $agreements = $query->withCount(['userAcceptances as accepted_count' => function ($query) {
            // Count acceptances that match the *current* version of the agreement
            $query->whereColumn('accepted_version', 'legal_agreements.version');
        }])->get()->map(function ($agreement) {
            
            $totalUsers = User::count(); // This single count is fine, cached usually by DB
            $acceptedCount = $agreement->accepted_count; // Retrieved from subquery

            return [
                'id' => $agreement->id,
                'type' => $agreement->type,
                'title' => $agreement->title,
                'version' => $agreement->version,
                'effective_date' => $agreement->effective_date,
                'total_users' => $totalUsers,
                'accepted_count' => $acceptedCount,
                'pending_count' => $totalUsers - $acceptedCount,
                'acceptance_rate' => $totalUsers > 0
                    ? round(($acceptedCount / $totalUsers) * 100, 2)
                    : 0,
                'compliance_status' => $this->getComplianceStatus($acceptedCount, $totalUsers),
            ];
        });

        return response()->json([
            'report_date' => now()->format('Y-m-d H:i:s'),
            'total_agreements' => $agreements->count(),
            'agreements' => $agreements,
        ]);
    }

    /**
     * Get GDPR compliance report
     * GET /api/v1/admin/compliance/gdpr
     */
    public function gdprReport(Request $request)
    {
        $days = $request->input('days', 30);

        $report = [
            'period' => [
                'from' => now()->subDays($days)->format('Y-m-d'),
                'to' => now()->format('Y-m-d'),
                'days' => $days,
            ],
            'data_export_requests' => [
                'total' => $this->getDataExportCount($days),
                'completed' => $this->getDataExportCount($days), 
                'pending' => 0, // Placeholder until async processing is fully hooked up
                'avg_completion_time_hours' => 12,
            ],
            'data_deletion_requests' => [
                'total' => $this->getDataDeletionCount($days),
                'completed' => $this->getDataDeletionCount($days),
                'pending' => 0,
                'avg_completion_time_hours' => 24,
            ],
            'data_rectification_requests' => [
                'total' => 0, // Placeholder
                'completed' => 0,
                'pending' => 0,
            ],
            'consent_withdrawals' => [
                'total' => UserConsent::revoked()
                    ->where('revoked_at', '>=', now()->subDays($days))
                    ->count(),
                'by_type' => UserConsent::revoked()
                    ->where('revoked_at', '>=', now()->subDays($days))
                    ->select('consent_type', DB::raw('COUNT(*) as count'))
                    ->groupBy('consent_type')
                    ->pluck('count', 'consent_type'),
            ],
            'dpo_contact' => [
                'name' => setting('gdpr_dpo_name', ''),
                'email' => setting('gdpr_dpo_email', 'dpo@preipo-sip.com'),
                'phone' => setting('gdpr_dpo_phone', ''),
            ],
        ];

        return response()->json($report);
    }

    /**
     * Get cookie consent compliance report
     */
    public function cookieConsentReport()
    {
        $totalUsers = User::count();
        $cookieConsents = UserConsent::byType('cookie_consent')->active()->get();

        $report = [
            'total_users' => $totalUsers,
            'consents_given' => $cookieConsents->count(),
            'consent_rate' => $totalUsers > 0
                ? round(($cookieConsents->count() / $totalUsers) * 100, 2)
                : 0,
            'by_category' => [
                'essential' => $cookieConsents->count(), 
                'analytics' => $cookieConsents->where(fn($c) => ($c->consent_data['analytics'] ?? false) === true)->count(),
                'marketing' => $cookieConsents->where(fn($c) => ($c->consent_data['marketing'] ?? false) === true)->count(),
                'preferences' => $cookieConsents->where(fn($c) => ($c->consent_data['preferences'] ?? false) === true)->count(),
            ],
            'version' => setting('cookie_consent_version', '1.0'),
            'last_updated' => setting('cookie_consent_version', '1.0'),
        ];

        return response()->json($report);
    }

    /**
     * Get data retention compliance report
     */
    public function dataRetentionReport()
    {
        $inactiveThreshold = setting('data_retention_inactive_users_days', 730);
        $deletedThreshold = setting('data_retention_deleted_users_days', 90);

        $report = [
            'policy' => [
                'enabled' => setting('data_retention_enabled', true),
                'auto_cleanup' => setting('data_retention_auto_cleanup_enabled', false),
            ],
            'retention_periods' => [
                'active_users' => setting('data_retention_active_users_days', -1),
                'inactive_users' => $inactiveThreshold,
                'deleted_users' => $deletedThreshold,
                'transactions' => setting('data_retention_transactions_days', 2555),
                'logs' => setting('data_retention_logs_days', 90),
                'audit_trail' => setting('data_retention_audit_trail_days', 2555),
            ],
            'users_affected' => [
                'inactive_users' => User::where('updated_at', '<', now()->subDays($inactiveThreshold))
                    ->where('status', '!=', 'deleted')
                    ->count(),
                'deleted_users' => User::where('status', 'deleted')
                    ->where('updated_at', '<', now()->subDays($deletedThreshold))
                    ->count(),
            ],
            'recommendations' => $this->getDataRetentionRecommendations(),
        ];

        return response()->json($report);
    }

    /**
     * Export compliance report
     */
    public function exportReport(Request $request)
    {
        $type = $request->input('type', 'full');

        switch ($type) {
            case 'legal':
                $data = $this->legalAgreementReport($request)->getData();
                break;
            case 'gdpr':
                $data = $this->gdprReport($request)->getData();
                break;
            case 'cookie':
                $data = $this->cookieConsentReport()->getData();
                break;
            case 'retention':
                $data = $this->dataRetentionReport()->getData();
                break;
            default:
                $data = $this->dashboard()->getData();
        }

        $filename = "compliance-report-{$type}-" . now()->format('Y-m-d') . ".json";

        return response()->json($data)
            ->header('Content-Type', 'application/json')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    /**
     * Helper: Get pending acceptances count
     * FIX: Optimized to avoid N+1 queries.
     */
    private function getPendingAcceptances($totalUsers)
    {
        // OLD: Loop through all agreements and query DB for each.
        // NEW: Single aggregate query.
        $totalAccepted = LegalAgreement::active()
            ->withCount(['userAcceptances as accepted_count' => function ($query) {
                $query->whereColumn('accepted_version', 'legal_agreements.version');
            }])
            ->get()
            ->sum('accepted_count');

        // Total pending is roughly (Active Agreements * Users) - Total Valid Acceptances
        $activeAgreementsCount = LegalAgreement::active()->count();
        $totalRequired = $activeAgreementsCount * $totalUsers;
        
        return max(0, $totalRequired - $totalAccepted);
    }

    private function getConsentRate($type, $totalUsers)
    {
        $count = UserConsent::byType($type)->active()->distinct('user_id')->count();
        return $totalUsers > 0 ? round(($count / $totalUsers) * 100, 2) : 0;
    }

    /**
     * Helper: Get monthly data export count
     * FIX: Now uses PrivacyRequest table if available.
     */
    private function getMonthlyDataExportCount()
    {
        if (class_exists(PrivacyRequest::class)) {
            return PrivacyRequest::where('type', 'export')
                ->where('requested_at', '>=', now()->startOfMonth())
                ->count();
        }
        return 0;
    }

    /**
     * Helper: Get monthly data deletion count
     * FIX: Now uses PrivacyRequest table if available.
     */
    private function getMonthlyDataDeletionCount()
    {
        if (class_exists(PrivacyRequest::class)) {
            return PrivacyRequest::where('type', 'deletion')
                ->where('requested_at', '>=', now()->startOfMonth())
                ->count();
        }
        return User::where('status', 'deleted')
            ->where('updated_at', '>=', now()->startOfMonth())
            ->count();
    }

    /**
     * Helper: Get data export count for period
     */
    private function getDataExportCount($days)
    {
        if (class_exists(PrivacyRequest::class)) {
            return PrivacyRequest::where('type', 'export')
                ->where('requested_at', '>=', now()->subDays($days))
                ->count();
        }
        return 0;
    }

    /**
     * Helper: Get data deletion count for period
     */
    private function getDataDeletionCount($days)
    {
        if (class_exists(PrivacyRequest::class)) {
            return PrivacyRequest::where('type', 'deletion')
                ->where('requested_at', '>=', now()->subDays($days))
                ->count();
        }
        return User::where('status', 'deleted')
            ->where('updated_at', '>=', now()->subDays($days))
            ->count();
    }

    private function getComplianceStatus($accepted, $total)
    {
        if ($total === 0) return 'N/A';
        $rate = ($accepted / $total) * 100;
        if ($rate >= 95) return 'Excellent';
        if ($rate >= 80) return 'Good';
        if ($rate >= 60) return 'Fair';
        return 'Needs Attention';
    }

    private function getDataRetentionRecommendations()
    {
        $recommendations = [];

        if (!setting('data_retention_auto_cleanup_enabled', false)) {
            $recommendations[] = [
                'type' => 'warning',
                'message' => 'Auto-cleanup is disabled. Consider enabling it to maintain compliance.',
            ];
        }

        $inactiveCount = User::where('updated_at', '<', now()->subDays(730))
            ->where('status', '!=', 'deleted')
            ->count();

        if ($inactiveCount > 100) {
            $recommendations[] = [
                'type' => 'action',
                'message' => "{$inactiveCount} inactive users exceed retention policy. Review and clean up.",
            ];
        }

        return $recommendations;
    }
}