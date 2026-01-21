<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * PURPOSE: Create company_investments table for direct company investments
     * DISTINCTION: Separate from 'investments' table which handles SIP-based investments
     *
     * This table stores one-time direct investments in companies via investor portal
     */
    public function up(): void
    {
        Schema::create('company_investments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 15, 2); // Investment amount in rupees
            $table->foreignId('disclosure_snapshot_id')->nullable()->constrained('investment_disclosure_snapshots')->onDelete('set null');
            $table->enum('status', ['pending', 'active', 'cancelled'])->default('pending');
            $table->timestamp('invested_at')->nullable();
            $table->string('idempotency_key')->nullable()->unique(); // Prevent duplicate submissions
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index(['user_id', 'status']);
            $table->index('company_id');
            $table->index('status');
            $table->index('invested_at');
            $table->index('idempotency_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_investments');
    }
};
