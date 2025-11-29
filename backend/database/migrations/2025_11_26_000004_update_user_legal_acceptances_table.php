<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_legal_acceptances', function (Blueprint $table) {
            /**
             * Drop foreign keys first (they depend on indexes).
             */
            $table->dropForeign('user_legal_acceptances_user_id_foreign'); // FK on user_id
            $table->dropForeign(['page_id']); // FK on page_id (removes its index too)

            /**
             * Drop the old unique constraint.
             */
            $table->dropUnique('user_legal_acceptances_user_id_page_id_page_version_unique');

            /**
             * Modify page_id to be nullable.
             */
            $table->unsignedBigInteger('page_id')->nullable()->change();

            /**
             * Add new columns.
             */
            $table->foreignId('legal_agreement_id')
                  ->nullable()
                  ->after('page_id')
                  ->constrained('legal_agreements')
                  ->onDelete('cascade');

            $table->string('document_type')->nullable()->after('legal_agreement_id');
            $table->string('accepted_version')->nullable()->after('page_version');
            $table->text('user_agent')->nullable()->after('ip_address');

            /**
             * Add new indexes.
             */
            $table->index('legal_agreement_id');
            $table->index('document_type');
            $table->index(['user_id', 'document_type']);
        });
    }

    public function down(): void
    {
        Schema::table('user_legal_acceptances', function (Blueprint $table) {
            /**
             * Drop new foreign key and columns.
             */
            $table->dropForeign(['legal_agreement_id']);
            $table->dropColumn(['legal_agreement_id', 'document_type', 'accepted_version', 'user_agent']);

            /**
             * Restore page_id to NOT NULL.
             */
            $table->unsignedBigInteger('page_id')->nullable(false)->change();

            /**
             * Restore old unique constraint.
             */
            $table->unique(['user_id', 'page_id', 'page_version']);

            /**
             * Restore old foreign keys.
             */
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');

            // IMPORTANT: adjust the referenced table name if page_id points elsewhere
            $table->foreign('page_id')
                  ->references('id')
                  ->on('pages') // <-- change 'pages' if your schema uses a different table
                  ->onDelete('cascade');
        });
    }
};
