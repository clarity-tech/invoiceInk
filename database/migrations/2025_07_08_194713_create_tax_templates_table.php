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
        Schema::create('tax_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('teams')->onDelete('cascade');
            $table->string('name'); // e.g., "GST 18%", "VAT 20%", "Sales Tax 8.25%"
            $table->string('type'); // e.g., "GST", "VAT", "Sales Tax", "TDS", "TCS"
            $table->decimal('rate', 5, 3); // Support up to 99.999% tax rate
            $table->string('category')->nullable(); // e.g., "CGST", "SGST", "IGST", "Standard Rate"
            $table->string('country_code', 2); // ISO 3166-1 alpha-2 country codes
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable(); // For country-specific data
            $table->timestamps();

            // Ensure unique templates per organization
            $table->unique(['organization_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_templates');
    }
};
