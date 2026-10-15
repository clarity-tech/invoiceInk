<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            // Off until an owner allows documents to be sent to the AI provider.
            $table->boolean('ai_drafting_enabled')->default(false)->after('setup_completed_at');
        });
    }

    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn('ai_drafting_enabled');
        });
    }
};
