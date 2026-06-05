<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->unsignedBigInteger('amount_paid')->default(0)->after('total');
        });

        // `status` is a plain string column validated via App\Enums\InvoiceStatus,
        // so no DB-level enum/constraint update is needed to allow 'partially_paid'.

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->onDelete('cascade');
            $table->unsignedBigInteger('amount');
            $table->char('currency', 3);
            $table->date('payment_date');
            $table->string('payment_method')->nullable();
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('amount_paid');
        });
    }
};
