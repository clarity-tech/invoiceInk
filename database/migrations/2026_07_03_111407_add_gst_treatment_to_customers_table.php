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
        Schema::table('customers', function (Blueprint $table) {
            // GST treatment: 'domestic' or 'overseas' (see App\Enums\GstTreatment).
            // Overseas customers are exports and have no Indian place of supply.
            $table->string('gst_treatment', 20)->default('domestic')->after('place_of_supply');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('gst_treatment');
        });
    }
};
