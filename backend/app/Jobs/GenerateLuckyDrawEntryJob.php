<?php
// V-PHASE3-1730-086

namespace App\Jobs;

use App\Models\Payment;
use App\Models\LuckyDraw;
use App\Models\LuckyDrawEntry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class GenerateLuckyDrawEntryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Payment $payment)
    {
    }

    public function handle(): void
    {
        if (!setting('lucky_draw_enabled', true)) {
            return;
        }

        $currentDraw = LuckyDraw::where('status', 'open')
                                ->where('draw_date', '>=', now())
                                ->first();
        
        if (!$currentDraw) {
            return; // No active draw
        }

        // Get entry count from plan config
        $plan = $this->payment->subscription->plan;
        $config = $plan->configs->where('config_key', 'lucky_draw_entries')->first();
        $entryCount = $config ? $config->value['count'] : 1; // Default to 1

        for ($i = 0; $i < $entryCount; $i++) {
            LuckyDrawEntry::create([
                'user_id' => $this->payment->user_id,
                'lucky_draw_id' => $currentDraw->id,
                'payment_id' => $this->payment->id,
                'entry_code' => Str::random(8), // Unique code
            ]);
        }
    }
}