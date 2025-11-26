<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legal_agreement_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('legal_agreement_id')->constrained('legal_agreements')->onDelete('cascade');
            $table->string('version');
            $table->longText('content');
            $table->text('change_summary')->nullable();
            $table->enum('status', ['draft', 'review', 'active', 'archived', 'superseded'])->default('draft');
            $table->date('effective_date')->nullable();
            $table->integer('acceptance_count')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // Indexes
            $table->index('legal_agreement_id');
            $table->index(['legal_agreement_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legal_agreement_versions');
    }
};
