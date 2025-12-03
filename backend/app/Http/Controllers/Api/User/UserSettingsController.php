<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserSettingsController extends Controller
{
    /**
     * Get user settings
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Get or create user settings from database
        $settings = DB::table('user_settings')->where('user_id', $user->id)->first();

        if (!$settings) {
            // Return default settings
            return response()->json([
                'success' => true,
                'data' => [
                    'notifications' => [
                        'email_notifications' => true,
                        'sms_notifications' => true,
                        'push_notifications' => true,
                        'payment_alerts' => true,
                        'investment_updates' => true,
                        'promotional_emails' => false,
                        'weekly_summary' => true,
                        'kyc_updates' => true,
                        'withdrawal_alerts' => true,
                        'bonus_alerts' => true,
                    ],
                    'security' => [
                        'two_factor_enabled' => false,
                        'email_verification' => true,
                        'login_alerts' => true,
                        'session_timeout' => 30,
                    ],
                    'preferences' => [
                        'language' => 'en',
                        'currency' => 'INR',
                        'timezone' => 'Asia/Kolkata',
                        'theme' => 'light',
                        'date_format' => 'DD/MM/YYYY',
                        'number_format' => 'en-IN',
                    ],
                ],
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => json_decode($settings->settings, true),
        ]);
    }

    /**
     * Update user settings
     */
    public function update(Request $request)
    {
        $user = $request->user();

        // Get current settings
        $currentSettings = DB::table('user_settings')->where('user_id', $user->id)->first();

        $currentData = $currentSettings ? json_decode($currentSettings->settings, true) : [
            'notifications' => [],
            'security' => [],
            'preferences' => [],
        ];

        // Merge new settings
        $newData = $currentData;

        if ($request->has('notifications')) {
            $newData['notifications'] = array_merge($currentData['notifications'] ?? [], $request->notifications);
        }

        if ($request->has('security')) {
            $newData['security'] = array_merge($currentData['security'] ?? [], $request->security);
        }

        if ($request->has('preferences')) {
            $newData['preferences'] = array_merge($currentData['preferences'] ?? [], $request->preferences);
        }

        // Save settings
        if ($currentSettings) {
            DB::table('user_settings')
                ->where('user_id', $user->id)
                ->update([
                    'settings' => json_encode($newData),
                    'updated_at' => now(),
                ]);
        } else {
            DB::table('user_settings')->insert([
                'user_id' => $user->id,
                'settings' => json_encode($newData),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Settings updated successfully',
            'data' => $newData,
        ]);
    }
}
