<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * MCP clients now only sign in over OAuth, so the API tokens Connect AI
     * tools used to issue are dead credentials.
     */
    public function up(): void
    {
        DB::table('personal_access_tokens')->where('abilities', 'like', '%"mcp:read"%')->delete();
    }

    public function down(): void
    {
        //
    }
};
