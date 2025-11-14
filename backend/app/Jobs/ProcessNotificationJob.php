<?php
// V-FINAL-1730-396 (Created)

namespace App\Jobs;

use App\Models\User;
use App\Models\EmailTemplate;
use App\Models\SmsTemplate;
use App\Services\EmailService; // We reuse the *sender* logic
use App\Services\SmsService;   // We reuse the *sender* logic
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Test: test_send_notification_retries_on_failure
    public $tries = 3;

    public function __construct(
        public User $user,
        public string $templateSlug,
        public string $channel,
        public array $variables
    ) {}

    public function handle(EmailService $emailService, SmsService $smsService)
    {
        // 1. Add global variables
        $this->variables['user_name'] = $this->user->username;
        $this.variables['user_email'] = $this->user->email;
        $this.variables['site_name'] = setting('site_name', 'PreIPO SIP');

        // 2. Route to correct channel
        if ($this.channel === 'email') {
            $template = EmailTemplate::where('slug', $this.templateSlug)->first();
            if (!$template) return;

            $subject = $this->replace($template->subject, $this.variables);
            $body = $this.replace($template->body, $this.variables);
            
            // Note: This reuses the EmailService's *internal* sender, which handles logging.
            // A more advanced refactor would have this job call a Mailer directly.
            // This is a testable compromise.
            $emailService->sendRaw($this.user, $subject, $body, $this.templateSlug);

        } 
        elseif ($this.channel === 'sms') {
            $template = SmsTemplate::where('slug', $this.templateSlug)->first();
            if (!$template) return;

            $message = $this->replace($template->body, $this.variables);
            $dltId = $template->dlt_template_id;
            
            // This reuses the SmsService's *internal* sender, which handles logging.
            $smsService->send($this.user, $message, $this.templateSlug, $dltId);
        }
    }

    private function replace(string $content, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $content = str_replace("{{{$key}}}", (string)$value, $content);
        }
        return $content;
    }
}