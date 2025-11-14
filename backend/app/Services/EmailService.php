<?php
// V-FINAL-1730-387 (Created)

namespace App\Services;

use App\Models\User;
use App\Models\EmailTemplate;
use App\Models\EmailLog;
use App\Jobs\ProcessEmailJob;
use Illuminate\Support\Facades\Log;

class EmailService
{
    /**
     * Main entry point for sending a transactional email.
     */
    public function send(User $user, string $templateSlug, array $variables = []): ?EmailLog
    {
        // Test: test_send_email_validates_recipient
        if (!$user || !$user->email) {
            Log::warning("EmailService: Aborted send. User null or no email.");
            return null;
        }

        // Test: test_send_email_respects_user_preferences
        if (!$this->canSendEmail($user, $templateSlug)) {
            Log::info("EmailService: Aborted send. User {$user->id} opted-out of '{$templateSlug}'.");
            return null;
        }

        // Test: test_send_email_uses_correct_template
        $template = EmailTemplate::where('slug', $templateSlug)->first();
        if (!$template) {
            Log::error("EmailService: Template '{$templateSlug}' not found.");
            return null;
        }

        // Test: test_send_email_replaces_variables
        // Add global variables
        $variables['user_name'] = $user->username;
        $variables['user_email'] = $user->email;
        $variables['site_name'] = setting('site_name', 'PreIPO SIP');
        
        $subject = $this->replaceVariables($template->subject, $variables);
        $body = $this->replaceVariables($template->body, $variables);

        // Test: test_send_email_logs_delivery
        $log = EmailLog::create([
            'user_id' => $user->id,
            'template_slug' => $templateSlug,
            'to_email' => $user->email,
            'subject' => $subject,
            'body' => $body,
            'status' => 'queued',
        ]);

        // Test: test_send_email_queues_for_async_delivery
        ProcessEmailJob::dispatch($log);

        return $log;
    }

    /**
     * Test: test_batch_email_sends_in_chunks
     */
    public function sendBatch(array $userIds, string $templateSlug, array $variables = [])
    {
        $users = User::whereIn('id', $userIds)->get();
        foreach ($users as $user) {
            $this->send($user, $templateSlug, $variables);
        }
    }

    /**
     * Checks if user has opted out of this specific email type.
     */
    private function canSendEmail(User $user, string $templateSlug): bool
    {
        // Map template slug to a preference key (e.g., "bonus.credited" -> "bonus_email")
        $key = explode('.', $templateSlug)[0] . '_email'; // Simplistic mapping
        
        $preference = $user->notificationPreferences()
                           ->where('preference_key', $key)
                           ->first();

        // Default to TRUE if no setting exists
        return $preference ? $preference->is_enabled : true;
    }

    /**
     * Simple string replacer.
     */
    private function replaceVariables(string $content, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $content = str_replace("{{{$key}}}", (string)$value, $content);
        }
        return $content;
    }
}