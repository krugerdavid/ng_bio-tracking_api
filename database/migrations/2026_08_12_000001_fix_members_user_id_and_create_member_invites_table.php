<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE members MODIFY user_id BIGINT UNSIGNED NULL');
        }

        Schema::create('member_invites', function (Blueprint $table) {
            $table->id();
            $table->uuid('member_id');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('email');
            $table->string('token', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();

            $table->foreign('member_id')->references('id')->on('members')->cascadeOnDelete();
            $table->index(['email', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_invites');

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE members MODIFY user_id CHAR(36) NULL');
        }
    }
};
