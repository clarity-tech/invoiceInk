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
        Schema::table('invoices', function (Blueprint $table) {
            // Add customer shipping location field after customer_location_id
            // Nullable to support both fresh migrations and existing databases
            $table->foreignId('customer_shipping_location_id')
                ->nullable()
                ->after('customer_location_id')
                ->constrained('locations');
        });

        // For existing databases: Set existing invoices to use billing address as shipping address
        if (DB::table('invoices')->exists()) {
            DB::table('invoices')->update([
                'customer_shipping_location_id' => DB::raw('customer_location_id'),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['customer_shipping_location_id']);
            $table->dropColumn('customer_shipping_location_id');
        });
    }
};
