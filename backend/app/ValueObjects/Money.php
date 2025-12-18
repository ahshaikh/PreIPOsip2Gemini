<?php

namespace App\ValueObjects;

/**
 * Money Value Object
 * * [AUDIT FIX]: Prevents float math issues by working strictly with integers (Paise).
 */
class Money
{
    public function __construct(protected int $amountPaise) {}

    public static function fromRupees(float $rupees): self {
        return new self((int) round($rupees * 100));
    }

    public function toRupees(): float {
        return $this->amountPaise / 100;
    }

    public function getAmount(): int {
        return $this->amountPaise;
    }
}