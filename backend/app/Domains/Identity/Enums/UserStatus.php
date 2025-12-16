<?php

namespace App\Domains\Identity\Enums;

enum UserStatus: string
{
    case ACTIVE = 'active';
    case PENDING = 'pending';
    case SUSPENDED = 'suspended';
    case BANNED = 'banned';
    case BLOCKED = 'blocked';
    case DELETED = 'deleted';

    public function label(): string
    {
        return match($this) {
            self::ACTIVE => 'Active',
            self::PENDING => 'Pending Verification',
            self::SUSPENDED => 'Suspended',
            self::BANNED => 'Permanently Banned',
            self::BLOCKED => 'Blocked',
            self::DELETED => 'Deleted',
        };
    }
}
