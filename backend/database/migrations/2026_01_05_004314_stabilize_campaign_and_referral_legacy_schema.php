<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /**
         * CAMPAIGNS (Canonical)
         */
        if (Schema::hasTable('campaigns')) {
            Schema::table('campaigns', function (Blueprint $table) {
                if (!Schema::hasColumn('campaigns', 'is_active')) {
                    $table->boolean('is_active')->default(true);
                }

                if (!Schema::hasColumn('campaigns', 'starts_at')) {
                    $table->timestamp('starts_at')->nullable();
                }

                if (!Schema::hasColumn('campaigns', 'ends_at')) {
                    $table->timestamp('ends_at')->nullable();
                }

                if (!Schema::hasColumn('campaigns', 'max_usages')) {
                    $table->unsignedInteger('max_usages')->nullable();
                }
            });
        }

        /**
         * CAMPAIGN USAGES (formerly offer_usages)
         */
        if (Schema::hasTable('campaign_usages')) {
            Schema::table('campaign_usages', function (Blueprint $table) {
                if (!Schema::hasColumn('campaign_usages', 'investment_id')) {
                    $table->unsignedBigInteger('investment_id')->nullable();
                }

                if (!Schema::hasColumn('campaign_usages', 'discount_applied')) {
                    $table->decimal('discount_applied', 15, 2)->default(0);
                }

                if (!Schema::hasColumn('campaign_usages', 'used_at')) {
                    $table->timestamp('used_at')->nullable();
                }
            });
        }

        /**
         * OFFER STATISTICS (LEGACY, READ-ONLY)
         */
        if (Schema::hasTable('offer_statistics')) {
            Schema::table('offer_statistics', function (Blueprint $table) {
                if (!Schema::hasColumn('offer_statistics', 'offer_id')) {
                    $table->unsignedBigInteger('offer_id')->nullable();
                }

                if (!Schema::hasColumn('offer_statistics', 'campaign_id')) {
                    $table->unsignedBigInteger('campaign_id')->nullable();
                }

                if (!Schema::hasColumn('offer_statistics', 'stat_date')) {
                    $table->date('stat_date')->nullable();
                }

                if (!Schema::hasColumn('offer_statistics', 'total_views')) {
                    $table->unsignedInteger('total_views')->default(0);
                }

                if (!Schema::hasColumn('offer_statistics', 'total_applications')) {
                    $table->unsignedInteger('total_applications')->default(0);
                }

                if (!Schema::hasColumn('offer_statistics', 'total_conversions')) {
                    $table->unsignedInteger('total_conversions')->default(0);
                }

                if (!Schema::hasColumn('offer_statistics', 'total_discount_given')) {
                    $table->decimal('total_discount_given', 15, 2)->default(0);
                }

                if (!Schema::hasColumn('offer_statistics', 'total_revenue_generated')) {
                    $table->decimal('total_revenue_generated', 15, 2)->default(0);
                }

                if (!Schema::hasColumn('offer_statistics', 'conversion_rate')) {
                    $table->decimal('conversion_rate', 5, 2)->default(0);
                }
            });
        }

        /**
         * REFERRAL CAMPAIGNS
         */
        if (Schema::hasTable('referral_campaigns')) {
            Schema::table('referral_campaigns', function (Blueprint $table) {
                if (!Schema::hasColumn('referral_campaigns', 'slug')) {
                    $table->string('slug')->nullable();
                }

                if (!Schema::hasColumn('referral_campaigns', 'description')) {
                    $table->text('description')->nullable();
                }

                if (!Schema::hasColumn('referral_campaigns', 'starts_at')) {
                    $table->timestamp('starts_at')->nullable();
                }

                if (!Schema::hasColumn('referral_campaigns', 'ends_at')) {
                    $table->timestamp('ends_at')->nullable();
                }

                if (!Schema::hasColumn('referral_campaigns', 'max_referrals')) {
                    $table->unsignedInteger('max_referrals')->nullable();
                }
            });
        }

        /**
         * REFERRAL TRANSACTIONS
         */
        if (Schema::hasTable('referral_transactions')) {
            Schema::table('referral_transactions', function (Blueprint $table) {
                if (!Schema::hasColumn('referral_transactions', 'referrer_id')) {
                    $table->unsignedBigInteger('referrer_id')->nullable();
                }

                if (!Schema::hasColumn('referral_transactions', 'referee_id')) {
                    $table->unsignedBigInteger('referee_id')->nullable();
                }

                if (!Schema::hasColumn('referral_transactions', 'investment_id')) {
                    $table->unsignedBigInteger('investment_id')->nullable();
                }

                if (!Schema::hasColumn('referral_transactions', 'amount_paise')) {
                    $table->bigInteger('amount_paise')->default(0);
                }

                if (!Schema::hasColumn('referral_transactions', 'level')) {
                    $table->unsignedTinyInteger('level')->default(1);
                }

                if (!Schema::hasColumn('referral_transactions', 'status')) {
                    $table->string('status')->default('pending');
                }
            });
        }
    }

    public function down(): void
    {
        // Intentionally empty — additive-only post-audit migration
    }
};
