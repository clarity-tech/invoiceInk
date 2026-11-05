<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * TDS the customer withheld from this payment and paid to the government
     * on the supplier's behalf, and the section they deducted it under. The
     * TDS settles the invoice alongside `amount`.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->unsignedBigInteger('tds_amount')->default(0)->after('amount');
            $table->string('tds_section', 20)->nullable()->after('tds_amount');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['tds_amount', 'tds_section']);
        });
    }
};
