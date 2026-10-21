<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Taste-profile module — see docs/specs/SPEC-taste-profile.md
 */
return new class extends Migration
{
    public function up(): void
    {
        // M:N users <-> top-level flavor categories they like
        Schema::create('user_taste_preferences', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('flavor_category_id')->constrained()->cascadeOnDelete();
            $table->primary(['user_id', 'flavor_category_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->enum('preferred_roast_level', ['light', 'medium', 'dark'])->nullable()->after('role');
            $table->enum('preferred_brew_method', ['pour_over', 'french_press', 'espresso', 'moka_pot', 'aeropress', 'cold_brew', 'drip_bag'])->nullable()->after('preferred_roast_level');
            $table->dateTime('taste_quiz_completed_at')->nullable()->after('preferred_brew_method');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['preferred_roast_level', 'preferred_brew_method', 'taste_quiz_completed_at']);
        });
        Schema::dropIfExists('user_taste_preferences');
    }
};
