<?php
// V-PRODUCT-DOCS-1210 (Created)

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->enum('document_type', [
                'prospectus',
                'financial_statement',
                'legal_agreement',
                'sebi_filing',
                'annual_report',
                'presentation',
                'other'
            ])->default('other');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('file_url'); // URL or path to document
            $table->string('file_type')->nullable(); // pdf, docx, xlsx, etc.
            $table->bigInteger('file_size')->nullable(); // Size in bytes
            $table->date('document_date')->nullable(); // Date of the document (e.g., report date)
            $table->boolean('is_public')->default(true); // Visible to all users
            $table->integer('display_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_documents');
    }
};
