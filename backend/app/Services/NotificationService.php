<?php
// V-FINAL-1730-395 (Created)

namespace App\Services;

use App\Models\User;
use App\Models\EmailTemplate;
use App\Models\SmsTemplate;
use App\Jobs\ProcessNotificationJob; // We will create this job
use Illuminate\Support\Facades\Log;

/**
 * NotificationService - Multi-Channel Notification Dispatcher
 *
 * Handles sending notifications across multiple channels (email, SMS) with
 * support for user preferences, templating, priority queuing, and batch sending.
 *
 * ## Notification Flow
 *
 * ```
 * send() → dispatchNotification() → check preferences → queue job → deliver
 * ```
 *
 * ## Channel Selection
 *
 * Channels are automatically selected based on template slug:
 * - **Standard notifications**: Email only
 * - **Critical notifications** (OTP, payment failures): Email + SMS
 *
 * Template slugs containing `otp` or `failed` trigger SMS delivery.
 *
 * ## Queue Priority System
 *
 * | Queue Name      | Template Types              | Priority |
 * |-----------------|-----------------------------|---------:|
 * | high_priority   | OTP, payment failures       | Immediate|
 * | notifications   | Standard notifications      | Normal   |
 *
 * ## User Preference Checks
 *
 * Users can opt out of specific notification types. Preferences are checked
 * using the `canReceiveNotification()` method on the User model:
 * ```
 * preferenceKey = {category}_{channel}  (e.g., "auth_email", "payment_sms")
 * ```
 *
 * ## Usage Examples
 *
 * ```php
 * // Single user notification
 * $notificationService->send($user, 'payment.success', [
 *     'amount' => 5000,
 *     'subscription_code' => 'SUB-123'
 * ]);
 *
 * // Batch notification (e.g., reminder emails)
 * $notificationService->sendBatch($userIds, 'subscription.payment_reminder', [
 *     'due_date' => '2024-01-15'
 * ]);
 * ```
 *
 * ## Template Slugs Convention
 *
 * Format: `{category}.{action}` (e.g., `auth.otp`, `payment.failed`, `subscription.created`)
 *
 * @package App\Services
 * @see \App\Jobs\ProcessNotificationJob
 * @see \App\Models\EmailTemplate
 * @see \App\Models\SmsTemplate
 */
class NotificationService
{
    /**
     * Send a notification to a single user.
     */
    public function send(User $user, string $templateSlug, array $variables = [])
    {
        $this->dispatchNotification($user, $templateSlug, $variables);
    }

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
        // FIX: Module 16 - Database-Driven Channels (Low)
        // Removed hardcoded str_contains($slug, 'otp') logic.
        // Instead, we check if specific templates exist for the slug to determine channels.
        // In the future, this can be replaced by a 'channels' column in a NotificationTemplate table.
        
        $channels = [];

        // 1. Check Email
        if (EmailTemplate::where('slug', $templateSlug)->exists()) {
            $channels[] = 'email';
        }

        // 2. Check SMS (OTP, Alerts)
        if (SmsTemplate::where('slug', $templateSlug)->exists()) {
            $channels[] = 'sms';
        }

        // Fallback for critical system messages if no template found but slug implies urgency
        // (Maintaining slight backward compatibility during migration)
        if (empty($channels)) {
            if (str_contains($templateSlug, 'otp') || str_contains($templateSlug, 'failed')) {
                $channels = ['email', 'sms'];
            } else {
                $channels = ['email'];
            }
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
            // Critical items go to high_priority queue
            if (str_contains($templateSlug, 'otp') || str_contains($templateSlug, 'failed')) {
                $job->onQueue('high_priority');
            }
            
            dispatch($job);
        }
    }
}