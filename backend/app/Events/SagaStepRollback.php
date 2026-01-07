<?php
/**
 * FIX 45: Saga Step Rollback Event
 *
 * Fired when a saga step needs to be rolled back.
 * Event listeners can implement custom rollback logic for specific steps.
 */

namespace App\Events;

use App\Models\PaymentSaga;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SagaStepRollback
{
    use Dispatchable, SerializesModels;

    public PaymentSaga $saga;
    public string $stepName;
    public array $stepData;

    /**
     * Create a new event instance.
     */
    public function __construct(PaymentSaga $saga, string $stepName, array $stepData)
    {
        $this->saga = $saga;
        $this->stepName = $stepName;
        $this->stepData = $stepData;
    }
}
