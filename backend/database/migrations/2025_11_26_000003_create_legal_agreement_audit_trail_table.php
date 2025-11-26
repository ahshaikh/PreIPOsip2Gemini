<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legal_agreement_audit_trail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('legal_agreement_id')->constrained('legal_agreements')->onDelete('cascade');
            $table->string('event_type'); // created, updated, deleted, published, archived, viewed, shared, downloaded, version_created, status_changed, accepted, declined
            $table->text('description');
            $table->json('changes')->nullable(); // Store before/after values
            $table->string('version')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('user_name')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('legal_agreement_id');
            $table->index('event_type');
            $table->index('user_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legal_agreement_audit_trail');
    }
};
