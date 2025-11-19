<?php
// V-FINAL-1730-536 (Created)

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\SmsService;
use App\Models\User;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * FSD-NOTIF-007: Send a test SMS using current settings.
     */
    public function sendTestSms(Request $request, SmsService $smsService)
    {
        $validated = $request->validate([
            'mobile' => 'required|string|regex:/^[0-9]{10}$/',
        ]);
        
        $user = $request->user();
        $message = "This is a test message from PreIPO SIP at " . now();
        $templateId = setting('msg91_dlt_te_id'); // Use DLT ID if available
        
        // Create a temporary "mock" user to send the SMS
        $mockUser = new User(['mobile' => $validated['mobile']]);

        // The SmsService will read the saved settings and attempt to send
        $log = $smsService->send(
            $mockUser,
            $message,
            'admin.test',
            $templateId
        );

        if ($log && $log->status === 'sent') {
            return response()->json(['message' => 'Test SMS sent successfully to ' . $validated['mobile']]);
        } else {
            return response()->json([
                'message' => 'SMS failed to send. Check logs and settings.',
                'error' => $log->error_message ?? 'Unknown error'
            ], 500);
        }
    }
}