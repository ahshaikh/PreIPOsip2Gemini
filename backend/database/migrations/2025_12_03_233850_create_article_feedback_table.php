<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::create('article_feedback', function (Blueprint $table) {
        $table->id();
        $table->string('article_id')->index(); // e.g., 'tx-1'
        $table->boolean('is_helpful'); // 1 = Yes, 0 = No
        $table->text('comment')->nullable(); // For "No" feedback
        $table->ipAddress('ip_address')->nullable(); // To prevent spam
        $table->unsignedBigInteger('user_id')->nullable(); // If logged in
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('article_feedback');
    }
};
