<?php
namespace App\Domains\Identity\Actions;

class VerifyOtpAction {
    public function execute(int $userId, string $type, string $otp): array { return []; }
}