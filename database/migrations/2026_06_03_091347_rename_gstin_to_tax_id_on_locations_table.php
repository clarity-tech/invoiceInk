<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Rename the India-specific `gstin` column to the country-neutral `tax_id`.
     *
     * `locations.tax_id` is the per-location tax *registration* number — the
     * GSTIN (India) / VAT (EU/GB) / TRN (UAE) / EIN (US) equivalent. It is a
     * different concept from `teams.tax_number`, which is the entity-level tax
     * *identity* number (the PAN equivalent in India).
     */
    public function up(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->renameColumn('gstin', 'tax_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->renameColumn('tax_id', 'gstin');
        });
    }
};
