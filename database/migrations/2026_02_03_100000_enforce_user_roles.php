<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Role values enforced in app (Role enum + validation). Default 'member'.
     */
    public function up(): void
    {
        DB::table('users')->where('role', 'user')->orWhereNull('role')->update(['role' => 'member']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reversible data change; role column stays.
    }
};
