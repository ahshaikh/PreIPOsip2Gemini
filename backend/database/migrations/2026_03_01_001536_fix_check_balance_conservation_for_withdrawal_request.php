<?php
// V-WALLET-FIRST-2026: Fix check_balance_conservation constraint for withdrawal_request
// ISSUE: withdrawal_request is categorized as a debit, but when lockBalance=true,
// the balance doesn't change (only locked_balance_paise increases).
// FIX: Exclude withdrawal_request from the debit balance check (treat like admin_adjustment).

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Enums\TransactionType;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Updates the check_balance_conservation constraint to treat withdrawal_request
     * as a special case (lock, not debit) that allows balance_after = balance_before.
     */
    public function up(): void
    {
        if (!Schema::hasTable('transactions')) {
            return;
        }

        // Drop existing constraint
        try {
            DB::statement("ALTER TABLE transactions DROP CONSTRAINT IF EXISTS check_balance_conservation");
        } catch (\Exception $e) {
            // Constraint might not exist, continue
        }

        // Get credit and debit types from the canonical TransactionType enum
        $creditTypes = array_map(fn ($type) => "'{$type->value}'", TransactionType::credits());

        // CRITICAL FIX: Exclude withdrawal_request from debits
        // withdrawal_request is a LOCK (balance unchanged), not a DEBIT (balance decremented)
        $debitTypes = array_filter(
            TransactionType::debits(),
            fn ($type) => $type !== TransactionType::WITHDRAWAL_REQUEST
        );
        $debitTypes = array_map(fn ($type) => "'{$type->value}'", $debitTypes);

        $creditTypesSql = implode(', ', $creditTypes);
        $debitTypesSql = implode(', ', $debitTypes);

        try {
            DB::statement("
                ALTER TABLE transactions
                ADD CONSTRAINT check_balance_conservation
                CHECK (
                    (type IN ({$creditTypesSql})
                        AND balance_after_paise = balance_before_paise + amount_paise)
                    OR
                    (type IN ({$debitTypesSql})
                        AND balance_after_paise = balance_before_paise - amount_paise)
                    OR
                    (type = 'admin_adjustment')
                    OR
                    (type = 'withdrawal_request')
                )
            ");
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning(
                "Could not update check_balance_conservation constraint - enforcing at application level only.",
                ['error' => $e->getMessage()]
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the updated constraint
        try {
            DB::statement("ALTER TABLE transactions DROP CONSTRAINT IF EXISTS check_balance_conservation");
        } catch (\Exception $e) {
            // Constraint might not exist
        }
    }
};
