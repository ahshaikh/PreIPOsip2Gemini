<?php
namespace App\Domains\Identity\Actions;
use App\Models\User;

class LoginUserAction {
    public function execute(User $user, string $ip): array { return []; }
    public function issueToken(User $user, string $ip): array { return []; }
}