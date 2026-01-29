<?php
/**
 * STORY 3.2: Disclosure Approved Event
 *
 * Fired when a company disclosure is approved.
 * Triggers automatic tier promotion check.
 */

namespace App\Events;

use App\Models\CompanyDisclosure;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DisclosureApproved
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public CompanyDisclosure $disclosure;
    public $approver;

    /**
     * Create a new event instance.
     *
     * @param CompanyDisclosure $disclosure The approved disclosure
     * @param mixed $approver The user who approved (User model or null for system)
     */
    public function __construct(CompanyDisclosure $disclosure, $approver = null)
    {
        $this->disclosure = $disclosure;
        $this->approver = $approver;
    }
}
