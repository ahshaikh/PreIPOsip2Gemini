<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailLog;
use App\Models\SmsLog;
use App\Models\PushLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotificationLogController extends Controller
{
    /**
     * Get notification dashboard statistics
     */
    public function dashboard(Request $request)
    {
        $days = $request->input('days', 30);
        $startDate = now()->subDays($days);

        return response()->json([
            'period_days' => $days,
            'email' => $this->getEmailStats($startDate),
            'sms' => $this->getSmsStats($startDate),
            'push' => $this->getPushStats($startDate),
            'combined' => $this->getCombinedStats($startDate),
        ]);
    }

    /**
     * Get all email logs with filters
     */
    public function emailLogs(Request $request)
    {
        $query = EmailLog::with(['user:id,username,email', 'emailTemplate:id,name,slug'])
            ->orderBy('created_at', 'desc');

        // Apply filters
        $query = $this->applyFilters($query, $request, 'email');

        $perPage = $request->input('per_page', 20);
        $logs = $query->paginate($perPage);

        return response()->json($logs);
    }

    /**
     * Get all SMS logs with filters
     */
    public function smsLogs(Request $request)
    {
        $query = SmsLog::with(['user:id,username', 'smsTemplate:id,name,slug'])
            ->orderBy('created_at', 'desc');

        // Apply filters
        $query = $this->applyFilters($query, $request, 'sms');

        $perPage = $request->input('per_page', 20);
        $logs = $query->paginate($perPage);

        return response()->json($logs);
    }

    /**
     * Get all push notification logs with filters
     */
    public function pushLogs(Request $request)
    {
        $query = PushLog::with('user:id,username')
            ->orderBy('created_at', 'desc');

        // Apply filters
        $query = $this->applyFilters($query, $request, 'push');

        $perPage = $request->input('per_page', 20);
        $logs = $query->paginate($perPage);

        return response()->json($logs);
    }

    /**
     * Get single email log details
     */
    public function showEmailLog(EmailLog $emailLog)
    {
        $emailLog->load(['user', 'emailTemplate']);
        return response()->json($emailLog);
    }

    /**
     * Get single SMS log details
     */
    public function showSmsLog(SmsLog $smsLog)
    {
        $smsLog->load(['user', 'smsTemplate']);
        return response()->json($smsLog);
    }

    /**
     * Get single push log details
     */
    public function showPushLog(PushLog $pushLog)
    {
        $pushLog->load('user');
        return response()->json($pushLog);
    }

    /**
     * Export email logs to CSV
     */
    public function exportEmailLogs(Request $request)
    {
        $query = EmailLog::with(['user', 'emailTemplate'])
            ->orderBy('created_at', 'desc');

        $query = $this->applyFilters($query, $request, 'email');

        $logs = $query->limit(10000)->get();

        $csv = $this->generateEmailCsv($logs);

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="email-logs-' . date('Y-m-d') . '.csv"',
        ]);
    }

    /**
     * Export SMS logs to CSV
     */
    public function exportSmsLogs(Request $request)
    {
        $query = SmsLog::with(['user', 'smsTemplate'])
            ->orderBy('created_at', 'desc');

        $query = $this->applyFilters($query, $request, 'sms');

        $logs = $query->limit(10000)->get();

        $csv = $this->generateSmsCsv($logs);

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="sms-logs-' . date('Y-m-d') . '.csv"',
        ]);
    }

    /**
     * Export push logs to CSV
     */
    public function exportPushLogs(Request $request)
    {
        $query = PushLog::with('user')
            ->orderBy('created_at', 'desc');

        $query = $this->applyFilters($query, $request, 'push');

        $logs = $query->limit(10000)->get();

        $csv = $this->generatePushCsv($logs);

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="push-logs-' . date('Y-m-d') . '.csv"',
        ]);
    }

    /**
     * Get user notification history
     */
    public function userHistory(Request $request, User $user)
    {
        $type = $request->input('type', 'all');
        $perPage = $request->input('per_page', 20);

        $history = [];

        if ($type === 'all' || $type === 'email') {
            $history['emails'] = EmailLog::where('user_id', $user->id)
                ->with('emailTemplate:id,name,slug')
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);
        }

        if ($type === 'all' || $type === 'sms') {
            $history['sms'] = SmsLog::where('user_id', $user->id)
                ->with('smsTemplate:id,name,slug')
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);
        }

        if ($type === 'all' || $type === 'push') {
            $history['push'] = PushLog::where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);
        }

        return response()->json($history);
    }

    /**
     * Delete old logs based on retention policy
     */
    public function cleanupLogs(Request $request)
    {
        $days = $request->input('days', setting('notification_log_retention_days', 90));
        $dryRun = $request->boolean('dry_run', true);

        $cutoffDate = now()->subDays($days);

        $emailCount = EmailLog::where('created_at', '<', $cutoffDate)->count();
        $smsCount = SmsLog::where('created_at', '<', $cutoffDate)->count();
        $pushCount = PushLog::where('created_at', '<', $cutoffDate)->count();

        if (!$dryRun) {
            EmailLog::where('created_at', '<', $cutoffDate)->delete();
            SmsLog::where('created_at', '<', $cutoffDate)->delete();
            PushLog::where('created_at', '<', $cutoffDate)->delete();
        }

        return response()->json([
            'dry_run' => $dryRun,
            'cutoff_date' => $cutoffDate,
            'deleted_counts' => [
                'email' => $emailCount,
                'sms' => $smsCount,
                'push' => $pushCount,
                'total' => $emailCount + $smsCount + $pushCount,
            ],
            'message' => $dryRun
                ? 'Dry run completed. No records were deleted.'
                : 'Log cleanup completed successfully.',
        ]);
    }

    /**
     * Helper: Apply filters to query
     */
    private function applyFilters($query, $request, $type)
    {
        // Status filter
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        // Date range filter
        if ($request->has('start_date')) {
            $query->whereDate('created_at', '>=', $request->input('start_date'));
        }

        if ($request->has('end_date')) {
            $query->whereDate('created_at', '<=', $request->input('end_date'));
        }

        // User filter
        if ($request->has('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        // Template filter
        if ($type === 'email' && $request->has('template_slug')) {
            $query->where('template_slug', $request->input('template_slug'));
        }

        if ($type === 'sms' && $request->has('template_slug')) {
            $query->where('template_slug', $request->input('template_slug'));
        }

        // Provider filter
        if ($request->has('provider')) {
            $query->where('provider', $request->input('provider'));
        }

        // Search filter (email/mobile)
        if ($request->has('search')) {
            $search = $request->input('search');
            if ($type === 'email') {
                $query->where('recipient_email', 'like', "%{$search}%");
            } elseif ($type === 'sms') {
                $query->where('recipient_mobile', 'like', "%{$search}%");
            } elseif ($type === 'push') {
                $query->where('device_token', 'like', "%{$search}%");
            }
        }

        return $query;
    }

    /**
     * Helper: Get email statistics
     */
    private function getEmailStats($startDate)
    {
        $query = EmailLog::where('created_at', '>=', $startDate);

        return [
            'total' => $query->count(),
            'sent' => $query->clone()->sent()->count(),
            'delivered' => $query->clone()->delivered()->count(),
            'opened' => $query->clone()->opened()->count(),
            'clicked' => $query->clone()->clicked()->count(),
            'bounced' => $query->clone()->bounced()->count(),
            'failed' => $query->clone()->failed()->count(),
        ];
    }

    /**
     * Helper: Get SMS statistics
     */
    private function getSmsStats($startDate)
    {
        $query = SmsLog::where('created_at', '>=', $startDate);

        return [
            'total' => $query->count(),
            'sent' => $query->clone()->sent()->count(),
            'delivered' => $query->clone()->delivered()->count(),
            'failed' => $query->clone()->failed()->count(),
            'credits_used' => $query->clone()->sum('credits_used') ?? 0,
        ];
    }

    /**
     * Helper: Get push notification statistics
     */
    private function getPushStats($startDate)
    {
        $query = PushLog::where('created_at', '>=', $startDate);

        return [
            'total' => $query->count(),
            'sent' => $query->clone()->sent()->count(),
            'delivered' => $query->clone()->delivered()->count(),
            'opened' => $query->clone()->opened()->count(),
            'failed' => $query->clone()->failed()->count(),
        ];
    }

    /**
     * Helper: Get combined statistics
     */
    private function getCombinedStats($startDate)
    {
        $emailStats = $this->getEmailStats($startDate);
        $smsStats = $this->getSmsStats($startDate);
        $pushStats = $this->getPushStats($startDate);

        return [
            'total_notifications' => $emailStats['total'] + $smsStats['total'] + $pushStats['total'],
            'total_delivered' => $emailStats['delivered'] + $smsStats['delivered'] + $pushStats['delivered'],
            'total_failed' => $emailStats['failed'] + $smsStats['failed'] + $pushStats['failed'],
            'by_channel' => [
                'email' => $emailStats['total'],
                'sms' => $smsStats['total'],
                'push' => $pushStats['total'],
            ],
        ];
    }

    /**
     * Helper: Generate email CSV
     */
    private function generateEmailCsv($logs)
    {
        $csv = "ID,User,Template,Recipient,Subject,Status,Provider,Sent At,Delivered At,Opened At,Created At\n";

        foreach ($logs as $log) {
            $csv .= sprintf(
                "%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s\n",
                $log->id,
                $log->user ? $log->user->username : 'N/A',
                $log->emailTemplate ? $log->emailTemplate->name : $log->template_slug,
                $log->recipient_email ?? $log->to_email,
                '"' . str_replace('"', '""', $log->subject) . '"',
                $log->status,
                $log->provider ?? 'N/A',
                $log->sent_at ?? 'N/A',
                $log->delivered_at ?? 'N/A',
                $log->opened_at ?? 'N/A',
                $log->created_at
            );
        }

        return $csv;
    }

    /**
     * Helper: Generate SMS CSV
     */
    private function generateSmsCsv($logs)
    {
        $csv = "ID,User,Template,Mobile,Message,Status,Provider,DLT Template,Credits,Sent At,Delivered At,Created At\n";

        foreach ($logs as $log) {
            $csv .= sprintf(
                "%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s\n",
                $log->id,
                $log->user ? $log->user->username : 'N/A',
                $log->smsTemplate ? $log->smsTemplate->name : $log->template_slug,
                $log->recipient_mobile ?? $log->to_mobile,
                '"' . str_replace('"', '""', $log->message) . '"',
                $log->status,
                $log->provider ?? 'N/A',
                $log->dlt_template_id ?? 'N/A',
                $log->credits_used ?? '0',
                $log->sent_at ?? 'N/A',
                $log->delivered_at ?? 'N/A',
                $log->created_at
            );
        }

        return $csv;
    }

    /**
     * Helper: Generate push CSV
     */
    private function generatePushCsv($logs)
    {
        $csv = "ID,User,Device Type,Title,Status,Provider,Priority,Sent At,Delivered At,Opened At,Created At\n";

        foreach ($logs as $log) {
            $csv .= sprintf(
                "%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s\n",
                $log->id,
                $log->user ? $log->user->username : 'N/A',
                $log->device_type ?? 'N/A',
                '"' . str_replace('"', '""', $log->title) . '"',
                $log->status,
                $log->provider ?? 'N/A',
                $log->priority ?? 'normal',
                $log->sent_at ?? 'N/A',
                $log->delivered_at ?? 'N/A',
                $log->opened_at ?? 'N/A',
                $log->created_at
            );
        }

        return $csv;
    }
}
