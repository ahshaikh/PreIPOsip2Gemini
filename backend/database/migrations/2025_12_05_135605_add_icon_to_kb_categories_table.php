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
	    Schema::table('kb_categories', function (Blueprint $table) {
	        // We store the class string (e.g., 'fas fa-home' or 'heroicon-cog')
	        $table->string('icon')->nullable()->after('name'); 
	    });
	}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            //
        });
    }
};
