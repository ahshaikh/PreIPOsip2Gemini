<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legal_agreements', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // terms_of_service, privacy_policy, cookie_policy, investment_disclaimer, refund_policy, etc.
            $table->string('title');
            $table->text('description')->nullable();
            $table->longText('content');
            $table->string('version'); // e.g., 1.0.0, 2.1.0
            $table->enum('status', ['draft', 'review', 'active', 'archived', 'superseded'])->default('draft');
            $table->date('effective_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->boolean('require_signature')->default(false);
            $table->boolean('is_template')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('type');
            $table->index('status');
            $table->index(['type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legal_agreements');
    }
};
