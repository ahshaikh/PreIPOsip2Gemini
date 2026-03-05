<?php

/**
 * MoneyValueObjectStrictnessTest
 *
 * INVARIANT: Money values should use a strict value object pattern.
 *
 * Recommends implementation of a Money value object that:
 * - Encapsulates amount in paise
 * - Prevents direct arithmetic on raw values
 * - Provides type-safe operations
 * - Makes unit explicit in the type
 *
 * @package Tests\FinancialLifecycle\MonetaryPrecision
 */

namespace Tests\FinancialLifecycle\MonetaryPrecision;

use Tests\FinancialLifecycle\FinancialLifecycleTestCase;
use App\Models\Payment;

class MoneyValueObjectStrictnessTest extends FinancialLifecycleTestCase
{
    /**
     * Test that Money value object exists.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function money_value_object_exists(): void
    {
        // After refactor, a Money value object should exist
        $moneyClass = \App\ValueObjects\Money::class;

        $this->assertTrue(
            class_exists($moneyClass),
            "Money value object not found at {$moneyClass}. " .
            "Create a Money class to encapsulate monetary values with type safety."
        );
    }

    /**
     * Test Money value object immutability.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function money_is_immutable(): void
    {
        if (!class_exists(\App\ValueObjects\Money::class)) {
            $this->markTestSkipped('Money value object not yet implemented');
        }

        $money1 = new \App\ValueObjects\Money(100000);
        $money2 = $money1->add(new \App\ValueObjects\Money(50000));

        // Original should be unchanged
        $this->assertEquals(
            100000,
            $money1->getPaise(),
            "Money should be immutable - add() must return new instance"
        );

        // New instance has sum
        $this->assertEquals(
            150000,
            $money2->getPaise(),
            "add() should return new Money with summed value"
        );
    }

    /**
     * Test Money prevents invalid values.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function money_rejects_invalid_values(): void
    {
        if (!class_exists(\App\ValueObjects\Money::class)) {
            $this->markTestSkipped('Money value object not yet implemented');
        }

        // Float should be rejected
        $this->expectException(\InvalidArgumentException::class);
        new \App\ValueObjects\Money(100.50);
    }

    /**
     * Test Money arithmetic operations.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function money_arithmetic_operations(): void
    {
        if (!class_exists(\App\ValueObjects\Money::class)) {
            $this->markTestSkipped('Money value object not yet implemented');
        }

        $money = new \App\ValueObjects\Money(100000);

        // Addition
        $sum = $money->add(new \App\ValueObjects\Money(50000));
        $this->assertEquals(150000, $sum->getPaise());

        // Subtraction
        $diff = $money->subtract(new \App\ValueObjects\Money(30000));
        $this->assertEquals(70000, $diff->getPaise());

        // Multiplication by integer
        $product = $money->multiply(3);
        $this->assertEquals(300000, $product->getPaise());
    }

    /**
     * Test Money comparison operations.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function money_comparison_operations(): void
    {
        if (!class_exists(\App\ValueObjects\Money::class)) {
            $this->markTestSkipped('Money value object not yet implemented');
        }

        $money1 = new \App\ValueObjects\Money(100000);
        $money2 = new \App\ValueObjects\Money(100000);
        $money3 = new \App\ValueObjects\Money(50000);

        $this->assertTrue($money1->equals($money2));
        $this->assertFalse($money1->equals($money3));
        $this->assertTrue($money1->isGreaterThan($money3));
        $this->assertTrue($money3->isLessThan($money1));
    }

    /**
     * Test Money factory methods.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function money_factory_methods(): void
    {
        if (!class_exists(\App\ValueObjects\Money::class)) {
            $this->markTestSkipped('Money value object not yet implemented');
        }

        // From paise
        $fromPaise = \App\ValueObjects\Money::fromPaise(500050);
        $this->assertEquals(500050, $fromPaise->getPaise());

        // From rupees (with explicit conversion)
        $fromRupees = \App\ValueObjects\Money::fromRupees(5000.50);
        $this->assertEquals(500050, $fromRupees->getPaise());

        // Zero
        $zero = \App\ValueObjects\Money::zero();
        $this->assertEquals(0, $zero->getPaise());
    }

    /**
     * Test Money prevents negative values (optional constraint).
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function money_handles_negative_appropriately(): void
    {
        if (!class_exists(\App\ValueObjects\Money::class)) {
            $this->markTestSkipped('Money value object not yet implemented');
        }

        // Option 1: Reject negative
        // Option 2: Allow negative (for credits/debits)
        // Document the chosen approach

        try {
            $negative = new \App\ValueObjects\Money(-100000);
            // If allowed, verify it works correctly
            $this->assertEquals(-100000, $negative->getPaise());
        } catch (\InvalidArgumentException $e) {
            // If rejected, that's also valid
            $this->assertStringContainsString('negative', strtolower($e->getMessage()));
        }
    }

    /**
     * Test Money serialization.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function money_serializable(): void
    {
        if (!class_exists(\App\ValueObjects\Money::class)) {
            $this->markTestSkipped('Money value object not yet implemented');
        }

        $money = new \App\ValueObjects\Money(500050);

        // JSON serialization
        if (method_exists($money, 'toArray')) {
            $array = $money->toArray();
            $this->assertArrayHasKey('paise', $array);
            $this->assertEquals(500050, $array['paise']);
        }

        // Database casting
        if (method_exists($money, '__toString')) {
            $string = (string) $money;
            $this->assertIsString($string);
        }
    }

    /**
     * Test Money display formatting.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function money_display_formatting(): void
    {
        if (!class_exists(\App\ValueObjects\Money::class)) {
            $this->markTestSkipped('Money value object not yet implemented');
        }

        $money = new \App\ValueObjects\Money(500050);

        if (method_exists($money, 'format')) {
            $formatted = $money->format();
            // Should return something like "₹5,000.50"
            $this->assertStringContainsString('5', $formatted);
        }

        if (method_exists($money, 'toRupees')) {
            $rupees = $money->toRupees();
            $this->assertEquals(5000.50, $rupees);
        }
    }

    /**
     * Test that services should accept Money objects.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function services_should_accept_money_objects(): void
    {
        if (!class_exists(\App\ValueObjects\Money::class)) {
            $this->markTestSkipped('Money value object not yet implemented');
        }

        $this->createTestUser();

        // After refactor, WalletService should accept Money
        // walletService->deposit($user, Money::fromPaise(100000), ...)

        $this->markTestIncomplete(
            "Services should be refactored to accept Money value objects " .
            "instead of raw integers/floats. This ensures type safety at compile time."
        );
    }

    /**
     * Test Money prevents unit confusion.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function money_prevents_unit_confusion(): void
    {
        if (!class_exists(\App\ValueObjects\Money::class)) {
            $this->markTestSkipped('Money value object not yet implemented');
        }

        // The type system should prevent mixing rupees and paise
        // By encapsulating in Money, we always know the unit

        $paise = \App\ValueObjects\Money::fromPaise(100);
        $rupees = \App\ValueObjects\Money::fromRupees(1);

        // Both represent the same value
        $this->assertTrue($paise->equals($rupees));

        // No confusion about what 100 means
        $this->assertEquals(100, $paise->getPaise());
        $this->assertEquals(100, $rupees->getPaise());
    }
}
