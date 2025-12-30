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
            $table->index(['organization_id', 'type', 'status'], 'invoices_org_type_status_index');
            $table->index(['organization_id', 'issued_at'], 'invoices_org_issued_at_index');
            $table->index(['customer_id', 'type'], 'invoices_customer_type_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex('invoices_org_type_status_index');
            $table->dropIndex('invoices_org_issued_at_index');
            $table->dropIndex('invoices_customer_type_index');
        });
    }
};
