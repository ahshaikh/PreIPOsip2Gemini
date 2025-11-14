<?php
// V-FINAL-1730-388 (Created)

namespace App\Jobs;

use App\Models\EmailLog;
use App\Mail\GenericMailable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class ProcessEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3; // Retry 3 times

    public function __construct(public EmailLog $log)
    {
    }

    /**
     * Test: test_send_email_handles_sendgrid_failure
     */
    public function handle(): void
    {
        $this->log->update(['status' => 'sending']);
        
        try {
            $mailable = new GenericMailable($this->log->subject, $this->log->body);
            Mail::to($this->log->to_email)->send($mailable);
            
            $this->log->update(['status' => 'sent']);

        } catch (\Exception $e) {
            $this->log->update([
                'status' => 'failed',
                'error_message' => $e->getMessage()
            ]);
            // Re-throw to make the job retry
            throw $e;
        }
    }
}