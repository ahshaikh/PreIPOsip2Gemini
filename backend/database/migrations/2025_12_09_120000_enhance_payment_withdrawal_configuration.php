<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Setting;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add new fields to payments table for analytics
        Schema::table('payments', function (Blueprint $table) {
            $table->string('payment_method')->nullable()->after('method'); // upi, card, netbanking, wallet
            $table->json('payment_metadata')->nullable()->after('gateway_signature'); // Store additional gateway data
            $table->timestamp('refunded_at')->nullable()->after('paid_at');
            $table->foreignId('refunded_by')->nullable()->constrained('users')->after('refunded_at');
        });

        // Add new fields to withdrawals table for fee tiers and processing
        Schema::table('withdrawals', function (Blueprint $table) {
            $table->string('priority')->default('normal')->after('status'); // low, normal, high
            $table->json('fee_breakdown')->nullable()->after('fee'); // Store tiered fee calculation details
            $table->timestamp('approved_at')->nullable()->after('admin_id');
            $table->timestamp('processed_at')->nullable()->after('approved_at');
            $table->text('admin_notes')->nullable()->after('rejection_reason');
        });

        // Insert payment & withdrawal configuration settings
        $settings = [
            // Payment Gateway Configuration
            [
                'key' => 'payment_gateway_razorpay_enabled',
                'value' => 'true',
                'type' => 'boolean',
                'group' => 'payment_gateway',
                'description' => 'Enable Razorpay payment gateway',
            ],
            [
                'key' => 'payment_gateway_razorpay_key',
                'value' => '',
                'type' => 'string',
                'group' => 'payment_gateway',
                'description' => 'Razorpay API Key',
            ],
            [
                'key' => 'payment_gateway_razorpay_secret',
                'value' => '',
                'type' => 'string',
                'group' => 'payment_gateway',
                'description' => 'Razorpay API Secret',
            ],
            [
                'key' => 'payment_gateway_stripe_enabled',
                'value' => 'false',
                'type' => 'boolean',
                'group' => 'payment_gateway',
                'description' => 'Enable Stripe payment gateway',
            ],
            [
                'key' => 'payment_gateway_stripe_key',
                'value' => '',
                'type' => 'string',
                'group' => 'payment_gateway',
                'description' => 'Stripe API Key',
            ],
            [
                'key' => 'payment_gateway_stripe_secret',
                'value' => '',
                'type' => 'string',
                'group' => 'payment_gateway',
                'description' => 'Stripe API Secret',
            ],
            [
                'key' => 'payment_gateway_paytm_enabled',
                'value' => 'false',
                'type' => 'boolean',
                'group' => 'payment_gateway',
                'description' => 'Enable Paytm payment gateway',
            ],

            // Payment Method Configuration
            [
                'key' => 'payment_method_upi_enabled',
                'value' => 'true',
                'type' => 'boolean',
                'group' => 'payment_methods',
                'description' => 'Enable UPI payments',
            ],
            [
                'key' => 'payment_method_upi_fee',
                'value' => '0',
                'type' => 'number',
                'group' => 'payment_methods',
                'description' => 'UPI transaction fee (flat amount)',
            ],
            [
                'key' => 'payment_method_upi_fee_percent',
                'value' => '0',
                'type' => 'number',
                'group' => 'payment_methods',
                'description' => 'UPI transaction fee (percentage)',
            ],
            [
                'key' => 'payment_method_card_enabled',
                'value' => 'true',
                'type' => 'boolean',
                'group' => 'payment_methods',
                'description' => 'Enable Card payments',
            ],
            [
                'key' => 'payment_method_card_fee',
                'value' => '0',
                'type' => 'number',
                'group' => 'payment_methods',
                'description' => 'Card transaction fee (flat amount)',
            ],
            [
                'key' => 'payment_method_card_fee_percent',
                'value' => '2',
                'type' => 'number',
                'group' => 'payment_methods',
                'description' => 'Card transaction fee (percentage)',
            ],
            [
                'key' => 'payment_method_netbanking_enabled',
                'value' => 'true',
                'type' => 'boolean',
                'group' => 'payment_methods',
                'description' => 'Enable Net Banking payments',
            ],
            [
                'key' => 'payment_method_netbanking_fee',
                'value' => '0',
                'type' => 'number',
                'group' => 'payment_methods',
                'description' => 'Net Banking transaction fee (flat amount)',
            ],
            [
                'key' => 'payment_method_netbanking_fee_percent',
                'value' => '1',
                'type' => 'number',
                'group' => 'payment_methods',
                'description' => 'Net Banking transaction fee (percentage)',
            ],
            [
                'key' => 'payment_method_wallet_enabled',
                'value' => 'true',
                'type' => 'boolean',
                'group' => 'payment_methods',
                'description' => 'Enable Wallet payments (Paytm, PhonePe, etc.)',
            ],

            // Auto-Debit Configuration
            [
                'key' => 'auto_debit_enabled',
                'value' => 'true',
                'type' => 'boolean',
                'group' => 'auto_debit',
                'description' => 'Enable auto-debit for subscriptions',
            ],
            [
                'key' => 'auto_debit_max_retries',
                'value' => '3',
                'type' => 'number',
                'group' => 'auto_debit',
                'description' => 'Maximum retry attempts for failed auto-debits',
            ],
            [
                'key' => 'auto_debit_retry_interval_days',
                'value' => '1',
                'type' => 'number',
                'group' => 'auto_debit',
                'description' => 'Days between retry attempts',
            ],
            [
                'key' => 'auto_debit_reminder_days',
                'value' => '3',
                'type' => 'number',
                'group' => 'auto_debit',
                'description' => 'Days before due date to send payment reminder',
            ],
            [
                'key' => 'auto_debit_suspend_after_max_retries',
                'value' => 'true',
                'type' => 'boolean',
                'group' => 'auto_debit',
                'description' => 'Suspend subscription after max retry failures',
            ],

            // Payment Processing Configuration
            [
                'key' => 'min_payment_amount',
                'value' => '500',
                'type' => 'number',
                'group' => 'payment_config',
                'description' => 'Minimum payment amount allowed',
            ],
            [
                'key' => 'max_payment_amount',
                'value' => '1000000',
                'type' => 'number',
                'group' => 'payment_config',
                'description' => 'Maximum payment amount allowed',
            ],
            [
                'key' => 'payment_manual_approval_required',
                'value' => 'true',
                'type' => 'boolean',
                'group' => 'payment_config',
                'description' => 'Require admin approval for manual payments',
            ],
            [
                'key' => 'payment_refund_allowed_days',
                'value' => '30',
                'type' => 'number',
                'group' => 'payment_config',
                'description' => 'Days within which refunds are allowed',
            ],

            // Withdrawal Fee Tier Configuration
            [
                'key' => 'withdrawal_fee_tier_1_max',
                'value' => '5000',
                'type' => 'number',
                'group' => 'withdrawal_fees',
                'description' => 'Tier 1: Maximum amount for lowest fee',
            ],
            [
                'key' => 'withdrawal_fee_tier_1_flat',
                'value' => '0',
                'type' => 'number',
                'group' => 'withdrawal_fees',
                'description' => 'Tier 1: Flat fee',
            ],
            [
                'key' => 'withdrawal_fee_tier_1_percent',
                'value' => '0',
                'type' => 'number',
                'group' => 'withdrawal_fees',
                'description' => 'Tier 1: Percentage fee',
            ],
            [
                'key' => 'withdrawal_fee_tier_2_max',
                'value' => '25000',
                'type' => 'number',
                'group' => 'withdrawal_fees',
                'description' => 'Tier 2: Maximum amount',
            ],
            [
                'key' => 'withdrawal_fee_tier_2_flat',
                'value' => '10',
                'type' => 'number',
                'group' => 'withdrawal_fees',
                'description' => 'Tier 2: Flat fee',
            ],
            [
                'key' => 'withdrawal_fee_tier_2_percent',
                'value' => '0.5',
                'type' => 'number',
                'group' => 'withdrawal_fees',
                'description' => 'Tier 2: Percentage fee',
            ],
            [
                'key' => 'withdrawal_fee_tier_3_max',
                'value' => '100000',
                'type' => 'number',
                'group' => 'withdrawal_fees',
                'description' => 'Tier 3: Maximum amount',
            ],
            [
                'key' => 'withdrawal_fee_tier_3_flat',
                'value' => '25',
                'type' => 'number',
                'group' => 'withdrawal_fees',
                'description' => 'Tier 3: Flat fee',
            ],
            [
                'key' => 'withdrawal_fee_tier_3_percent',
                'value' => '1',
                'type' => 'number',
                'group' => 'withdrawal_fees',
                'description' => 'Tier 3: Percentage fee',
            ],
            [
                'key' => 'withdrawal_fee_tier_4_flat',
                'value' => '50',
                'type' => 'number',
                'group' => 'withdrawal_fees',
                'description' => 'Tier 4: Flat fee (above tier 3)',
            ],
            [
                'key' => 'withdrawal_fee_tier_4_percent',
                'value' => '1.5',
                'type' => 'number',
                'group' => 'withdrawal_fees',
                'description' => 'Tier 4: Percentage fee (above tier 3)',
            ],

            // Withdrawal Processing Configuration
            [
                'key' => 'withdrawal_processing_days',
                'value' => '3',
                'type' => 'number',
                'group' => 'withdrawal_config',
                'description' => 'Standard processing days for withdrawals',
            ],
            [
                'key' => 'withdrawal_priority_processing_enabled',
                'value' => 'true',
                'type' => 'boolean',
                'group' => 'withdrawal_config',
                'description' => 'Enable priority processing for high-value withdrawals',
            ],
            [
                'key' => 'withdrawal_priority_threshold',
                'value' => '50000',
                'type' => 'number',
                'group' => 'withdrawal_config',
                'description' => 'Amount threshold for priority processing',
            ],
            [
                'key' => 'withdrawal_bulk_processing_limit',
                'value' => '50',
                'type' => 'number',
                'group' => 'withdrawal_config',
                'description' => 'Maximum number of withdrawals per bulk operation',
            ],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['refunded_by']);
            $table->dropColumn([
                'payment_method',
                'payment_metadata',
                'refunded_at',
                'refunded_by',
            ]);
        });

        Schema::table('withdrawals', function (Blueprint $table) {
            $table->dropColumn([
                'priority',
                'fee_breakdown',
                'approved_at',
                'processed_at',
                'admin_notes',
            ]);
        });

        // Delete settings
        Setting::whereIn('group', [
            'payment_gateway',
            'payment_methods',
            'auto_debit',
            'payment_config',
            'withdrawal_fees',
            'withdrawal_config',
        ])->delete();
    }
};
