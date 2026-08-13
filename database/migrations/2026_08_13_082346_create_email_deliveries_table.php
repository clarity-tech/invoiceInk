<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Records the outcome of every document email.
 *
 * Until now the send was fire-and-forget: the controller queued a mailable and
 * immediately marked the invoice SENT, so a failure left the invoice claiming it
 * had been sent with nothing recording otherwise. This table is where an outcome
 * can actually live.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_deliveries', function (Blueprint $table) {
            $table->id();

            $table->foreignId('organization_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();

            $table->string('mailable_type');
            // Which sending identity was used. Only 'platform' exists today; the
            // per-organization SMTP and domain modes populate the others.
            $table->string('mode', 20)->default('platform');
            $table->string('status', 20)->default('queued');

            $table->json('recipients');
            $table->text('error')->nullable();
            $table->unsignedSmallInteger('attempts')->default(0);

            $table->timestamp('queued_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();

            $table->timestamps();

            // Drives "the latest delivery for this invoice", which is what the
            // listing and the invoice page both read.
            $table->index(['invoice_id', 'created_at']);
            // Drives the organization-scoped listing of recent failures.
            $table->index(['organization_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_deliveries');
    }
};
