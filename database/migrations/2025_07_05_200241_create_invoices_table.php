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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['invoice', 'estimate']);
            $table->ulid('ulid')->unique();
            $table->foreignId('organization_id')->constrained('teams')->onDelete('cascade');
            $table->foreignId('organization_location_id')->constrained('locations');
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->foreignId('customer_location_id')->constrained('locations');
            $table->string('invoice_number')->unique();
            $table->enum('status', ['draft', 'sent', 'paid', 'void']);
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('due_at')->nullable();

            // Financial fields with currency support
            $table->char('currency', 3);
            $table->unsignedBigInteger('exchange_rate')->default(1000000); // Exchange rate in micro-units (1.000000 = 1000000)
            $table->unsignedBigInteger('subtotal'); // Amount in cents
            $table->unsignedBigInteger('tax'); // Amount in cents
            $table->unsignedBigInteger('total'); // Amount in cents

            // Tax information
            $table->string('tax_type')->nullable(); // Flexible tax type (not enum)
            $table->json('tax_breakdown')->nullable(); // For complex tax structures

            // Email and communication
            $table->json('email_recipients')->nullable(); // Simple email array

            // Additional fields
            $table->text('notes')->nullable();
            $table->text('terms')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
