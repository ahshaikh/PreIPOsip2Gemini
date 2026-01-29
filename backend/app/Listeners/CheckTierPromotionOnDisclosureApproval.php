<?php
/**
 * STORY 3.2: Check Tier Promotion on Disclosure Approval
 *
 * Listener that triggers automatic tier promotion check
 * when a disclosure is approved.
 *
 * BEHAVIOR:
 * - Idempotent: safe to call multiple times
 * - Silent: does nothing if company not eligible for promotion
 * - Atomic: promotion is transactional
 */

namespace App\Listeners;

use App\Events\DisclosureApproved;
use App\Services\CompanyDisclosureTierService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class CheckTierPromotionOnDisclosureApproval implements ShouldQueue
{
    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * Handle the event.
     */
    public function handle(DisclosureApproved $event): void
    {
        $disclosure = $event->disclosure;
        $company = $disclosure->company;

        if (!$company) {
            Log::warning('DisclosureApproved event: company not found', [
                'disclosure_id' => $disclosure->id,
            ]);
            return;
        }

        $service = app(CompanyDisclosureTierService::class);

        $promoted = $service->tryAutomaticPromotion(
            $company,
            $event->approver,
            $disclosure->id
        );

        if ($promoted) {
            Log::info('Tier promotion triggered by disclosure approval', [
                'company_id' => $company->id,
                'disclosure_id' => $disclosure->id,
                'module_code' => $disclosure->disclosureModule?->code,
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(DisclosureApproved $event, \Throwable $exception): void
    {
        Log::error('CheckTierPromotionOnDisclosureApproval failed', [
            'disclosure_id' => $event->disclosure->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
