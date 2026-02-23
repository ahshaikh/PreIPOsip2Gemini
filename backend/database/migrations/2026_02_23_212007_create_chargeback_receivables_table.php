<?php
// V-WAVE3-REVERSAL-AUDIT: Dedicated receivable tracking table
// Each chargeback/refund shortfall creates a separate receivable record
// Enables granular audit trail and multi-receivable tracking per user

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chargeback_receivables', function (Blueprint $table) {
            $table->id();

            // User who owes the receivable
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Payment that caused this receivable (refund/chargeback source)
            $table->foreignId('payment_id')->constrained()->onDelete('cascade');

            // Ledger entry that recorded this receivable (for reconciliation)
            $table->foreignId('ledger_entry_id')->nullable()->constrained('ledger_entries')->onDelete('set null');

            // Amount owed in paise
            $table->bigInteger('amount_paise');

            // Amount paid towards this receivable
            $table->bigInteger('paid_paise')->default(0);

            // Remaining balance (computed: amount_paise - paid_paise)
            $table->bigInteger('balance_paise');

            // Status: pending, partial, settled, written_off
            $table->string('status', 20)->default('pending');

            // Source type: refund, chargeback
            $table->string('source_type', 20);

            // Reason for the receivable
            $table->text('reason')->nullable();

            // Timestamps for lifecycle tracking
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->timestamp('settled_at')->nullable();
            $table->timestamp('written_off_at')->nullable();

            // Admin who wrote off (if applicable)
            $table->foreignId('written_off_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('write_off_reason')->nullable();

            // Indexes for common queries
            $table->index(['user_id', 'status']);
            $table->index(['payment_id']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chargeback_receivables');
    }
};
