<?php

namespace App\Jobs;

use App\Models\WebhookEventLedger;
use App\Models\WebhookLog;
use App\Services\Webhooks\EventRouter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ProcessWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected WebhookLog $webhookLog,
        protected int $ledgerId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(EventRouter $eventRouter): void
    {
        $ledgerEntry = WebhookEventLedger::findOrFail($this->ledgerId);
        
        // 1. Institutional-Grade Anti-Double Execution Lock
        $lock = Cache::lock("webhook-event:{$ledgerEntry->provider}:{$ledgerEntry->event_id}", 30);

        try {
            if (!$lock->get()) {
                Log::warning("ProcessWebhookJob: Could not acquire lock for event {$ledgerEntry->event_id}. Possibly already processing.");
                return;
            }

            // 2. Check current status in ledger (Audit Protection)
            if ($ledgerEntry->processing_status === 'success') {
                Log::info("ProcessWebhookJob: Event {$ledgerEntry->event_id} already processed successfully. Skipping.");
                return;
            }

            // 3. Update status to processing
            $ledgerEntry->update(['processing_status' => 'processing']);
            $this->webhookLog->markAsProcessing();

            // 4. Resolve provider and process
            $provider = $ledgerEntry->provider;
            $event = $this->webhookLog->event_type;
            $data = $this->webhookLog->payload;

            // Use the EventRouter for decoupled processing
            $eventRouter->dispatch($provider, $event, $data);

            // 5. Success Tracking
            $ledgerEntry->update([
                'processing_status' => 'success',
                'processed_at' => now(),
            ]);
            $this->webhookLog->markAsSuccess(['message' => 'Institutional processing complete'], 200);

        } catch (\Exception $e) {
            Log::error("ProcessWebhookJob Error: {$e->getMessage()}", [
                'provider' => $ledgerEntry->provider,
                'event' => $ledgerEntry->event_id
            ]);

            $ledgerEntry->update(['processing_status' => 'failed']);
            $this->webhookLog->markAsFailed($e->getMessage(), 500);

            // Re-throw to trigger queue retry if needed, or handle based on business rules
            throw $e;
        } finally {
            $lock->release();
        }
    }
}
