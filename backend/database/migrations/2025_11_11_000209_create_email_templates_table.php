// V-PHASE2-1730-033
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "Welcome Email"
            $table->string('slug')->unique(); // e.g., "auth.welcome"
            $table->string('subject');
            $table->text('body'); // Storing HTML content
            $table->json('variables')->nullable(); // Hint for available variables
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_templates');
    }
};