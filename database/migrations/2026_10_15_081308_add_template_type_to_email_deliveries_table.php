<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('email_deliveries', function (Blueprint $table) {
            // Null for sends recorded before the template was tracked.
            $table->string('template_type', 40)->nullable()->after('mode');
        });
    }

    public function down(): void
    {
        Schema::table('email_deliveries', function (Blueprint $table) {
            $table->dropColumn('template_type');
        });
    }
};
