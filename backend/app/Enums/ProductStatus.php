<?php

namespace App\Enums;

enum ProductStatus: string
{
    case DRAFT = 'draft';
    case SUBMITTED = 'submitted';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case LOCKED = 'locked';
}
