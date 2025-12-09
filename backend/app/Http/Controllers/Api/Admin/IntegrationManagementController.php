<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class IntegrationManagementController extends Controller
{
    /**
     * Get all integrations
     * GET /api/v1/admin/integrations
     */
    public function index()
    {
        $integrations = [
            'payment_gateways' => [
                'razorpay' => [
                    'enabled' => setting('payment_gateway_razorpay_enabled', false),
                    'configured' => !empty(setting('payment_gateway_razorpay_key')),
                ],
                'stripe' => [
                    'enabled' => setting('payment_gateway_stripe_enabled', false),
                    'configured' => !empty(setting('payment_gateway_stripe_key')),
                ],
                'paytm' => [
                    'enabled' => setting('payment_gateway_paytm_enabled', false),
                    'configured' => !empty(setting('payment_gateway_paytm_key', '')),
                ],
            ],
            'notification_services' => [
                'msg91' => [
                    'enabled' => setting('sms_provider') === 'msg91',
                    'configured' => !empty(setting('msg91_auth_key')),
                ],
                'email' => [
                    'enabled' => true,
                    'configured' => !empty(env('MAIL_USERNAME')),
                    'from' => setting('email_from_address', 'noreply@preipo-sip.com'),
                ],
            ],
            'captcha' => [
                'enabled' => setting('captcha_enabled', false),
                'provider' => setting('captcha_provider', 'recaptcha_v2'),
                'configured' => !empty(setting('captcha_secret_key')),
            ],
            'storage' => [
                'driver' => config('filesystems.default'),
                'configured' => true,
            ],
            'cache' => [
                'driver' => config('cache.default'),
                'enabled' => setting('cache_enabled', true),
            ],
        ];

        return response()->json(['integrations' => $integrations]);
    }

    /**
     * Test integration
     * POST /api/v1/admin/integrations/{type}/test
     */
    public function test($type, Request $request)
    {
        try {
            $result = match ($type) {
                'razorpay' => $this->testRazorpay(),
                'stripe' => $this->testStripe(),
                'msg91' => $this->testMsg91(),
                'email' => $this->testEmail(),
                'captcha' => $this->testCaptcha(),
                'cache' => $this->testCache(),
                default => throw new \Exception('Unknown integration type'),
            };

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    private function testRazorpay()
    {
        $key = setting('payment_gateway_razorpay_key');
        $secret = setting('payment_gateway_razorpay_secret');

        if (empty($key) || empty($secret)) {
            return ['success' => false, 'message' => 'Razorpay credentials not configured'];
        }

        // Test API connection (basic check)
        return ['success' => true, 'message' => 'Razorpay credentials configured'];
    }

    private function testStripe()
    {
        $key = setting('payment_gateway_stripe_key');

        if (empty($key)) {
            return ['success' => false, 'message' => 'Stripe credentials not configured'];
        }

        return ['success' => true, 'message' => 'Stripe credentials configured'];
    }

    private function testMsg91()
    {
        $authKey = setting('msg91_auth_key');

        if (empty($authKey)) {
            return ['success' => false, 'message' => 'MSG91 auth key not configured'];
        }

        return ['success' => true, 'message' => 'MSG91 credentials configured'];
    }

    private function testEmail()
    {
        try {
            \Mail::raw('Test email from PreIPO SIP', function ($message) {
                $message->to(setting('contact_email', 'test@example.com'))
                    ->subject('Test Email');
            });

            return ['success' => true, 'message' => 'Test email sent successfully'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Failed to send test email: ' . $e->getMessage()];
        }
    }

    private function testCaptcha()
    {
        if (!setting('captcha_enabled', false)) {
            return ['success' => false, 'message' => 'CAPTCHA is disabled'];
        }

        $secretKey = setting('captcha_secret_key');
        if (empty($secretKey)) {
            return ['success' => false, 'message' => 'CAPTCHA secret key not configured'];
        }

        return ['success' => true, 'message' => 'CAPTCHA configured'];
    }

    private function testCache()
    {
        try {
            $testKey = 'integration_test_' . time();
            $testValue = 'test_value';

            Cache::put($testKey, $testValue, 60);
            $retrieved = Cache::get($testKey);
            Cache::forget($testKey);

            if ($retrieved === $testValue) {
                return ['success' => true, 'message' => 'Cache is working correctly'];
            } else {
                return ['success' => false, 'message' => 'Cache test failed'];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Cache error: ' . $e->getMessage()];
        }
    }
}
