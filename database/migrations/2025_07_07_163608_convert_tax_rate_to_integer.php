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
        Schema::table('invoice_items', function (Blueprint $table) {
            // Change tax_rate from decimal(5,2) to unsignedSmallInteger
            // This stores basis points (e.g., 1800 for 18%)
            // Range: 0-65,535 (supports up to 655.35%)
            $table->dropColumn('tax_rate');
            $table->unsignedSmallInteger('tax_rate')->nullable()->after('unit_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropColumn('tax_rate');
            $table->decimal('tax_rate', 5, 2)->nullable()->after('unit_price');
        });
    }
};
