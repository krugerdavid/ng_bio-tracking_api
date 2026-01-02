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
        Schema::create('bioimpedances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('member_id')->constrained('members')->onDelete('cascade');
            $table->date('date');
            $table->decimal('height', 5, 2);
            $table->decimal('weight', 5, 2);
            $table->decimal('imc', 5, 2);
            $table->decimal('body_fat_percentage', 5, 2);
            $table->decimal('muscle_mass_percentage', 5, 2);
            $table->decimal('kcal', 8, 2);
            $table->decimal('metabolic_age', 5, 2);
            $table->decimal('visceral_fat_percentage', 5, 2);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bioimpedances');
    }
};
