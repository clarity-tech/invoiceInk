<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('email_deliveries', function (Blueprint $table) {
            // Shared by the per-invoice rows of one email that covered several invoices.
            $table->ulid('batch_id')->nullable()->after('invoice_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('email_deliveries', function (Blueprint $table) {
            $table->dropColumn('batch_id');
        });
    }
};
