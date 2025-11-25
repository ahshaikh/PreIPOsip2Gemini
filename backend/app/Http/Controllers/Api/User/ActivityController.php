<?php
// V-FIX-USER-ACTIVITY (Created to fix missing dashboard endpoint)

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    /**
     * Get recent user activity for dashboard
     *
     * Returns recent activities including payments, bonuses, referrals, etc.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // Get user's recent activity logs
        $activities = ActivityLog::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($log) {
                // Map activity log to frontend format
                $type = $this->getActivityType($log->action);
                $amount = $this->getActivityAmount($log);

                return [
                    'type' => $type,
                    'description' => $log->description,
                    'created_at' => $log->created_at,
                    'amount' => $amount,
                ];
            });

        return response()->json($activities);
    }

    /**
     * Determine activity type from action
     */
    private function getActivityType(string $action): string
    {
        if (str_contains($action, 'payment') || str_contains($action, 'deposit')) {
            return 'payment';
        }
        if (str_contains($action, 'bonus')) {
            return 'bonus';
        }
        if (str_contains($action, 'referral')) {
            return 'referral';
        }
        return 'other';
    }

    /**
     * Extract amount from activity log if available
     */
    private function getActivityAmount($log): ?float
    {
        if ($log->new_values && isset($log->new_values['amount'])) {
            return floatval($log->new_values['amount']);
        }
        return null;
    }
}
