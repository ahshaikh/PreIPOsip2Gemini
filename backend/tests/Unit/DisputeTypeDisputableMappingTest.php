<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Enums\DisputeType;
use App\Models\Payment;
use App\Models\UserInvestment;
use App\Models\Withdrawal;
use App\Models\BonusTransaction;
use App\Models\Allocation;

class DisputeTypeDisputableMappingTest extends TestCase
{
    #[\PHPUnit\Framework\Attributes\Test]
    public function confusion_type_allows_investment_and_bonus()
    {
        $type = DisputeType::A_CONFUSION;
        $allowed = $type->allowedDisputableTypes();

        $this->assertContains(\App\Models\Investment::class, $allowed);
        $this->assertContains(\App\Models\BonusTransaction::class, $allowed);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function confusion_type_allows_payment()
    {
        // Confusion disputes can be attached to any entity type including Payment
        // This is by design - users can be confused about any transaction
        $type = DisputeType::A_CONFUSION;

        $this->assertTrue($type->isDisputableTypeAllowed(Payment::class));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function payment_type_allows_only_payment()
    {
        $type = DisputeType::B_PAYMENT;
        $allowed = $type->allowedDisputableTypes();

        $this->assertContains(Payment::class, $allowed);
        $this->assertContains(Withdrawal::class, $allowed);
        $this->assertNotContains(\App\Models\Investment::class, $allowed);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function allocation_type_allows_investment_and_allocation()
    {
        $type = DisputeType::C_ALLOCATION;
        $allowed = $type->allowedDisputableTypes();

        $this->assertContains(\App\Models\Investment::class, $allowed);
        $this->assertContains(Allocation::class, $allowed);
        $this->assertNotContains(Payment::class, $allowed);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function fraud_type_allows_payment_and_withdrawal()
    {
        $type = DisputeType::D_FRAUD;
        $allowed = $type->allowedDisputableTypes();

        $this->assertContains(Payment::class, $allowed);
        $this->assertContains(Withdrawal::class, $allowed);
        $this->assertContains(\App\Models\Investment::class, $allowed);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function validate_combination_throws_for_invalid_mapping()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('cannot be attached to');

        // Payment type cannot be attached to Investment
        DisputeType::validateCombination('payment', \App\Models\Investment::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function validate_combination_passes_for_valid_mapping()
    {
        // Should not throw
        DisputeType::validateCombination('payment', Payment::class);
        DisputeType::validateCombination('allocation', \App\Models\Investment::class);
        DisputeType::validateCombination('fraud', Payment::class);

        $this->assertTrue(true); // If we get here, no exception was thrown
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function from_string_returns_correct_enum()
    {
        $this->assertEquals(DisputeType::A_CONFUSION, DisputeType::fromString('confusion'));
        $this->assertEquals(DisputeType::B_PAYMENT, DisputeType::fromString('payment'));
        $this->assertEquals(DisputeType::C_ALLOCATION, DisputeType::fromString('allocation'));
        $this->assertEquals(DisputeType::D_FRAUD, DisputeType::fromString('fraud'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function from_string_throws_for_invalid_type()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid dispute type');

        DisputeType::fromString('invalid_type');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function risk_levels_are_ordered_correctly()
    {
        $this->assertEquals(1, DisputeType::A_CONFUSION->riskScore());
        $this->assertEquals(2, DisputeType::B_PAYMENT->riskScore());
        $this->assertEquals(3, DisputeType::C_ALLOCATION->riskScore());
        $this->assertEquals(4, DisputeType::D_FRAUD->riskScore());

        // Verify ordering
        $this->assertLessThan(
            DisputeType::D_FRAUD->riskScore(),
            DisputeType::A_CONFUSION->riskScore()
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function fraud_requires_immediate_escalation()
    {
        $this->assertTrue(DisputeType::D_FRAUD->requiresImmediateEscalation());
        $this->assertFalse(DisputeType::A_CONFUSION->requiresImmediateEscalation());
        $this->assertFalse(DisputeType::B_PAYMENT->requiresImmediateEscalation());
        $this->assertFalse(DisputeType::C_ALLOCATION->requiresImmediateEscalation());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function sla_hours_increase_with_lower_risk()
    {
        // Lower risk = more time allowed
        $this->assertGreaterThan(
            DisputeType::D_FRAUD->slaHours(),
            DisputeType::A_CONFUSION->slaHours()
        );

        $this->assertGreaterThan(
            DisputeType::D_FRAUD->slaHours(),
            DisputeType::B_PAYMENT->slaHours()
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function high_risk_types_returns_correct_types()
    {
        $highRisk = DisputeType::highRiskTypes();

        $this->assertContains(DisputeType::C_ALLOCATION, $highRisk);
        $this->assertContains(DisputeType::D_FRAUD, $highRisk);
        $this->assertNotContains(DisputeType::A_CONFUSION, $highRisk);
        $this->assertNotContains(DisputeType::B_PAYMENT, $highRisk);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function values_returns_all_type_strings()
    {
        $values = DisputeType::values();

        $this->assertContains('confusion', $values);
        $this->assertContains('payment', $values);
        $this->assertContains('allocation', $values);
        $this->assertContains('fraud', $values);
        $this->assertCount(4, $values);
    }
}
