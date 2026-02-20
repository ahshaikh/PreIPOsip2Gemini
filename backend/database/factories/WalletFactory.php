<?php
// V-PHASE2-HARDENING-2026 (Invariant Enforcement)

namespace Database\Factories;

use App\Models\Wallet;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * WalletFactory
 *
 * INVARIANTS ENFORCED:
 * 1. balance_paise >= 0
 * 2. locked_balance_paise >= 0
 * 3. balance_paise >= locked_balance_paise (CRITICAL)
 * 4. available_balance_paise = balance_paise - locked_balance_paise
 */
class WalletFactory extends Factory
{
    protected $model = Wallet::class;

    public function definition(): array
    {
        // Generate balance first, then locked as subset
        $balancePaise = $this->faker->numberBetween(0, 10000000); // 0 to ₹1,00,000

        // INVARIANT 3: locked_balance_paise MUST be <= balance_paise
        $maxLocked = max(0, $balancePaise);
        $lockedBalancePaise = $this->faker->numberBetween(0, $maxLocked);

        return [
            'user_id' => User::factory(),
            'balance_paise' => $balancePaise,
            'locked_balance_paise' => $lockedBalancePaise,
        ];
    }

    /**
     * Empty wallet - zero balance and zero locked.
     */
    public function empty(): static
    {
        return $this->state(fn (array $attributes) => [
            'balance_paise' => 0,
            'locked_balance_paise' => 0,
        ]);
    }

    /**
     * Set balance in paise directly (canonical).
     */
    public function withBalancePaise(int $balancePaise): static
    {
        return $this->state(function (array $attributes) use ($balancePaise) {
            $lockedPaise = $attributes['locked_balance_paise'] ?? 0;

            // INVARIANT 3: Clamp locked if it would exceed new balance
            if ($lockedPaise > $balancePaise) {
                $lockedPaise = $balancePaise;
            }

            return [
                'balance_paise' => $balancePaise,
                'locked_balance_paise' => $lockedPaise,
            ];
        });
    }

    /**
     * Set locked balance in paise directly (canonical).
     * Will throw if it would violate invariant 3.
     */
    public function withLockedBalancePaise(int $lockedPaise): static
    {
        return $this->state(function (array $attributes) use ($lockedPaise) {
            $balancePaise = $attributes['balance_paise'] ?? 0;

            // INVARIANT 3: locked cannot exceed balance
            if ($lockedPaise > $balancePaise) {
                throw new \InvalidArgumentException(
                    "Invariant violation: locked_balance_paise ({$lockedPaise}) cannot exceed balance_paise ({$balancePaise})"
                );
            }

            return [
                'locked_balance_paise' => $lockedPaise,
            ];
        });
    }

    /**
     * Set balance in rupees (converts to paise internally).
     * @deprecated Use withBalancePaise() for precision
     */
    public function withBalance(float $balanceRupees): static
    {
        return $this->withBalancePaise((int) round($balanceRupees * 100));
    }

    /**
     * Set locked balance in rupees (converts to paise internally).
     * @deprecated Use withLockedBalancePaise() for precision
     */
    public function withLockedBalance(float $lockedRupees): static
    {
        return $this->withLockedBalancePaise((int) round($lockedRupees * 100));
    }

    /**
     * Convenience: Set both balance and locked in one call (paise).
     * Validates invariant 3 at call time.
     */
    public function withFunds(int $balancePaise, int $lockedPaise = 0): static
    {
        if ($lockedPaise > $balancePaise) {
            throw new \InvalidArgumentException(
                "Invariant violation: locked ({$lockedPaise}) cannot exceed balance ({$balancePaise})"
            );
        }

        return $this->state(fn () => [
            'balance_paise' => $balancePaise,
            'locked_balance_paise' => $lockedPaise,
        ]);
    }
}
