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

    /**
     * Send notifications to multiple users in batch.
     *
     * V-AUDIT-MODULE16-CRITICAL (PERFORMANCE): Fix N+1 Query Disaster
     *
     * Previous Issue:
     * For every user in the batch, dispatchNotification() would query:
     * - EmailTemplate::where('slug', $templateSlug)->exists() (Query 1)
     * - SmsTemplate::where('slug', $templateSlug)->exists() (Query 2)
     *
     * Impact: 1,000 users = 2,000 database queries during batch send
     * This crushed the database during high-volume events (e.g., "Market Open" alerts)
     *
     * Fix:
     * 1. Query templates ONCE before the loop
     * 2. Pre-determine enabled channels
     * 3. Pass channels array to dispatchNotification to avoid per-user queries
     *
     * Performance Improvement:
     * Before: 1,000 users = 2,000 template queries + 1 user query
     * After: 1,000 users = 2 template queries + 1 user query
     * Result: 99.9% reduction in database load for batch operations
     */
    public function sendBatch(array $userIds, string $templateSlug, array $variables = [])
    {
        // V-AUDIT-MODULE16-CRITICAL: Query templates ONCE before the loop
        // This eliminates 2 queries per user (N+1 query disaster)
        $hasEmailTemplate = EmailTemplate::where('slug', $templateSlug)->exists();
        $hasSmsTemplate = SmsTemplate::where('slug', $templateSlug)->exists();

        // Determine channels once for all users
        $channels = [];
        if ($hasEmailTemplate) {
            $channels[] = 'email';
        }
        if ($hasSmsTemplate) {
            $channels[] = 'sms';
        }

        // Fallback for critical system messages if no template found
        if (empty($channels)) {
            if (str_contains($templateSlug, 'otp') || str_contains($templateSlug, 'failed')) {
                $channels = ['email', 'sms'];
            } else {
                $channels = ['email'];
            }
        }

        // Fetch users in a single query
        $users = User::whereIn('id', $userIds)->get();

        foreach ($users as $user) {
            // Queue up one job per user, passing pre-determined channels
            // V-AUDIT-MODULE16-CRITICAL: No more database queries inside this loop
            $this->dispatchNotification($user, $templateSlug, $variables, $channels);
        }
    }

    /**
     * Dispatch notification for a single user.
     *
     * V-AUDIT-MODULE16-CRITICAL: Added $channels parameter to eliminate per-user template queries
     *
     * @param User $user The recipient
     * @param string $templateSlug Template identifier
     * @param array $variables Template variables
     * @param array|null $channels Pre-determined channels (for batch optimization)
     */
    private function dispatchNotification(User $user, string $templateSlug, array $variables, array $channels = null)
    {
        // V-AUDIT-MODULE16-CRITICAL: Use pre-determined channels if provided (batch send)
        // Otherwise query templates (single send)
        if ($channels === null) {
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