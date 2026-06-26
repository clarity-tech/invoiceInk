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
        Schema::table('teams', function (Blueprint $table) {
            // ISO 3166-1 alpha-2 of the country picked for the phone number. Kept
            // alongside the E.164 `phone` value so the dial-code dropdown can be
            // restored on edit (e.g. distinguishing +1 US from +1 CA).
            $table->char('phone_country', 2)->nullable()->after('phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn('phone_country');
        });
    }
};
