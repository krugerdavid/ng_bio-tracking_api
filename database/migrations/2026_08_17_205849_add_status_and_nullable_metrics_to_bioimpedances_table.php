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
        Schema::table('bioimpedances', function (Blueprint $table) {
            $table->string('status', 20)->default('confirmed')->after('notes');
            $table->decimal('height', 5, 2)->nullable()->change();
            $table->decimal('imc', 5, 2)->nullable()->change();
            $table->decimal('body_fat_percentage', 5, 2)->nullable()->change();
            $table->decimal('muscle_mass_percentage', 5, 2)->nullable()->change();
            $table->decimal('kcal', 8, 2)->nullable()->change();
            $table->decimal('metabolic_age', 5, 2)->nullable()->change();
            $table->decimal('visceral_fat_percentage', 5, 2)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bioimpedances', function (Blueprint $table) {
            $table->dropColumn('status');
            $table->decimal('height', 5, 2)->nullable(false)->change();
            $table->decimal('imc', 5, 2)->nullable(false)->change();
            $table->decimal('body_fat_percentage', 5, 2)->nullable(false)->change();
            $table->decimal('muscle_mass_percentage', 5, 2)->nullable(false)->change();
            $table->decimal('kcal', 8, 2)->nullable(false)->change();
            $table->decimal('metabolic_age', 5, 2)->nullable(false)->change();
            $table->decimal('visceral_fat_percentage', 5, 2)->nullable(false)->change();
        });
    }
};
