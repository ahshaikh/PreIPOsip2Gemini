<?php
// V-FINAL-1730-629 (Created for Manual KYC)

namespace App\Events;

use App\Models\UserKyc;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class KycStatusUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public UserKyc $kyc;
    public string $oldStatus;

    public function __construct(UserKyc $kyc, string $oldStatus)
    {
        $this->kyc = $kyc;
        $this->oldStatus = $oldStatus;
    }
}
