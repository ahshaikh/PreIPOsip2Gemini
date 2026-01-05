<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /**
         * LEGAL AGREEMENTS
         */
        if (Schema::hasTable('legal_agreements')) {
            Schema::table('legal_agreements', function (Blueprint $table) {
                if (!Schema::hasColumn('legal_agreements', 'is_active')) {
                    $table->boolean('is_active')->default(true);
                }
            });
        }

        /**
         * LEGAL AGREEMENT VERSIONS
         */
        if (Schema::hasTable('legal_agreement_versions')) {
            Schema::table('legal_agreement_versions', function (Blueprint $table) {
                if (!Schema::hasColumn('legal_agreement_versions', 'version_number')) {
                    $table->string('version_number')->nullable();
                }

                if (!Schema::hasColumn('legal_agreement_versions', 'created_by')) {
                    $table->unsignedBigInteger('created_by')->nullable();
                }

                if (!Schema::hasColumn('legal_agreement_versions', 'notes')) {
                    $table->text('notes')->nullable();
                }
            });
        }

        /**
         * USER AGREEMENT SIGNATURES
         */
        if (Schema::hasTable('user_agreement_signatures')) {
            Schema::table('user_agreement_signatures', function (Blueprint $table) {
                if (!Schema::hasColumn('user_agreement_signatures', 'user_id')) {
                    $table->unsignedBigInteger('user_id')->nullable();
                }

                if (!Schema::hasColumn('user_agreement_signatures', 'legal_agreement_id')) {
                    $table->unsignedBigInteger('legal_agreement_id')->nullable();
                }

                if (!Schema::hasColumn('user_agreement_signatures', 'legal_agreement_version_id')) {
                    $table->unsignedBigInteger('legal_agreement_version_id')->nullable();
                }

                if (!Schema::hasColumn('user_agreement_signatures', 'version_signed')) {
                    $table->string('version_signed')->nullable();
                }

                if (!Schema::hasColumn('user_agreement_signatures', 'ip_address')) {
                    $table->string('ip_address')->nullable();
                }

                if (!Schema::hasColumn('user_agreement_signatures', 'user_agent')) {
                    $table->text('user_agent')->nullable();
                }

                if (!Schema::hasColumn('user_agreement_signatures', 'signed_at')) {
                    $table->timestamp('signed_at')->nullable();
                }
            });
        }

        /**
         * USER LEGAL ACCEPTANCES
         */
        if (Schema::hasTable('user_legal_acceptances')) {
            Schema::table('user_legal_acceptances', function (Blueprint $table) {
                if (!Schema::hasColumn('user_legal_acceptances', 'accepted_at')) {
                    $table->timestamp('accepted_at')->nullable();
                }
            });
        }

        /**
         * PRIVACY REQUESTS (GDPR / DPDP / RTI)
         */
        if (Schema::hasTable('privacy_requests')) {
            Schema::table('privacy_requests', function (Blueprint $table) {
                if (!Schema::hasColumn('privacy_requests', 'user_id')) {
                    $table->unsignedBigInteger('user_id')->nullable();
                }

                if (!Schema::hasColumn('privacy_requests', 'type')) {
                    $table->string('type')->nullable()
                        ->comment('export, delete, rectify');
                }

                if (!Schema::hasColumn('privacy_requests', 'status')) {
                    $table->string('status')->nullable()
                        ->comment('pending, processing, completed, rejected');
                }

                if (!Schema::hasColumn('privacy_requests', 'requested_at')) {
                    $table->timestamp('requested_at')->nullable();
                }

                if (!Schema::hasColumn('privacy_requests', 'completed_at')) {
                    $table->timestamp('completed_at')->nullable();
                }

                if (!Schema::hasColumn('privacy_requests', 'details')) {
                    $table->json('details')->nullable();
                }

                if (!Schema::hasColumn('privacy_requests', 'ip_address')) {
                    $table->string('ip_address')->nullable();
                }
            });
        }

        /**
         * USER CONSENTS
         */
        if (Schema::hasTable('user_consents')) {
            Schema::table('user_consents', function (Blueprint $table) {
                if (!Schema::hasColumn('user_consents', 'consented_at')) {
                    $table->timestamp('consented_at')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        // Additive-only compliance migration
    }
};
