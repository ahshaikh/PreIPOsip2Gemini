<?php

namespace App\Domains\Identity\Enums;

enum UserStatus: string
{
    case ACTIVE = 'active';
    case PENDING = 'pending';       // Pending Email/Mobile Verification
    case SUSPENDED = 'suspended';   // Temporarily disabled by Admin
    case BANNED = 'banned';         // Permanently disabled
    case BLOCKED = 'blocked';       // Automated block (e.g. suspicious activity)
    case DELETED = 'deleted';       // Soft deleted

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