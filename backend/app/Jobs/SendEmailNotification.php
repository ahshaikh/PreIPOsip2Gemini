<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Models\AuditLog;

/**
 * FIX 15 (P3): Email Notification System (Queued Jobs)
 *
 * Base job for sending all email notifications asynchronously
 * Prevents blocking user requests and allows retry on failure
 */
class SendEmailNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $backoff = [60, 300, 900]; // 1min, 5min, 15min

    /**
     * Email recipient
     *
     * @var string
     */
    protected $recipientEmail;

    /**
     * Email recipient name
     *
     * @var string
     */
    protected $recipientName;

    /**
     * Email template name
     *
     * @var string
     */
    protected $template;

    /**
     * Email subject
     *
     * @var string
     */
    protected $subject;

    /**
     * Template data
     *
     * @var array
     */
    protected $data;

    /**
     * User ID for audit logging
     *
     * @var int|null
     */
    protected $userId;

    /**
     * Create a new job instance.
     */
    public function __construct(
        string $recipientEmail,
        string $recipientName,
        string $template,
        string $subject,
        array $data = [],
        ?int $userId = null
    ) {
        $this->recipientEmail = $recipientEmail;
        $this->recipientName = $recipientName;
        $this->template = $template;
        $this->subject = $subject;
        $this->data = $data;
        $this->userId = $userId;

        // Use 'emails' queue for better isolation
        $this->onQueue('emails');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Mail::send(
                "emails.{$this->template}",
                $this->data,
                function ($message) {
                    $message->to($this->recipientEmail, $this->recipientName)
                        ->subject($this->subject);

                    // Optional: Add CC, BCC, attachments
                    if (isset($this->data['cc'])) {
                        $message->cc($this->data['cc']);
                    }

                    if (isset($this->data['attachments'])) {
                        foreach ($this->data['attachments'] as $attachment) {
                            $message->attach($attachment);
                        }
                    }
                }
            );

            // Log successful email send
            Log::info('Email sent successfully', [
                'template' => $this->template,
                'recipient' => $this->recipientEmail,
                'subject' => $this->subject,
                'user_id' => $this->userId,
            ]);

            // Audit log
            if ($this->userId) {
                AuditLog::create([
                    'action' => 'email.sent',
                    'actor_id' => $this->userId,
                    'description' => "Email sent: {$this->subject}",
                    'metadata' => [
                        'template' => $this->template,
                        'recipient' => $this->recipientEmail,
                    ],
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Email sending failed', [
                'template' => $this->template,
                'recipient' => $this->recipientEmail,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);

            // Re-throw to trigger retry mechanism
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Email job failed after all retries', [
            'template' => $this->template,
            'recipient' => $this->recipientEmail,
            'error' => $exception->getMessage(),
            'user_id' => $this->userId,
        ]);

        // Optionally notify admin of critical email failure
        // You could dispatch another job to send admin alert
    }

    /**
     * Static helper methods for common email types
     */
    public static function welcome(string $email, string $name, array $data = []): void
    {
        static::dispatch(
            $email,
            $name,
            'welcome',
            'Welcome to PreIPOsip Investment Platform!',
            array_merge(['name' => $name], $data),
            $data['user_id'] ?? null
        );
    }

    public static function kycApproved(string $email, string $name, int $userId): void
    {
        static::dispatch(
            $email,
            $name,
            'kyc-approved',
            'KYC Verification Approved',
            ['name' => $name],
            $userId
        );
    }

    public static function kycRejected(string $email, string $name, string $reason, int $userId): void
    {
        static::dispatch(
            $email,
            $name,
            'kyc-rejected',
            'KYC Verification Requires Attention',
            ['name' => $name, 'reason' => $reason],
            $userId
        );
    }

    public static function withdrawalApproved(string $email, string $name, float $amount, string $reference, int $userId): void
    {
        static::dispatch(
            $email,
            $name,
            'withdrawal-approved',
            'Withdrawal Request Approved',
            [
                'name' => $name,
                'amount' => $amount,
                'reference' => $reference,
            ],
            $userId
        );
    }

    public static function withdrawalRejected(string $email, string $name, float $amount, string $reason, int $userId): void
    {
        static::dispatch(
            $email,
            $name,
            'withdrawal-rejected',
            'Withdrawal Request Declined',
            [
                'name' => $name,
                'amount' => $amount,
                'reason' => $reason,
            ],
            $userId
        );
    }

    public static function paymentSuccess(string $email, string $name, float $amount, string $paymentId, int $userId): void
    {
        static::dispatch(
            $email,
            $name,
            'payment-success',
            'Payment Successful',
            [
                'name' => $name,
                'amount' => $amount,
                'payment_id' => $paymentId,
                'date' => now()->format('d M Y, h:i A'),
            ],
            $userId
        );
    }

    public static function investmentAllocated(string $email, string $name, array $investmentDetails, int $userId): void
    {
        static::dispatch(
            $email,
            $name,
            'investment-allocated',
            'Investment Allocated Successfully',
            array_merge(['name' => $name], $investmentDetails),
            $userId
        );
    }

    public static function bonusCredited(string $email, string $name, float $amount, string $bonusType, int $userId): void
    {
        static::dispatch(
            $email,
            $name,
            'bonus-credited',
            'Bonus Credited to Your Account',
            [
                'name' => $name,
                'amount' => $amount,
                'bonus_type' => ucfirst($bonusType),
            ],
            $userId
        );
    }

    public static function passwordReset(string $email, string $name, string $resetUrl): void
    {
        static::dispatch(
            $email,
            $name,
            'password-reset',
            'Reset Your Password',
            [
                'name' => $name,
                'reset_url' => $resetUrl,
            ]
        );
    }

    public static function monthlyStatement(string $email, string $name, string $pdfPath, string $month, int $userId): void
    {
        static::dispatch(
            $email,
            $name,
            'monthly-statement',
            "Your Monthly Investment Statement - {$month}",
            [
                'name' => $name,
                'month' => $month,
                'attachments' => [storage_path('app/' . $pdfPath)],
            ],
            $userId
        );
    }
}
