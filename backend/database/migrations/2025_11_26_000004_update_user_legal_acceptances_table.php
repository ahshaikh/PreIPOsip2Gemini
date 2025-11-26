<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_legal_acceptances', function (Blueprint $table) {
            // Make page_id nullable and add legal_agreement_id
            $table->foreignId('page_id')->nullable()->change();
            $table->foreignId('legal_agreement_id')->nullable()->after('page_id')->constrained('legal_agreements')->onDelete('cascade');
            $table->string('document_type')->nullable()->after('legal_agreement_id'); // terms_of_service, privacy_policy, etc.
            $table->string('accepted_version')->nullable()->after('page_version'); // Version string
            $table->text('user_agent')->nullable()->after('ip_address');

            // Drop the old unique constraint
            $table->dropUnique(['user_id', 'page_id', 'page_version']);

            // Add new indexes
            $table->index('legal_agreement_id');
            $table->index('document_type');
            $table->index(['user_id', 'document_type']);
        });
    }

    public function down(): void
    {
        Schema::table('user_legal_acceptances', function (Blueprint $table) {
            $table->dropForeign(['legal_agreement_id']);
            $table->dropColumn(['legal_agreement_id', 'document_type', 'accepted_version', 'user_agent']);
            $table->foreignId('page_id')->nullable(false)->change();
            $table->unique(['user_id', 'page_id', 'page_version']);
        });
    }
};
