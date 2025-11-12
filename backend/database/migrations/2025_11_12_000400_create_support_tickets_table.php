<?php
// V-REMEDIATE-1730-145

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
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('ticket_code')->unique();
            $table->string('subject');
            $table->string('category')->default('general'); // general, payment, kyc, technical
            $table->string('priority')->default('medium'); // low, medium, high
            $table->string('status')->default('open'); // open, waiting_for_user, waiting_for_support, resolved
            $table->foreignId('resolved_by')->nullable()->constrained('users'); // Admin who resolved it
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('support_tickets');
    }
};