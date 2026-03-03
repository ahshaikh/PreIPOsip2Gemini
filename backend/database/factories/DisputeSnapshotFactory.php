<?php

namespace Database\Factories;

use App\Models\Dispute;
use App\Models\DisputeSnapshot;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DisputeSnapshotFactory extends Factory
{
    protected $model = DisputeSnapshot::class;

    public function definition(): array
    {
        $disputableSnapshot = [
            'type' => null,
            'id' => null,
            'data' => null,
            'captured_at' => now()->toIso8601String(),
        ];

        $walletSnapshot = [
            'wallet_id' => $this->faker->randomNumber(5),
            'user_id' => $this->faker->randomNumber(5),
            'balance_paise' => $this->faker->randomNumber(6),
            'locked_balance_paise' => 0,
            'balance' => $this->faker->randomFloat(2, 0, 10000),
            'available_balance' => $this->faker->randomFloat(2, 0, 10000),
            'locked_balance' => 0,
            'captured_at' => now()->toIso8601String(),
        ];

        $relatedTransactionsSnapshot = [
            'ledger_entries' => [],
            'bonus_transactions' => [],
            'related_payments' => [],
        ];

        $systemStateSnapshot = [
            'platform_version' => '1.0.0',
            'env' => 'testing',
            'withdrawal_settings' => [
                'min_withdrawal_amount' => 1000,
                'max_withdrawal_amount' => 100000,
            ],
            'dispute_settings' => [
                'auto_escalation_enabled' => true,
                'sla_hours_default' => 48,
            ],
            'captured_at' => now()->toIso8601String(),
        ];

        return [
            'dispute_id' => Dispute::factory(),
            'disputable_snapshot' => $disputableSnapshot,
            'wallet_snapshot' => $walletSnapshot,
            'related_transactions_snapshot' => $relatedTransactionsSnapshot,
            'system_state_snapshot' => $systemStateSnapshot,
            'integrity_hash' => hash('sha256', json_encode([
                'dispute_id' => null, // Will be set after create
                'disputable_snapshot' => $disputableSnapshot,
                'wallet_snapshot' => $walletSnapshot,
                'related_transactions_snapshot' => $relatedTransactionsSnapshot,
                'system_state_snapshot' => $systemStateSnapshot,
                'capture_trigger' => DisputeSnapshot::TRIGGER_DISPUTE_FILED,
            ])),
            'captured_by_user_id' => null,
            'capture_trigger' => DisputeSnapshot::TRIGGER_DISPUTE_FILED,
        ];
    }

    /**
     * Configure the model factory to compute proper integrity hash.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (DisputeSnapshot $snapshot) {
            // Recompute hash with actual dispute_id after making
            $snapshot->integrity_hash = $snapshot->computeIntegrityHash();
        });
    }

    /**
     * Admin-requested snapshot.
     */
    public function adminRequested(User $admin = null): static
    {
        return $this->state(fn(array $attributes) => [
            'capture_trigger' => DisputeSnapshot::TRIGGER_ADMIN_REQUEST,
            'captured_by_user_id' => $admin?->id ?? User::factory(),
        ]);
    }

    /**
     * Auto-escalation snapshot.
     */
    public function autoEscalation(): static
    {
        return $this->state(fn(array $attributes) => [
            'capture_trigger' => DisputeSnapshot::TRIGGER_AUTO_ESCALATION,
            'captured_by_user_id' => null,
        ]);
    }
}
