<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Company Users Authentication Table
        Schema::create('company_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('contact_person_name');
            $table->string('contact_person_designation')->nullable();
            $table->string('phone')->nullable();
            $table->enum('status', ['pending', 'active', 'suspended', 'rejected'])->default('pending');
            $table->boolean('is_verified')->default(false);
            $table->timestamp('email_verified_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        // Company Financial Reports Table
        Schema::create('company_financial_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('uploaded_by')->constrained('company_users')->onDelete('cascade');
            $table->integer('year');
            $table->enum('quarter', ['Q1', 'Q2', 'Q3', 'Q4', 'Annual'])->default('Annual');
            $table->enum('report_type', ['financial_statement', 'balance_sheet', 'cash_flow', 'income_statement', 'annual_report', 'other'])->default('annual_report');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('file_path');
            $table->string('file_name');
            $table->bigInteger('file_size')->nullable(); // in bytes
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'year', 'quarter']);
        });

        // Company Documents Table
        Schema::create('company_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('uploaded_by')->constrained('company_users')->onDelete('cascade');
            $table->enum('document_type', ['logo', 'banner', 'pitch_deck', 'investor_presentation', 'legal_document', 'certificate', 'agreement', 'other'])->default('other');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('file_path');
            $table->string('file_name');
            $table->string('file_type')->nullable(); // mime type
            $table->bigInteger('file_size')->nullable(); // in bytes
            $table->boolean('is_public')->default(false);
            $table->enum('status', ['active', 'archived'])->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'document_type']);
        });

        // Company Updates/News Table
        Schema::create('company_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('created_by')->constrained('company_users')->onDelete('cascade');
            $table->string('title');
            $table->text('content');
            $table->enum('update_type', ['news', 'milestone', 'funding', 'product_launch', 'partnership', 'other'])->default('news');
            $table->json('media')->nullable(); // Array of image/video URLs
            $table->boolean('is_featured')->default(false);
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Company Team Members Table
        Schema::create('company_team_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->string('name');
            $table->string('designation');
            $table->text('bio')->nullable();
            $table->string('photo_path')->nullable();
            $table->string('linkedin_url')->nullable();
            $table->string('twitter_url')->nullable();
            $table->integer('display_order')->default(0);
            $table->boolean('is_key_member')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        // Company Funding Rounds Table
        Schema::create('company_funding_rounds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->string('round_name'); // e.g., Series A, Series B, Pre-IPO
            $table->decimal('amount_raised', 20, 2)->nullable();
            $table->string('currency', 10)->default('INR');
            $table->decimal('valuation', 20, 2)->nullable();
            $table->date('round_date')->nullable();
            $table->json('investors')->nullable(); // Array of investor names
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Add company_id relationship to existing companies table if needed
        if (!Schema::hasColumn('companies', 'is_verified')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->boolean('is_verified')->default(false)->after('status');
                $table->boolean('profile_completed')->default(false)->after('is_verified');
                $table->integer('profile_completion_percentage')->default(0)->after('profile_completed');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_funding_rounds');
        Schema::dropIfExists('company_team_members');
        Schema::dropIfExists('company_updates');
        Schema::dropIfExists('company_documents');
        Schema::dropIfExists('company_financial_reports');
        Schema::dropIfExists('company_users');

        if (Schema::hasColumn('companies', 'is_verified')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->dropColumn(['is_verified', 'profile_completed', 'profile_completion_percentage']);
            });
        }
    }
};
