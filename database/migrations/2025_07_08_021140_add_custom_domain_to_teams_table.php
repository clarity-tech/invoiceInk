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
        Schema::table('teams', function (Blueprint $table) {
            $table->string('slug', 50)->unique()->nullable()->after('name');
            $table->string('custom_domain', 100)->unique()->nullable()->after('slug');
            $table->index(['slug']);
            $table->index(['custom_domain']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropIndex(['custom_domain']);
            $table->dropIndex(['slug']);
            $table->dropColumn(['custom_domain', 'slug']);
        });
    }
};
