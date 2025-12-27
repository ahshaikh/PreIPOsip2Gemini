<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Company Share Listings - Self-service share upload workflow.
     *
     * BUSINESS FLOW:
     * 1. Company submits share listing with details
     * 2. Admin reviews submission
     * 3. Admin approves → BulkPurchase created (inventory)
     * 4. Admin can create Deal from approved listing
     * 5. Company notified of approval/rejection
     */
    public function up(): void
    {
        Schema::create('company_share_listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('submitted_by')->constrained('company_users')->onDelete('cascade');

            // Share Details
            $table->string('listing_title');
            $table->text('description');
            $table->decimal('total_shares_offered', 15, 4)->comment('Total shares company wants to sell');
            $table->decimal('face_value_per_share', 10, 2);
            $table->decimal('asking_price_per_share', 10, 2)->comment('Price company wants');
            $table->decimal('total_value', 20, 2)->comment('Total offering value (shares * asking price)');
            $table->decimal('minimum_purchase_value', 15, 2)->nullable()->comment('Minimum platform must buy');

            // Company Valuation Context
            $table->decimal('current_company_valuation', 20, 2)->nullable();
            $table->string('valuation_currency', 3)->default('INR');
            $table->decimal('percentage_of_company', 5, 4)->nullable()->comment('% of company these shares represent');

            // Terms & Conditions
            $table->text('terms_and_conditions')->nullable();
            $table->date('offer_valid_until')->nullable()->comment('How long offer is valid');
            $table->json('lock_in_period')->nullable()->comment('Any restrictions on resale');
            $table->json('rights_attached')->nullable()->comment('Voting rights, dividends, etc');

            // Supporting Documents
            $table->json('documents')->nullable()->comment('Share certificates, board resolution, etc');
            $table->json('financial_documents')->nullable()->comment('Balance sheet, P&L, etc');

            // Admin Review
            $table->enum('status', [
                'pending',      // Waiting for admin review
                'under_review', // Admin is reviewing
                'approved',     // Admin approved - inventory created
                'rejected',     // Admin rejected
                'expired',      // Offer validity expired
                'withdrawn'     // Company withdrew the listing
            ])->default('pending');

            $table->foreignId('reviewed_by')->nullable()->constrained('users')->comment('Admin who reviewed');
            $table->timestamp('reviewed_at')->nullable();
            $table->text('admin_notes')->nullable()->comment('Admin review notes');
            $table->text('rejection_reason')->nullable();

            // Approval & Inventory Link
            $table->foreignId('bulk_purchase_id')->nullable()->constrained('bulk_purchases')->comment('Created on approval');
            $table->decimal('approved_quantity', 15, 4)->nullable()->comment('May be less than offered');
            $table->decimal('approved_price', 10, 2)->nullable()->comment('Final negotiated price');
            $table->decimal('discount_percentage', 5, 2)->nullable()->comment('Discount from asking price');

            // Tracking
            $table->integer('view_count')->default(0)->comment('How many admins viewed');
            $table->timestamp('last_viewed_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['company_id', 'status']);
            $table->index('status');
            $table->index('offer_valid_until');
        });

        // Add activity log for listing workflow
        Schema::create('company_share_listing_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->constrained('company_share_listings')->onDelete('cascade');
            $table->foreignId('actor_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('actor_type')->comment('company_user or admin');
            $table->string('action')->comment('submitted, viewed, approved, rejected, etc');
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable()->comment('Changed fields, etc');
            $table->timestamp('created_at');

            $table->index(['listing_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_share_listing_activities');
        Schema::dropIfExists('company_share_listings');
    }
};
