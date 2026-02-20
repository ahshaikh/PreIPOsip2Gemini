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
        Schema::table('campaign_usages', function (Blueprint $table) {
            // Terms acceptance tracking
            $table->boolean('terms_accepted')->default(false)->after('campaign_snapshot');
            $table->timestamp('terms_accepted_at')->nullable()->after('terms_accepted');
            $table->string('terms_acceptance_ip', 45)->nullable()->after('terms_accepted_at');

            // Regulatory disclaimer acceptance
            $table->boolean('disclaimer_acknowledged')->default(false)->after('terms_acceptance_ip');
            $table->timestamp('disclaimer_acknowledged_at')->nullable()->after('disclaimer_acknowledged');

            // Indexes
            $table->index('terms_accepted');
            $table->index('disclaimer_acknowledged');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
{
    if (!Schema::hasTable('campaign_usages')) {
        return;
    }

    Schema::table('campaign_usages', function (Blueprint $table) {
        if (Schema::hasColumn('campaign_usages', 'terms_accepted')) {
            $table->dropIndex(['terms_accepted']);
        }

        if (Schema::hasColumn('campaign_usages', 'disclaimer_acknowledged')) {
            $table->dropIndex(['disclaimer_acknowledged']);
        }

        $table->dropColumn([
            'terms_accepted',
            'terms_accepted_at',
            'terms_acceptance_ip',
            'disclaimer_acknowledged',
            'disclaimer_acknowledged_at',
        ]);
    });
}

};
