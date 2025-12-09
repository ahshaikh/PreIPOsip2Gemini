<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\UserConsent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CookieConsentController extends Controller
{
    /**
     * Get cookie consent configuration
     * GET /api/v1/cookie-consent/config
     */
    public function getConfig()
    {
        $config = [
            'enabled' => setting('cookie_consent_enabled', true),
            'version' => setting('cookie_consent_version', '1.0'),
            'title' => setting('cookie_consent_title', 'We use cookies'),
            'message' => setting('cookie_consent_message'),
            'position' => setting('cookie_consent_position', 'bottom'),
            'theme' => setting('cookie_consent_theme', 'light'),
            'show_reject' => setting('cookie_consent_show_reject', true),
            'show_preferences' => setting('cookie_consent_show_preferences', true),
            'auto_hide_delay' => setting('cookie_consent_auto_hide_delay', 0),
            'revisit_consent' => setting('cookie_consent_revisit_consent', true),
            'expiry_days' => setting('cookie_consent_expiry_days', 365),
            'categories' => [
                'essential' => [
                    'enabled' => true, // Always enabled
                    'required' => true,
                    'name' => 'Essential Cookies',
                    'description' => 'These cookies are necessary for the website to function and cannot be switched off.',
                ],
                'analytics' => [
                    'enabled' => setting('cookies_analytics_enabled', true),
                    'required' => false,
                    'name' => 'Analytics Cookies',
                    'description' => 'These cookies help us understand how visitors interact with our website.',
                ],
                'marketing' => [
                    'enabled' => setting('cookies_marketing_enabled', false),
                    'required' => false,
                    'name' => 'Marketing Cookies',
                    'description' => 'These cookies are used to deliver personalized advertisements.',
                ],
                'preferences' => [
                    'enabled' => setting('cookies_preferences_enabled', true),
                    'required' => false,
                    'name' => 'Preference Cookies',
                    'description' => 'These cookies enable the website to remember your preferences.',
                ],
            ],
        ];

        return response()->json($config);
    }

    /**
     * Save user cookie consent preferences
     * POST /api/v1/cookie-consent/save
     */
    public function saveConsent(Request $request)
    {
        $validated = $request->validate([
            'essential' => 'boolean',
            'analytics' => 'boolean',
            'marketing' => 'boolean',
            'preferences' => 'boolean',
            'accept_all' => 'boolean',
            'reject_all' => 'boolean',
        ]);

        $consentData = [
            'type' => 'cookie_consent',
            'version' => setting('cookie_consent_version', '1.0'),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'consents' => [
                'essential' => true, // Always true
                'analytics' => $validated['accept_all'] ?? $validated['analytics'] ?? false,
                'marketing' => $validated['accept_all'] ?? $validated['marketing'] ?? false,
                'preferences' => $validated['accept_all'] ?? $validated['preferences'] ?? false,
            ],
            'accepted_at' => now(),
        ];

        // If user is logged in, save to database
        if (Auth::check()) {
            UserConsent::updateOrCreate(
                [
                    'user_id' => Auth::id(),
                    'consent_type' => 'cookie_consent',
                ],
                [
                    'consent_version' => $consentData['version'],
                    'consent_data' => $consentData['consents'],
                    'ip_address' => $consentData['ip_address'],
                    'user_agent' => $consentData['user_agent'],
                    'granted_at' => $consentData['accepted_at'],
                ]
            );
        }

        return response()->json([
            'message' => 'Cookie consent saved successfully',
            'consent' => $consentData,
        ]);
    }

    /**
     * Get user's current cookie consent
     * GET /api/v1/cookie-consent/status
     */
    public function getStatus()
    {
        if (!Auth::check()) {
            return response()->json([
                'has_consent' => false,
                'message' => 'Not authenticated',
            ]);
        }

        $consent = UserConsent::where('user_id', Auth::id())
            ->where('consent_type', 'cookie_consent')
            ->latest()
            ->first();

        if (!$consent) {
            return response()->json([
                'has_consent' => false,
                'needs_consent' => true,
            ]);
        }

        $currentVersion = setting('cookie_consent_version', '1.0');
        $needsUpdate = $consent->consent_version !== $currentVersion;

        return response()->json([
            'has_consent' => true,
            'needs_consent' => $needsUpdate,
            'consent_version' => $consent->consent_version,
            'current_version' => $currentVersion,
            'granted_at' => $consent->granted_at,
            'consents' => $consent->consent_data,
        ]);
    }

    /**
     * Withdraw cookie consent
     * POST /api/v1/cookie-consent/withdraw
     */
    public function withdrawConsent()
    {
        if (!Auth::check()) {
            return response()->json([
                'message' => 'Authentication required',
            ], 401);
        }

        $consent = UserConsent::where('user_id', Auth::id())
            ->where('consent_type', 'cookie_consent')
            ->latest()
            ->first();

        if ($consent) {
            $consent->update([
                'revoked_at' => now(),
                'consent_data' => [
                    'essential' => true,
                    'analytics' => false,
                    'marketing' => false,
                    'preferences' => false,
                ],
            ]);
        }

        return response()->json([
            'message' => 'Cookie consent withdrawn successfully',
        ]);
    }
}
