<?php
namespace App\Domains\Identity\Actions;
use App\Models\User;

class LoginUserAction {
    public function execute(User $user, string $ip): array { return []; }
    public function issueToken(User $user, string $ip): array { return []; }
    // Log::info('[AUDIT] CP-1: Login Action Started', ['input' => $data['login'] ?? 'N/A']);
}