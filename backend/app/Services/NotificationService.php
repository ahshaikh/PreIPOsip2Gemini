<?php
// V-FINAL-1730-395 (Created)

namespace App\Services;

use App\Models\User;
use App\Models\EmailTemplate;
use App\Models\SmsTemplate;
use App\Jobs\ProcessNotificationJob; // We will create this job
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Send a notification to a single user.
     */
    public function send(User $user, string $templateSlug, array $variables = [])
    {
        $this->dispatchNotification($user, $templateSlug, $variables);
    }

    /**
     * Test: test_send_notification_batches_bulk_sends
     */
    public function sendBatch(array $userIds, string $templateSlug, array $variables = [])
    {
        $users = User::whereIn('id', $userIds)->get();
        foreach ($users as $user) {
            // Queue up one job per user
            $this->dispatchNotification($user, $templateSlug, $variables);
        }
    }

    private function dispatchNotification(User $user, string $templateSlug, array $variables)
    {
        // 1. Get Channel Priorities (FSD-SYS-118)
        // For now, we assume standard: Email first, SMS second (for critical)
        $channels = ['email'];
        if (str_contains($templateSlug, 'otp') || str_contains($templateSlug, 'failed')) {
            $channels[] = 'sms';
        }

        foreach ($channels as $channel) {
            // 2. Check User Preferences (Test: test_send_notification_respects_user_preferences)
            $preferenceKey = explode('.', $templateSlug)[0] . "_{$channel}"; // e.g., auth_email, auth_sms
            if (!$user->canReceiveNotification($preferenceKey)) {
                Log::info("Skipped notification {$templateSlug} to {$channel} for User #{$user->id} (opt-out).");
                continue;
            }

            // 3. Queue the Job (Test: test_send_notification_queues_for_async_delivery)
            $job = (new ProcessNotificationJob($user, $templateSlug, $channel, $variables))
                ->onQueue('notifications'); // Use a dedicated queue

            // 4. Respect Priority (Test: test_send_notification_respects_priority)
            if (str_contains($templateSlug, 'otp') || str_contains($templateSlug, 'failed')) {
                $job->onQueue('high_priority');
            }
            
            dispatch($job);
        }
    }
}