<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Creates the 'ip_whitelist' table based on the fields required by the
     * IpWhitelistController (ip_address, description, is_active).
     */
    public function up(): void
    {
        Schema::create('ip_whitelist', function (Blueprint $table) {
            // Primary Key
            $table->id();

            // Data Fields
            // The IpWhitelistController uses a 'unique' rule on this column.
            $table->string('ip_address')->unique()->comment('The whitelisted IP address.');

            // Metadata
            $table->string('description', 255)->comment('A short description for why this IP is whitelisted.');
            
            // Status Field
            // The application logic relies on this field (WHERE is_active = 1).
            $table->boolean('is_active')->default(true)->index()->comment('Status for quick lookups and querying.');

            // Timestamps
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ip_whitelist');
    }
};