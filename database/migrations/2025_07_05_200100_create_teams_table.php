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
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->index();
            $table->string('name');
            $table->boolean('personal_team');

            // Business organization fields
            $table->string('company_name')->nullable();
            $table->string('tax_number')->nullable();
            $table->string('registration_number')->nullable();
            $table->json('emails')->nullable();
            $table->string('phone')->nullable();
            $table->string('website')->nullable();
            $table->char('currency', 3);
            $table->text('notes')->nullable();

            // Location relationship
            $table->foreignId('primary_location_id')->nullable()->constrained('locations')->onDelete('set null');

            // Jetstream team features
            $table->string('custom_domain')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teams');
    }
};
