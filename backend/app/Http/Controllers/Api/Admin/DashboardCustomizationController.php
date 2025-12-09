<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminDashboardWidget;
use App\Models\AdminPreference;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardCustomizationController extends Controller
{
    /**
     * Get admin's dashboard widgets
     * GET /api/v1/admin/dashboard/widgets
     */
    public function getWidgets(Request $request)
    {
        $admin = $request->user();

        $widgets = AdminDashboardWidget::where('admin_id', $admin->id)
            ->orderBy('position')
            ->get();

        // If no widgets exist, return default configuration
        if ($widgets->isEmpty()) {
            return response()->json([
                'widgets' => $this->getDefaultWidgets(),
                'is_default' => true,
            ]);
        }

        return response()->json([
            'widgets' => $widgets,
            'is_default' => false,
        ]);
    }

    /**
     * Save/update dashboard widgets
     * POST /api/v1/admin/dashboard/widgets
     */
    public function saveWidgets(Request $request)
    {
        $validated = $request->validate([
            'widgets' => 'required|array',
            'widgets.*.widget_type' => 'required|string',
            'widgets.*.position' => 'required|integer|min:0',
            'widgets.*.width' => 'required|integer|min:1|max:12',
            'widgets.*.height' => 'required|integer|min:1|max:12',
            'widgets.*.is_visible' => 'required|boolean',
            'widgets.*.config' => 'nullable|array',
        ]);

        $admin = $request->user();

        DB::transaction(function () use ($admin, $validated) {
            // Delete existing widgets
            AdminDashboardWidget::where('admin_id', $admin->id)->delete();

            // Create new widgets
            foreach ($validated['widgets'] as $widget) {
                AdminDashboardWidget::create([
                    'admin_id' => $admin->id,
                    'widget_type' => $widget['widget_type'],
                    'position' => $widget['position'],
                    'width' => $widget['width'],
                    'height' => $widget['height'],
                    'is_visible' => $widget['is_visible'],
                    'config' => $widget['config'] ?? null,
                ]);
            }
        });

        return response()->json([
            'message' => 'Dashboard layout saved successfully',
        ]);
    }

    /**
     * Reset dashboard to default
     * POST /api/v1/admin/dashboard/reset
     */
    public function resetDashboard(Request $request)
    {
        $admin = $request->user();

        AdminDashboardWidget::where('admin_id', $admin->id)->delete();

        return response()->json([
            'message' => 'Dashboard reset to default',
            'widgets' => $this->getDefaultWidgets(),
        ]);
    }

    /**
     * Get available widget types
     * GET /api/v1/admin/dashboard/widget-types
     */
    public function getWidgetTypes()
    {
        $types = [
            [
                'type' => 'revenue_chart',
                'name' => 'Revenue Chart',
                'description' => '30-day revenue trend',
                'default_width' => 12,
                'default_height' => 4,
                'category' => 'financial',
            ],
            [
                'type' => 'user_stats',
                'name' => 'User Statistics',
                'description' => 'Total users and growth',
                'default_width' => 6,
                'default_height' => 3,
                'category' => 'users',
            ],
            [
                'type' => 'payment_stats',
                'name' => 'Payment Statistics',
                'description' => 'Payment success/failure rates',
                'default_width' => 6,
                'default_height' => 3,
                'category' => 'financial',
            ],
            [
                'type' => 'kyc_pending',
                'name' => 'Pending KYC',
                'description' => 'KYC submissions awaiting review',
                'default_width' => 4,
                'default_height' => 3,
                'category' => 'compliance',
            ],
            [
                'type' => 'withdrawal_queue',
                'name' => 'Withdrawal Queue',
                'description' => 'Pending withdrawal requests',
                'default_width' => 4,
                'default_height' => 3,
                'category' => 'financial',
            ],
            [
                'type' => 'recent_activity',
                'name' => 'Recent Activity',
                'description' => 'Latest user activities',
                'default_width' => 4,
                'default_height' => 3,
                'category' => 'monitoring',
            ],
            [
                'type' => 'subscription_stats',
                'name' => 'Subscription Stats',
                'description' => 'Active subscriptions overview',
                'default_width' => 6,
                'default_height' => 3,
                'category' => 'business',
            ],
            [
                'type' => 'system_health',
                'name' => 'System Health',
                'description' => 'Server and database status',
                'default_width' => 6,
                'default_height' => 3,
                'category' => 'monitoring',
            ],
            [
                'type' => 'error_tracking',
                'name' => 'Error Tracking',
                'description' => 'Recent application errors',
                'default_width' => 12,
                'default_height' => 4,
                'category' => 'monitoring',
            ],
            [
                'type' => 'queue_monitor',
                'name' => 'Queue Monitor',
                'description' => 'Background job status',
                'default_width' => 6,
                'default_height' => 3,
                'category' => 'monitoring',
            ],
        ];

        return response()->json(['widget_types' => $types]);
    }

    /**
     * Get admin preferences
     * GET /api/v1/admin/preferences
     */
    public function getPreferences(Request $request)
    {
        $admin = $request->user();

        $preferences = AdminPreference::where('admin_id', $admin->id)
            ->get()
            ->pluck('value', 'key');

        // Set defaults if not set
        $defaults = [
            'dark_mode' => 'false',
            'sidebar_collapsed' => 'false',
            'notifications_enabled' => 'true',
            'auto_refresh' => 'true',
            'refresh_interval' => '30',
            'timezone' => 'Asia/Kolkata',
            'date_format' => 'Y-m-d',
            'currency_format' => 'INR',
        ];

        $merged = array_merge($defaults, $preferences->toArray());

        return response()->json(['preferences' => $merged]);
    }

    /**
     * Update admin preference
     * PUT /api/v1/admin/preferences
     */
    public function updatePreference(Request $request)
    {
        $validated = $request->validate([
            'key' => 'required|string|in:dark_mode,sidebar_collapsed,notifications_enabled,auto_refresh,refresh_interval,timezone,date_format,currency_format',
            'value' => 'required|string',
        ]);

        $admin = $request->user();

        AdminPreference::updateOrCreate(
            [
                'admin_id' => $admin->id,
                'key' => $validated['key'],
            ],
            [
                'value' => $validated['value'],
            ]
        );

        return response()->json([
            'message' => 'Preference updated successfully',
        ]);
    }

    /**
     * Bulk update preferences
     * POST /api/v1/admin/preferences/bulk
     */
    public function bulkUpdatePreferences(Request $request)
    {
        $validated = $request->validate([
            'preferences' => 'required|array',
            'preferences.*.key' => 'required|string',
            'preferences.*.value' => 'required|string',
        ]);

        $admin = $request->user();

        DB::transaction(function () use ($admin, $validated) {
            foreach ($validated['preferences'] as $pref) {
                AdminPreference::updateOrCreate(
                    [
                        'admin_id' => $admin->id,
                        'key' => $pref['key'],
                    ],
                    [
                        'value' => $pref['value'],
                    ]
                );
            }
        });

        return response()->json([
            'message' => 'Preferences updated successfully',
        ]);
    }

    /**
     * Get default widgets configuration
     */
    private function getDefaultWidgets()
    {
        return [
            [
                'widget_type' => 'revenue_chart',
                'position' => 0,
                'width' => 12,
                'height' => 4,
                'is_visible' => true,
                'config' => null,
            ],
            [
                'widget_type' => 'user_stats',
                'position' => 1,
                'width' => 6,
                'height' => 3,
                'is_visible' => true,
                'config' => null,
            ],
            [
                'widget_type' => 'payment_stats',
                'position' => 2,
                'width' => 6,
                'height' => 3,
                'is_visible' => true,
                'config' => null,
            ],
            [
                'widget_type' => 'kyc_pending',
                'position' => 3,
                'width' => 4,
                'height' => 3,
                'is_visible' => true,
                'config' => null,
            ],
            [
                'widget_type' => 'withdrawal_queue',
                'position' => 4,
                'width' => 4,
                'height' => 3,
                'is_visible' => true,
                'config' => null,
            ],
            [
                'widget_type' => 'recent_activity',
                'position' => 5,
                'width' => 4,
                'height' => 3,
                'is_visible' => true,
                'config' => null,
            ],
        ];
    }
}
