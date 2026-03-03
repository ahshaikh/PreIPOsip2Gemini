<?php

namespace App\Enums;

/**
 * DisputeType - Classification of disputes with risk levels and allowed disputable mappings
 *
 * Each type has:
 * - A risk level (low, medium, high, critical)
 * - Allowed disputable_type mappings (what models can have this dispute type)
 * - Auto-escalation rules based on risk
 *
 * DisputeType ↔ DisputableType validation ensures only valid combinations are created.
 */
enum DisputeType: string
{
    case A_CONFUSION = 'confusion';           // Low risk - UI/UX confusion, misunderstanding
    case B_PAYMENT = 'payment';               // Medium risk - Payment failures, delays, discrepancies
    case C_ALLOCATION = 'allocation';         // High risk - Share allocation errors, quantity mismatches
    case D_FRAUD = 'fraud';                   // Critical risk - Suspected fraud, unauthorized transactions

    /**
     * Get the risk level for this dispute type.
     */
    public function riskLevel(): string
    {
        return match ($this) {
            self::A_CONFUSION => 'low',
            self::B_PAYMENT => 'medium',
            self::C_ALLOCATION => 'high',
            self::D_FRAUD => 'critical',
        };
    }

    /**
     * Get the numeric risk score (1-4) for sorting and comparison.
     */
    public function riskScore(): int
    {
        return match ($this) {
            self::A_CONFUSION => 1,
            self::B_PAYMENT => 2,
            self::C_ALLOCATION => 3,
            self::D_FRAUD => 4,
        };
    }

    /**
     * Get allowed disputable_type values for this dispute type.
     *
     * This enforces the DisputeType ↔ DisputableType mapping.
     *
     * @return array<string> Fully qualified model class names
     */
    public function allowedDisputableTypes(): array
    {
        return match ($this) {
            self::A_CONFUSION => [
                \App\Models\Payment::class,
                \App\Models\Investment::class,
                \App\Models\Withdrawal::class,
                \App\Models\BonusTransaction::class,
                \App\Models\Allocation::class,
            ],
            self::B_PAYMENT => [
                \App\Models\Payment::class,
                \App\Models\Withdrawal::class,
            ],
            self::C_ALLOCATION => [
                \App\Models\Investment::class,
                \App\Models\Allocation::class,
            ],
            self::D_FRAUD => [
                \App\Models\Payment::class,
                \App\Models\Investment::class,
                \App\Models\Withdrawal::class,
                \App\Models\BonusTransaction::class,
                \App\Models\Allocation::class,
            ],
        };
    }

    /**
     * Check if the given disputable_type is allowed for this dispute type.
     */
    public function isDisputableTypeAllowed(string $disputableType): bool
    {
        return in_array($disputableType, $this->allowedDisputableTypes(), true);
    }

    /**
     * Get the SLA hours for initial response based on risk level.
     */
    public function slaHours(): int
    {
        return match ($this) {
            self::A_CONFUSION => 72,   // 3 days
            self::B_PAYMENT => 48,     // 2 days
            self::C_ALLOCATION => 24,  // 1 day
            self::D_FRAUD => 4,        // 4 hours - immediate attention
        };
    }

    /**
     * Get the auto-escalation timeout in hours.
     * After this time without resolution, dispute auto-escalates.
     */
    public function autoEscalationHours(): int
    {
        return match ($this) {
            self::A_CONFUSION => 168,  // 7 days
            self::B_PAYMENT => 120,    // 5 days
            self::C_ALLOCATION => 72,  // 3 days
            self::D_FRAUD => 24,       // 1 day
        };
    }

    /**
     * Check if this dispute type requires immediate escalation on creation.
     */
    public function requiresImmediateEscalation(): bool
    {
        return $this === self::D_FRAUD;
    }

    /**
     * Get human-readable label for display.
     */
    public function label(): string
    {
        return match ($this) {
            self::A_CONFUSION => 'General Confusion / Inquiry',
            self::B_PAYMENT => 'Payment Issue',
            self::C_ALLOCATION => 'Allocation Discrepancy',
            self::D_FRAUD => 'Fraud / Unauthorized Activity',
        };
    }

    /**
     * Get short code for internal tracking.
     */
    public function code(): string
    {
        return match ($this) {
            self::A_CONFUSION => 'A',
            self::B_PAYMENT => 'B',
            self::C_ALLOCATION => 'C',
            self::D_FRAUD => 'D',
        };
    }

    /**
     * Get CSS color class for UI display based on risk.
     */
    public function colorClass(): string
    {
        return match ($this) {
            self::A_CONFUSION => 'blue',
            self::B_PAYMENT => 'yellow',
            self::C_ALLOCATION => 'orange',
            self::D_FRAUD => 'red',
        };
    }

    /**
     * Get all dispute type values as array.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get dispute types that are high risk or above.
     *
     * @return array<DisputeType>
     */
    public static function highRiskTypes(): array
    {
        return [
            self::C_ALLOCATION,
            self::D_FRAUD,
        ];
    }

    /**
     * Create from string value with validation.
     *
     * @throws \InvalidArgumentException If value is not a valid dispute type
     */
    public static function fromString(string $value): self
    {
        $type = self::tryFrom($value);

        if ($type === null) {
            throw new \InvalidArgumentException(
                "Invalid dispute type: {$value}. Allowed values: " . implode(', ', self::values())
            );
        }

        return $type;
    }

    /**
     * Validate that a dispute type + disputable type combination is valid.
     *
     * @throws \InvalidArgumentException If combination is not allowed
     */
    public static function validateCombination(string $disputeType, string $disputableType): void
    {
        $type = self::fromString($disputeType);

        if (!$type->isDisputableTypeAllowed($disputableType)) {
            $allowed = implode(', ', array_map(
                fn($class) => class_basename($class),
                $type->allowedDisputableTypes()
            ));

            throw new \InvalidArgumentException(
                "Dispute type '{$disputeType}' cannot be attached to " . class_basename($disputableType) .
                ". Allowed: {$allowed}"
            );
        }
    }
}
