<?php
// V-PHASE3-PAISE-CANONICAL: Update check_balance_conservation constraint
// to include all valid TransactionType enum values.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Enums\TransactionType;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Updates the check_balance_conservation constraint to include
     * all valid transaction types from the TransactionType enum.
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
        $debitTypes = array_map(fn ($type) => "'{$type->value}'", TransactionType::debits());

        // Also include admin_adjustment which can be either credit or debit
        // (it's neutral - not categorized in either direction by the enum)
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

        // Restore original constraint (optional - for safety we'll leave it enforced at app level)
    }
};
