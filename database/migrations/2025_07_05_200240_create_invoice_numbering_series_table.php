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
        Schema::create('invoice_numbering_series', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('teams')->onDelete('cascade');
            $table->foreignId('location_id')->nullable()->constrained('locations')->onDelete('cascade');
            $table->string('name', 100); // e.g., "Default", "Dubai Branch", "Main Office"
            $table->string('prefix', 20); // e.g., "INV", "EST", "INV-DXB"
            $table->string('format_pattern', 255); // e.g., "{PREFIX}-{YEAR}-{MONTH}-{SEQUENCE:4}"
            $table->integer('current_number'); // Current sequence number
            $table->string('reset_frequency', 20);
            $table->boolean('is_active');
            $table->boolean('is_default');
            $table->timestamp('last_reset_at')->nullable();
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['organization_id', 'location_id']);
            $table->index(['organization_id', 'is_active']);
            $table->index(['organization_id', 'is_default']);
            
            // Note: Unique constraint for default series will be handled at application level
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_numbering_series');
    }
};
