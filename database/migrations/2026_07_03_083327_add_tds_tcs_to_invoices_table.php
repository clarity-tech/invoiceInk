<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * TDS (Tax Deducted at Source) is deducted by the buyer and reduces the
     * amount payable; TCS (Tax Collected at Source) is collected by the seller
     * and adds to it. Rates are stored as basis points (1000 = 10%) and
     * amounts as integer cents, mirroring the item tax columns.
     */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->unsignedInteger('tds_rate')->nullable()->after('tax_breakdown');
            $table->unsignedBigInteger('tds_amount')->default(0)->after('tds_rate');
            $table->string('tds_section')->nullable()->after('tds_amount');
            $table->unsignedInteger('tcs_rate')->nullable()->after('tds_section');
            $table->unsignedBigInteger('tcs_amount')->default(0)->after('tcs_rate');
            $table->string('tcs_section')->nullable()->after('tcs_amount');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn([
                'tds_rate',
                'tds_amount',
                'tds_section',
                'tcs_rate',
                'tcs_amount',
                'tcs_section',
            ]);
        });
    }
};
