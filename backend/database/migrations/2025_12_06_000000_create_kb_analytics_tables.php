<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Track individual views
        Schema::create('kb_article_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kb_article_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('user_id')->nullable(); // Nullable for guests
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent')->nullable(); // To detect devices/bots
            $table->timestamps();
            
            // Indexes for fast dashboard loading
            $table->index(['kb_article_id', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('kb_article_views');
    }
};